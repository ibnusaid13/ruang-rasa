@extends('layouts.app')
@section('title', 'Katalog Produk — Ruang Rasa')

@section('content')
<div class="container">
    <div class="page-title"><span class="eyebrow">Katalog</span><h1>Semua Produk</h1></div>

    <form method="GET" class="panel" style="display:flex;gap:.8rem;flex-wrap:wrap;margin-bottom:2rem">
        <input class="input" style="flex:2;min-width:180px" name="q" placeholder="Cari produk…" value="{{ request('q') }}">
        <select class="input" style="flex:1;min-width:140px" name="category">
            <option value="">Semua kategori</option>
            @foreach($categories as $c)
                <option value="{{ $c->slug }}" @selected(request('category') === $c->slug)>{{ $c->name }}</option>
            @endforeach
        </select>
        <select class="input" style="flex:1;min-width:140px" name="sort">
            <option value="">Terbaru</option>
            <option value="best" @selected(request('sort') === 'best')>Best Seller</option>
            <option value="price_asc" @selected(request('sort') === 'price_asc')>Harga terendah</option>
            <option value="price_desc" @selected(request('sort') === 'price_desc')>Harga tertinggi</option>
        </select>
        <button class="btn btn-dark" type="submit">Terapkan</button>
    </form>

    @if($products->isEmpty())
        <div class="empty"><div class="icon">🔍</div><h3>Produk tidak ditemukan</h3><p class="muted">Coba kata kunci atau kategori lain.</p>
            <a class="btn btn-outline" href="{{ route('products.index') }}">Reset filter</a></div>
    @else
        <div class="grid-products">@foreach($products as $product) @include('partials.product-card') @endforeach</div>
        {{ $products->links() }}
    @endif
</div>
@endsection
