@extends('layouts.admin')
@use('App\Support\Money')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
<div class="stats">
    <div class="stat"><div class="n">{{ $productCount }}</div><div class="l">Total Produk</div></div>
    <div class="stat"><div class="n">{{ $orderCount }}</div><div class="l">Total Pesanan</div></div>
    <div class="stat" style="border-color:var(--warn)"><div class="n">{{ $awaitingPayment }}</div><div class="l">Menunggu Pembayaran</div></div>
    <div class="stat" style="border-color:var(--ok)"><div class="n">{{ $paidCount }}</div><div class="l">Pembayaran Berhasil</div></div>
    <div class="stat" style="border-color:var(--espresso)"><div class="n" style="font-size:1.4rem">{{ Money::rp($totalSales) }}</div><div class="l">Total Penjualan (sudah dibayar)</div></div>
</div>

<div class="panel" style="margin-bottom:1.5rem">
    <h3>Penjualan 7 Hari Terakhir</h3>
    <div class="bars">
        @foreach($chart as $d)
            <div class="bar" title="{{ Money::rp($d['total']) }}">
                <span class="muted">{{ $d['total'] > 0 ? number_format($d['total'] / 1000, 0, ',', '.') . 'k' : '' }}</span>
                <i style="height: {{ round($d['total'] / $chartMax * 100) }}%"></i>
                <span>{{ $d['label'] }}</span>
            </div>
        @endforeach
    </div>
    <p class="muted" style="font-size:.78rem;margin:.6rem 0 0">Hanya menghitung pembayaran berstatus paid.</p>
</div>

<h3>Pesanan Terbaru</h3>
@if($recentOrders->isEmpty())
    <div class="empty panel"><div class="icon">🧾</div><p class="muted">Belum ada pesanan.</p></div>
@else
    <div class="table-wrap"><table>
        <thead><tr><th>No. Pesanan</th><th>Pelanggan</th><th>Total</th><th>Pembayaran</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach($recentOrders as $o)
            <tr>
                <td><strong>{{ $o->order_number }}</strong><div class="muted" style="font-size:.78rem">{{ $o->created_at->format('d M, H:i') }}</div></td>
                <td>{{ $o->customer_name }}</td>
                <td>{{ Money::rp($o->total) }}</td>
                <td><span class="badge badge-{{ $o->payment?->status }}">{{ $o->payment?->status_label ?? 'Belum Bayar' }}</span></td>
                <td><span class="badge badge-{{ $o->status }}">{{ $o->status_label }}</span></td>
                <td><a class="btn btn-outline btn-sm" href="{{ route('admin.orders.show', $o) }}">Detail</a></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
@endif
@endsection
