@extends('layouts.app')
@use('App\Support\Money')
@section('title', 'Struk ' . $order->order_number)

@section('content')
@php($payment = $order->payment)
@php($addr = \App\Models\Setting::get('shop_address', config('shop.contact.address')))
@php($phone = \App\Models\Setting::get('shop_phone', config('shop.contact.phone')))
<div class="receipt-wrap">
    <div class="receipt">
        <div class="receipt-head">
            <div class="brand">Ruang<span>Rasa</span></div>
            @if($addr)<div class="muted">{{ $addr }}</div>@endif
            @if($phone)<div class="muted">Telp/WA: {{ $phone }}</div>@endif
        </div>

        <dl class="dl" style="grid-template-columns:140px 1fr">
            <dt>No. Pesanan</dt><dd><strong>{{ $order->order_number }}</strong></dd>
            <dt>Tanggal</dt><dd>{{ $order->created_at->format('d M Y, H:i') }}</dd>
            <dt>Pelanggan</dt><dd>{{ $order->customer_name }}</dd>
            <dt>Penerimaan</dt><dd>{{ $order->fulfillment_method === 'delivery' ? 'Delivery' : 'Pickup' }}</dd>
        </dl>

        <table style="margin-top:1rem">
            <thead><tr><th>Produk</th><th class="r">Harga</th><th class="r">Qty</th><th class="r">Subtotal</th></tr></thead>
            <tbody>
            @foreach($order->items as $it)
                <tr><td>{{ $it->product_name }}</td><td class="r">{{ Money::rp($it->unit_price) }}</td><td class="r">{{ $it->quantity }}</td><td class="r">{{ Money::rp($it->subtotal) }}</td></tr>
            @endforeach
            </tbody>
        </table>

        <div style="margin-top:1rem;margin-left:auto;max-width:300px">
            <div class="sum-row"><span>Subtotal</span><span>{{ Money::rp($order->subtotal) }}</span></div>
            @if((float) $order->shipping_fee > 0)<div class="sum-row"><span>Ongkir</span><span>{{ Money::rp($order->shipping_fee) }}</span></div>@endif
            <div class="sum-row sum-total"><span>Total</span><span>{{ Money::rp($order->total) }}</span></div>
        </div>

        <dl class="dl" style="grid-template-columns:160px 1fr;margin-top:1.2rem">
            <dt>Metode Pembayaran</dt><dd>{{ $payment->method_label }}</dd>
            <dt>Status Pembayaran</dt><dd><span class="badge badge-{{ $payment->status }}">{{ $payment->status_label }}</span></dd>
            <dt>Status Pesanan</dt><dd><span class="badge badge-{{ $order->status }}">{{ $order->status_label }}</span></dd>
            @if($payment->paid_at)<dt>Dibayar pada</dt><dd>{{ $payment->paid_at->format('d M Y, H:i') }}</dd>@endif
        </dl>

        <p class="center" style="margin-top:1.6rem"><strong>Terima kasih telah berbelanja di Ruang Rasa! 🤎</strong></p>
    </div>

    <div class="receipt-actions no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()">Cetak Struk</button>
        <a href="{{ route('orders.show', $order->uuid) }}" class="btn btn-outline">Lihat Detail Pesanan</a>
        <a href="{{ route('home') }}" class="btn btn-outline">Kembali ke Beranda</a>
    </div>
</div>
@endsection
