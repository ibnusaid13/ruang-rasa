@extends('layouts.admin')
@use('App\Support\Money')
@section('title', 'Produk')
@section('heading', 'Produk')
@section('actions')<a href="{{ route('admin.products.create') }}" class="btn btn-primary">+ Tambah Produk</a>@endsection

@section('content')
<form method="GET" style="display:flex;gap:.6rem;margin-bottom:1rem">
    <input class="input" style="max-width:320px" name="q" placeholder="Cari produk…" value="{{ request('q') }}">
    <button class="btn btn-dark btn-sm">Cari</button>
</form>

@if($products->isEmpty())
    <div class="empty panel"><div class="icon">🍰</div><p class="muted">Belum ada produk.</p><a href="{{ route('admin.products.create') }}" class="btn btn-primary">Tambah Produk Pertama</a></div>
@else
<div class="table-wrap"><table>
    <thead><tr><th></th><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th><th>Best Seller</th><th>Aksi</th></tr></thead>
    <tbody>
    @foreach($products as $p)
        <tr>
            <td>@if($p->image_url)<img class="thumb-sm" src="{{ $p->image_url }}" alt="">@else<div class="thumb-sm"></div>@endif</td>
            <td><strong>{{ $p->name }}</strong></td>
            <td>{{ $p->category?->name ?? '—' }}</td>
            <td>{{ Money::rp($p->price) }}</td>
            <td>{{ $p->stock }}</td>
            <td><span class="badge {{ $p->is_active ? 'badge-paid' : 'badge-cancelled' }}">{{ $p->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
            <td>
                <form method="POST" action="{{ route('admin.products.best-seller', $p) }}">@csrf
                    <button class="btn btn-sm {{ $p->is_best_seller ? 'btn-primary' : 'btn-outline' }}">{{ $p->is_best_seller ? '★ Ya' : '☆ Tidak' }}</button></form>
            </td>
            <td><div class="actions">
                <a class="btn btn-outline btn-sm" href="{{ route('admin.products.edit', $p) }}">Edit</a>
                <form method="POST" action="{{ route('admin.products.destroy', $p) }}" data-confirm="Hapus produk &quot;{{ $p->name }}&quot;? Tindakan ini tidak bisa dibatalkan.">@csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm">Hapus</button></form>
            </div></td>
        </tr>
    @endforeach
    </tbody>
</table></div>
{{ $products->links() }}
@endif
@endsection
