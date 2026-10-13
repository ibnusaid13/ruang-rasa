<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\HmacGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Penerima callback gateway (KERANGKA). Pembayaran hanya ditandai paid setelah
 * tanda tangan callback terverifikasi di server. Idempotent: callback yang sama
 * tidak memproses transaksi dua kali.
 */
class PaymentWebhookController extends Controller
{
    public function handle(Request $request, HmacGateway $gateway)
    {
        if (! config('shop.gateway.webhook_secret')) {
            return response()->json(['message' => 'Gateway belum dikonfigurasi.'], 503);
        }

        $raw = $request->getContent();
        if (! $gateway->verifyWebhook($raw, $request->header('X-Signature'))) {
            return response()->json(['message' => 'Signature tidak valid.'], 401);
        }

        $payload = json_decode($raw, true);
        $reference = $payload['transaction_reference'] ?? null;
        $orderNumber = $payload['order_number'] ?? null;
        $status = $payload['status'] ?? null;

        if (! $reference || ! $orderNumber || ! in_array($status, ['paid', 'failed'], true)) {
            return response()->json(['message' => 'Payload tidak valid.'], 422);
        }

        return DB::transaction(function () use ($reference, $orderNumber, $status) {
            $payment = Payment::where('method', 'gateway')
                ->whereHas('order', fn ($q) => $q->where('order_number', $orderNumber))
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
            }

            // Idempotent: sudah final -> abaikan.
            if (in_array($payment->status, ['paid', 'failed', 'refunded', 'cancelled'], true)) {
                return response()->json(['message' => 'Sudah diproses.']);
            }

            $payment->update([
                'status' => $status,
                'transaction_reference' => $reference,
                'paid_at' => $status === 'paid' ? now() : null,
            ]);

            if ($status === 'paid' && $payment->order->status === 'pending') {
                $payment->order->update(['status' => 'processing']);
            }

            return response()->json(['message' => 'OK']);
        });
    }
}
