@extends('layouts.admin')
@use('App\Support\Money')
@section('title', 'Pesanan')
@section('heading', 'Pesanan')

@section('content')
<form method="GET" style="display:flex;gap:.6rem;margin-bottom:1rem;flex-wrap:wrap">
    <input class="input" style="max-width:260px" name="q" placeholder="No. pesanan / nama…" value="{{ request('q') }}">
    <select class="input" style="max-width:200px" name="status">
        <option value="">Semua status</option>
        @foreach(\App\Models\Order::STATUS_LABELS as $k => $l)
            <option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>
        @endforeach
    </select>
    <button class="btn btn-dark btn-sm">Filter</button>
</form>

@if($orders->isEmpty())
    <div class="empty panel"><div class="icon">🧾</div><p class="muted">Tidak ada pesanan.</p></div>
@else
<div class="table-wrap"><table>
    <thead><tr><th>No. Pesanan</th><th>Pelanggan</th><th>Total</th><th>Pembayaran</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach($orders as $o)
        <tr>
            <td><strong>{{ $o->order_number }}</strong><div class="muted" style="font-size:.78rem">{{ $o->created_at->format('d M Y, H:i') }}</div></td>
            <td>{{ $o->customer_name }}<div class="muted" style="font-size:.78rem">{{ $o->customer_phone }}</div></td>
            <td>{{ Money::rp($o->total) }}</td>
            <td><span class="badge badge-{{ $o->payment->status }}">{{ $o->payment->status_label }}</span></td>
            <td><span class="badge badge-{{ $o->status }}">{{ $o->status_label }}</span></td>
            <td><a class="btn btn-outline btn-sm" href="{{ route('admin.orders.show', $o) }}">Detail</a></td>
        </tr>
    @endforeach
    </tbody>
</table></div>
{{ $orders->links() }}
@endif
@endsection
