@extends('layouts.app')
@use('App\Support\Money')
@section('title', 'Keranjang — Ruang Rasa')

@section('content')
<div class="container">
    <div class="page-title"><span class="eyebrow">Belanja</span><h1>Keranjang Anda</h1></div>

    @if($cart['is_empty'])
        <div class="empty panel">
            <div class="icon">🛒</div>
            <h3>Keranjang masih kosong</h3>
            <p class="muted">Yuk pilih dessert favoritmu dulu.</p>
            <a href="{{ route('products.index') }}" class="btn btn-primary">Mulai Belanja</a>
        </div>
    @else
        <div class="layout-2">
            <div class="panel">
                @foreach($cart['lines'] as $line)
                    @php($item = $line['item']) @php($product = $line['product'])
                    <div class="cart-row">
                        <div class="cart-thumb">
                            @if($product?->image_url) <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                            @else <div class="ph" style="font-size:1.8rem">{{ $product ? mb_substr($product->name, 0, 1) : '?' }}</div> @endif
                        </div>
                        <div>
                            <strong>{{ $product?->name ?? 'Produk dihapus' }}</strong>
                            @if($product)
                                <div class="muted">{{ Money::rp($product->price) }} / item · Stok {{ $product->stock }}
                                    @if($product->is_active && $product->stock > 0) <span class="badge badge-paid">Tersedia</span> @endif</div>
                            @endif
                            @if($line['issue']) <div class="cart-issue">⚠ {{ $line['issue'] }}</div> @endif
                        </div>
                        <div style="text-align:right">
                            <div class="qty">
                                <form method="POST" action="{{ route('cart.update', $item) }}">@csrf @method('PATCH')
                                    <input type="hidden" name="quantity" value="{{ max(1, $item->quantity - 1) }}">
                                    <button aria-label="Kurangi" @disabled($item->quantity <= 1)>−</button></form>
                                <span>{{ $item->quantity }}</span>
                                <form method="POST" action="{{ route('cart.update', $item) }}">@csrf @method('PATCH')
                                    <input type="hidden" name="quantity" value="{{ $item->quantity + 1 }}">
                                    <button aria-label="Tambah">+</button></form>
                            </div>
                            <div class="price" style="margin:.4rem 0">{{ Money::rp($line['line_total']) }}</div>
                            <form method="POST" action="{{ route('cart.destroy', $item) }}" data-confirm="Hapus produk ini dari keranjang?">@csrf @method('DELETE')
                                <button class="btn btn-outline btn-sm">Hapus</button></form>
                        </div>
                    </div>
                @endforeach
            </div>

            <aside class="panel sticky">
                <h3>Ringkasan</h3>
                <div class="sum-row"><span>Subtotal</span><strong>{{ Money::rp($cart['subtotal']) }}</strong></div>
                <div class="sum-row muted"><span>Ongkir</span><span>dihitung saat checkout</span></div>
                <div class="sum-total sum-row"><span>Total sementara</span><span>{{ Money::rp($cart['subtotal']) }}</span></div>
                <p class="muted" style="font-size:.8rem">Harga final dihitung ulang oleh server saat checkout.</p>
                @if($cart['has_issues'])
                    <div class="alert alert-err">Perbaiki produk bermasalah sebelum checkout.</div>
                    <button class="btn btn-primary" style="width:100%" disabled>Lanjut Checkout</button>
                @else
                    <a href="{{ route('checkout.show') }}" class="btn btn-primary" style="width:100%">Lanjut Checkout</a>
                @endif
                <a href="{{ route('products.index') }}" class="btn btn-outline" style="width:100%;margin-top:.6rem">Lanjut Belanja</a>
                <p class="muted" style="font-size:.78rem;margin-top:1rem">Keranjang tersimpan di perangkat/browser ini dan tidak otomatis tersinkron antarperangkat.</p>
            </aside>
        </div>
    @endif
</div>
@endsection
