@extends('layouts.admin')
@use('App\Support\Money')
@section('title', $order->order_number)
@section('heading', 'Pesanan ' . $order->order_number)
@section('actions')
    <div class="actions no-print">
        <a href="{{ route('orders.receipt', $order->uuid) }}" target="_blank" class="btn btn-dark btn-sm">🖨 Cetak Struk</a>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline btn-sm">← Kembali</a>
    </div>
@endsection

@section('content')
@php($payment = $order->payment)
@php($next = $order->allowedNextStatuses())
<div class="layout-2" style="padding-bottom:1rem">
    <div>
        <div class="panel" style="margin-bottom:1.5rem">
            <h3>Item Pesanan</h3>
            <div class="table-wrap" style="box-shadow:none"><table>
                <thead><tr><th>Produk</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr></thead>
                <tbody>
                @foreach($order->items as $it)
                    <tr><td>{{ $it->product_name }}</td><td>{{ Money::rp($it->unit_price) }}</td><td>{{ $it->quantity }}</td><td>{{ Money::rp($it->subtotal) }}</td></tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="sum-row" style="margin-top:.8rem"><span>Subtotal</span><span>{{ Money::rp($order->subtotal) }}</span></div>
            <div class="sum-row"><span>Ongkir</span><span>{{ Money::rp($order->shipping_fee) }}</span></div>
            <div class="sum-row sum-total"><span>Total</span><span>{{ Money::rp($order->total) }}</span></div>
        </div>

        <div class="panel">
            <h3>Pelanggan</h3>
            <dl class="dl">
                <dt>Nama</dt><dd>{{ $order->customer_name }}</dd>
                <dt>WhatsApp</dt><dd>{{ $order->customer_phone }}</dd>
                <dt>Email</dt><dd>{{ $order->customer_email ?: '—' }}</dd>
                <dt>Penerimaan</dt><dd>{{ $order->fulfillment_method === 'delivery' ? 'Delivery' : 'Pickup' }}</dd>
                <dt>Alamat</dt><dd>{{ $order->shipping_address ?: '—' }}</dd>
                <dt>Catatan</dt><dd>{{ $order->notes ?: '—' }}</dd>
            </dl>
        </div>
    </div>

    <aside>
        <div class="panel" style="margin-bottom:1.5rem">
            <h3>Status Pesanan</h3>
            <p><span class="badge badge-{{ $order->status }}">{{ $order->status_label }}</span></p>
            @if($next)
                @foreach($next as $s)
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}" style="margin-bottom:.5rem" data-loading
                          @if($s === 'cancelled') data-confirm="Batalkan pesanan ini? Stok akan dikembalikan." @endif>
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $s }}">
                        <button class="btn {{ $s === 'cancelled' ? 'btn-danger' : 'btn-primary' }} btn-sm" style="width:100%">→ {{ \App\Models\Order::STATUS_LABELS[$s] }}</button>
                    </form>
                @endforeach
            @else
                <p class="muted">Tidak ada perubahan status yang tersedia.</p>
            @endif
            @if($order->status === 'pending' && $payment->method !== 'cod' && $payment->status !== 'paid')
                <p class="muted" style="font-size:.8rem">Pesanan non-COD baru bisa diproses setelah pembayaran diverifikasi.</p>
            @endif
        </div>

        <div class="panel">
            <h3>Pembayaran</h3>
            <dl class="dl" style="grid-template-columns:100px 1fr">
                <dt>Metode</dt><dd>{{ $payment->method_label }}</dd>
                <dt>Jumlah</dt><dd>{{ Money::rp($payment->amount) }}</dd>
                <dt>Status</dt><dd><span class="badge badge-{{ $payment->status }}">{{ $payment->status_label }}</span></dd>
            </dl>
            <a class="btn btn-outline btn-sm" style="margin-top:.8rem" href="{{ route('admin.payments.show', $payment) }}">Detail / Verifikasi</a>
        </div>
    </aside>
</div>
@endsection
