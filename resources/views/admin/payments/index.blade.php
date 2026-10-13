@extends('layouts.admin')
@use('App\Support\Money')
@section('title', 'Pembayaran')
@section('heading', 'Pembayaran')

@section('content')
<form method="GET" style="display:flex;gap:.6rem;margin-bottom:1rem">
    <select class="input" style="max-width:240px" name="status">
        <option value="">Semua status</option>
        @foreach(\App\Models\Payment::STATUS_LABELS as $k => $l)
            <option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>
        @endforeach
    </select>
    <button class="btn btn-dark btn-sm">Filter</button>
</form>

@if($payments->isEmpty())
    <div class="empty panel"><div class="icon">💳</div><p class="muted">Belum ada pembayaran.</p></div>
@else
<div class="table-wrap"><table>
    <thead><tr><th>Pesanan</th><th>Metode</th><th>Jumlah</th><th>Status</th><th>Bukti</th><th></th></tr></thead>
    <tbody>
    @foreach($payments as $p)
        <tr>
            <td><strong>{{ $p->order->order_number }}</strong><div class="muted" style="font-size:.78rem">{{ $p->order->customer_name }}</div></td>
            <td>{{ $p->method_label }}</td>
            <td>{{ Money::rp($p->amount) }}</td>
            <td><span class="badge badge-{{ $p->status }}">{{ $p->status_label }}</span></td>
            <td>{{ $p->proof_path ? 'Ada' : '—' }}</td>
            <td><a class="btn btn-outline btn-sm" href="{{ route('admin.payments.show', $p) }}">Detail</a></td>
        </tr>
    @endforeach
    </tbody>
</table></div>
{{ $payments->links() }}
@endif
@endsection
