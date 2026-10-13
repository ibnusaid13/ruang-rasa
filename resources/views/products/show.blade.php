@extends('layouts.app')
@use('App\Support\Money')
@section('title', $product->name . ' — Ruang Rasa')

@section('content')
<div class="container">
    <div class="detail-grid">
        <div class="detail-img reveal">
            @if($product->image_url) <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
            @else <div class="ph" style="font-size:7rem">{{ mb_substr($product->name, 0, 1) }}</div> @endif
        </div>
        <div class="reveal">
            <span class="eyebrow">{{ $product->category?->name ?? 'Dessert' }}</span>
            <h1>{{ $product->name }}</h1>
            @if($product->is_best_seller) <span class="badge badge-ready">★ Best Seller</span> @endif
            <p class="price" style="font-size:1.8rem;margin:.6rem 0">{{ Money::rp($product->price) }}</p>
            <p class="muted">{{ $product->description }}</p>
            <p>
                @if($product->stock < 1) <span class="badge badge-cancelled">Stok habis</span>
                @elseif($product->stock <= 5) <span class="badge badge-pending">Sisa {{ $product->stock }}</span>
                @else <span class="badge badge-paid">Tersedia ({{ $product->stock }})</span> @endif
            </p>
            <form class="js-add-cart" method="POST" action="{{ route('cart.add') }}" data-cart-url="{{ route('cart.index') }}" style="display:flex;gap:.8rem;align-items:center;margin-top:1rem">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input class="input" style="width:90px" type="number" name="quantity" value="1" min="1" max="{{ max(1, $product->stock) }}">
                <button type="submit" class="btn btn-primary" @disabled($product->stock < 1)>Tambah ke Keranjang</button>
            </form>
        </div>
    </div>

    @if($related->isNotEmpty())
        <div class="section-head"><h2>Mungkin Anda suka</h2></div>
        <div class="grid-products" style="padding-bottom:3rem">@foreach($related as $product) @include('partials.product-card') @endforeach</div>
    @endif
</div>
@endsection
