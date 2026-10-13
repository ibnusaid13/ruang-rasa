<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with('order')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('admin.payments.index', ['payments' => $query->paginate(15)->withQueryString()]);
    }

    public function show(Payment $payment)
    {
        return view('admin.payments.show', ['payment' => $payment->load('order.items')]);
    }

    /** Bukti ada di disk privat; hanya bisa dilihat lewat route admin ini. */
    public function proof(Payment $payment)
    {
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path);
    }

    /** Jalur verifikasi yang sah untuk transfer/QRIS manual. */
    public function approve(Payment $payment)
    {
        if ($payment->status !== 'awaiting_verification' || ! $payment->proof_path || ! $payment->needsProof()) {
            return back()->with('error', 'Hanya pembayaran dengan bukti yang menunggu verifikasi yang dapat disetujui.');
        }

        DB::transaction(function () use ($payment) {
            $payment->update(['status' => 'paid', 'paid_at' => now(), 'admin_note' => null]);

            if ($payment->order->status === 'pending') {
                $payment->order->update(['status' => 'processing']);
            }
        });

        return back()->with('success', 'Pembayaran disetujui. Pesanan masuk ke status Diproses.');
    }

    public function reject(Request $request, Payment $payment)
    {
        $data = $request->validate(['admin_note' => ['required', 'string', 'min:3', 'max:300']]);

        if ($payment->status !== 'awaiting_verification') {
            return back()->with('error', 'Pembayaran ini tidak sedang menunggu verifikasi.');
        }

        $payment->update(['status' => 'rejected', 'admin_note' => $data['admin_note']]);

        return back()->with('success', 'Bukti pembayaran ditolak. Pelanggan dapat mengunggah ulang.');
    }
}
