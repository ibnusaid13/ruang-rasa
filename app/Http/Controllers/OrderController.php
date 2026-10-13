<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payments\HmacGateway;
use App\Support\PaymentMethods;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function show(string $uuid)
    {
        $order = $this->findByUuid($uuid);

        return view('orders.show', [
            'order' => $order,
            'bank' => PaymentMethods::bank(),
            'qrisImage' => \App\Models\Setting::get('qris_image'),
            'gatewayUrl' => $order->payment->method === 'gateway' ? app(HmacGateway::class)->paymentUrl($order) : null,
        ]);
    }

    public function receipt(string $uuid)
    {
        return view('orders.receipt', ['order' => $this->findByUuid($uuid)]);
    }

    /** Upload bukti: hanya mengubah status ke "menunggu verifikasi", TIDAK menandai paid. */
    public function uploadProof(Request $request, string $uuid)
    {
        $order = $this->findByUuid($uuid);
        $payment = $order->payment;

        abort_unless($payment->canUploadProof() && $order->status !== 'cancelled', 403);

        $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'proof.mimes' => 'Bukti harus berupa JPG, PNG, atau PDF.',
            'proof.max' => 'Ukuran bukti maksimal 4 MB.',
        ]);

        // Disimpan di disk privat (storage/app/private), tidak bisa diakses via URL publik.
        $path = $request->file('proof')->store('payment-proofs', 'local');

        $payment->update([
            'proof_path' => $path,
            'status' => 'awaiting_verification',
            'admin_note' => null,
        ]);

        return back()->with('success', 'Bukti pembayaran terkirim. Admin akan memverifikasi pembayaran Anda.');
    }

    private function findByUuid(string $uuid): Order
    {
        abort_unless(Str::isUuid($uuid), 404);

        return Order::with(['items', 'payment'])->where('uuid', $uuid)->firstOrFail();
    }
}
