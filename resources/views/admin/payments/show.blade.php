@extends('layouts.admin')
@use('App\Support\Money')
@section('title', 'Detail Pembayaran')
@section('heading', 'Pembayaran ' . $payment->order->order_number)
@section('actions')<a href="{{ route('admin.payments.index') }}" class="btn btn-outline btn-sm">← Kembali</a>@endsection

@section('content')
<div class="layout-2" style="padding-bottom:1rem">
    <div class="panel">
        <h3>Detail</h3>
        <dl class="dl">
            <dt>Pesanan</dt><dd><a href="{{ route('admin.orders.show', $payment->order) }}" style="text-decoration:underline">{{ $payment->order->order_number }}</a></dd>
            <dt>Pelanggan</dt><dd>{{ $payment->order->customer_name }} ({{ $payment->order->customer_phone }})</dd>
            <dt>Metode</dt><dd>{{ $payment->method_label }}</dd>
            <dt>Jumlah</dt><dd><strong>{{ Money::rp($payment->amount) }}</strong></dd>
            <dt>Status</dt><dd><span class="badge badge-{{ $payment->status }}">{{ $payment->status_label }}</span></dd>
            <dt>Dibayar pada</dt><dd>{{ $payment->paid_at?->format('d M Y, H:i') ?: '—' }}</dd>
            @if($payment->admin_note)<dt>Catatan admin</dt><dd>{{ $payment->admin_note }}</dd>@endif
        </dl>
    </div>

    <div class="panel">
        <h3>Bukti Pembayaran</h3>
        @if($payment->proof_path)
            @php($isPdf = str_ends_with(strtolower($payment->proof_path), '.pdf'))
            @if($isPdf)
                <a class="btn btn-outline btn-sm" target="_blank" href="{{ route('admin.payments.proof', $payment) }}">Buka PDF</a>
            @else
                <a target="_blank" href="{{ route('admin.payments.proof', $payment) }}"><img src="{{ route('admin.payments.proof', $payment) }}" alt="Bukti" style="border-radius:12px;max-height:340px"></a>
            @endif
            <div class="alert alert-info" style="margin-top:1rem;font-size:.85rem">Periksa nominal & tujuan transfer di mutasi rekening/QRIS toko sebelum menyetujui. Bukti unggahan bukan jaminan pembayaran.</div>
        @else
            <p class="muted">{{ $payment->needsProof() ? 'Pelanggan belum mengunggah bukti.' : 'Metode ini tidak memakai bukti unggahan.' }}</p>
        @endif

        @if($payment->status === 'awaiting_verification')
            <form method="POST" action="{{ route('admin.payments.approve', $payment) }}" data-loading
                  data-confirm="Setujui pembayaran ini? Pesanan akan masuk ke status Diproses.">
                @csrf <button class="btn btn-primary" style="width:100%;margin-bottom:.8rem">✓ Setujui Pembayaran</button>
            </form>
            <form method="POST" action="{{ route('admin.payments.reject', $payment) }}" data-loading data-confirm="Tolak bukti pembayaran ini?">
                @csrf
                <div class="field"><label for="admin_note">Alasan penolakan *</label>
                    <input id="admin_note" name="admin_note" class="input @error('admin_note') is-invalid @enderror" required minlength="3" maxlength="300" value="{{ old('admin_note') }}">
                    @error('admin_note')<div class="error">{{ $message }}</div>@enderror</div>
                <button class="btn btn-danger" style="width:100%">✕ Tolak</button>
            </form>
        @elseif($payment->method === 'cod' && $payment->status === 'pending')
            <div class="alert alert-info">Pembayaran COD otomatis lunas saat pesanan diubah ke “Selesai”.</div>
        @endif
    </div>
</div>
@endsection
