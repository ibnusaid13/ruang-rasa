@extends('layouts.app')
@use('App\Support\Money')
@section('title', 'Pesanan ' . $order->order_number)

@section('content')
@php($payment = $order->payment)
<div class="container" style="max-width:860px">
    <div class="page-title">
        <span class="eyebrow">Pesanan dibuat</span>
        <h1>{{ $order->order_number }}</h1>
        <p class="muted">{{ $order->created_at->format('d M Y, H:i') }}</p>
    </div>

    @if($errors->any()) <div class="alert alert-err">{{ $errors->first() }}</div> @endif

    <div class="panel" style="margin-bottom:1.5rem">
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;margin-bottom:1rem">
            <span class="badge badge-{{ $payment->status }}">{{ $payment->status_label }}</span>
            <span class="badge badge-{{ $order->status }}">Pesanan: {{ $order->status_label }}</span>
        </div>

        @if($payment->status !== 'paid' && $order->status !== 'cancelled')
            <div class="alert alert-info">Pesanan ini belum lunas. Halaman ini bukan bukti pembayaran; status baru berubah menjadi lunas setelah pembayaran diverifikasi.</div>
        @endif

        <h3>Instruksi Pembayaran</h3>
        <p>Jumlah yang harus dibayar: <strong class="price">{{ Money::rp($order->total) }}</strong> · Nomor order: <strong>{{ $order->order_number }}</strong></p>

        @if($payment->method === 'bank_transfer')
            @if($bank['account'])
                <dl class="dl">
                    <dt>Bank</dt><dd>{{ $bank['name'] }}</dd>
                    <dt>No. Rekening</dt><dd><strong>{{ $bank['account'] }}</strong></dd>
                    <dt>Atas Nama</dt><dd>{{ $bank['holder'] }}</dd>
                </dl>
            @else
                <div class="alert alert-warn">Data rekening belum diisi admin. Hubungi toko untuk instruksi pembayaran.</div>
            @endif
            <p class="muted">{{ $bank['note'] }}</p>
        @elseif($payment->method === 'qris')
            @if($qrisImage) <img src="{{ asset('storage/' . $qrisImage) }}" alt="QRIS Ruang Rasa" style="max-width:260px;border-radius:12px">
            @else <div class="alert alert-warn">QRIS belum tersedia. Hubungi toko.</div> @endif
            <p class="muted">Scan, bayar sesuai nominal, lalu unggah bukti di bawah.</p>
        @elseif($payment->method === 'cod')
            <p>Siapkan pembayaran tunai saat pesanan diterima. Status pembayaran menjadi lunas setelah pesanan diselesaikan oleh toko.</p>
        @else
            @if($gatewayUrl) <a class="btn btn-primary" href="{{ $gatewayUrl }}">Bayar via Gateway</a>
            @else <div class="alert alert-warn">Tautan pembayaran gateway belum tersedia.</div> @endif
        @endif

        @if($payment->canUploadProof() && $order->status !== 'cancelled')
            <hr style="border:0;border-top:1px dashed var(--sand);margin:1.2rem 0">
            <h3>Unggah Bukti Pembayaran</h3>
            @if($payment->status === 'rejected')
                <div class="alert alert-err">Bukti sebelumnya ditolak: {{ $payment->admin_note }}. Silakan unggah ulang.</div>
            @elseif($payment->status === 'awaiting_verification')
                <div class="alert alert-info">Bukti terkirim, menunggu verifikasi admin. Anda dapat mengunggah ulang jika salah.</div>
            @endif
            <form method="POST" action="{{ route('orders.proof', $order->uuid) }}" enctype="multipart/form-data" data-loading>
                @csrf
                <div class="field">
                    <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" class="input @error('proof') is-invalid @enderror" required>
                    <div class="hint">JPG/PNG/PDF, maks. 4 MB. Mengunggah bukti tidak otomatis melunasi pesanan.</div>
                    @error('proof')<div class="error">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-dark" type="submit">Kirim Bukti</button>
            </form>
        @endif
    </div>

    <div class="panel" style="margin-bottom:1.5rem">
        <h3>Rincian</h3>
        @foreach($order->items as $it)
            <div class="sum-row"><span>{{ $it->product_name }} × {{ $it->quantity }}</span><span>{{ Money::rp($it->subtotal) }}</span></div>
        @endforeach
        <div class="sum-row"><span>Subtotal</span><span>{{ Money::rp($order->subtotal) }}</span></div>
        <div class="sum-row"><span>Ongkir</span><span>{{ Money::rp($order->shipping_fee) }}</span></div>
        <div class="sum-row sum-total"><span>Total</span><span>{{ Money::rp($order->total) }}</span></div>
        <hr style="border:0;border-top:1px solid var(--cream-2)">
        <dl class="dl">
            <dt>Nama</dt><dd>{{ $order->customer_name }}</dd>
            <dt>WhatsApp</dt><dd>{{ $order->customer_phone }}</dd>
            <dt>Penerimaan</dt><dd>{{ $order->fulfillment_method === 'delivery' ? 'Delivery' : 'Pickup' }}</dd>
            @if($order->shipping_address)<dt>Alamat</dt><dd>{{ $order->shipping_address }}</dd>@endif
            @if($order->notes)<dt>Catatan</dt><dd>{{ $order->notes }}</dd>@endif
        </dl>
    </div>

    <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:4rem">
        <a href="{{ route('orders.receipt', $order->uuid) }}" class="btn btn-primary">Lihat Struk</a>
        <a href="{{ route('home') }}" class="btn btn-outline">Kembali ke Beranda</a>
    </div>
    <p class="muted" style="font-size:.85rem;margin-top:-3rem;margin-bottom:3rem">Simpan tautan halaman ini untuk melihat status pesanan Anda. Siapa pun yang memiliki tautan dapat melihatnya.</p>
</div>
@endsection
