@extends('layouts.app')
@section('title', 'Ruang Rasa — Dessert UMKM')

@section('content')
<section class="hero">
    <div class="container hero-grid">
        <div class="reveal">
            <span class="eyebrow">Dessert UMKM · Dibuat Segar</span>
            <h1>Manis yang <em>menenangkan</em>, untuk setiap cerita.</h1>
            <p>Cake, pudding, dan cookies buatan tangan dengan bahan pilihan. Pesan hari ini, nikmati dengan sepenuh rasa.</p>
            <div class="hero-actions">
                <a href="{{ route('products.index') }}" class="btn btn-primary">Belanja Sekarang</a>
                <a href="#best-seller" class="btn btn-outline">Lihat Best Seller</a>
            </div>
        </div>
        @php
            // Slide: produk Best Seller yang punya foto (maks. 4). Jika belum ada, pakai produk terbaru.
            $slides = $bestSellers->filter(fn ($p) => $p->image_url)->values();
            if ($slides->isEmpty()) {
                $slides = $latest->filter(fn ($p) => $p->image_url)->take(4)->values();
            }
        @endphp
        <div class="hero-visual reveal {{ $slides->isEmpty() ? 'is-empty' : '' }}">
            @if($slides->isNotEmpty())
                <div class="hero-slider" data-slider data-interval="4500" aria-roledescription="carousel" aria-label="Produk unggulan">
                    @foreach($slides as $i => $p)
                        <a href="{{ route('products.show', $p->slug) }}" class="hero-slide {{ $i === 0 ? 'is-active' : '' }}" aria-label="{{ $p->name }}">
                            <img src="{{ $p->image_url }}" alt="{{ $p->name }}" @if($i > 0) loading="lazy" @endif>
                        </a>
                    @endforeach
                    @if($slides->count() > 1)
                        <div class="hero-dots">
                            @foreach($slides as $i => $p)
                                <button type="button" class="hero-dot {{ $i === 0 ? 'is-active' : '' }}" aria-label="Tampilkan {{ $p->name }}"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="ph" style="font-size:6rem;color:rgba(255,255,255,.7)">RR</div>
            @endif
        </div>
    </div>
</section>

<section class="section" id="best-seller">
    <div class="container">
        <div class="section-head reveal"><span class="eyebrow">Favorit pelanggan</span><h2>Best Seller</h2></div>
        @if($bestSellers->isEmpty())
            <div class="empty"><div class="icon">🍰</div><p class="muted">Belum ada produk Best Seller.</p></div>
        @else
            <div class="grid-products">@foreach($bestSellers as $product) @include('partials.product-card') @endforeach</div>
        @endif
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head reveal"><span class="eyebrow">Kategori</span><h2>Pilih sesuai selera</h2></div>
        <div class="grid-products">
            @foreach($categories as $cat)
                <a href="{{ route('products.index', ['category' => $cat->slug]) }}" class="panel reveal" style="text-align:center">
                    <h3>{{ $cat->name }}</h3>
                    <span class="muted">{{ $cat->products_count }} produk</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head reveal"><span class="eyebrow">Baru dari dapur</span><h2>Produk Terbaru</h2></div>
        <div class="grid-products">@foreach($latest as $product) @include('partials.product-card') @endforeach</div>
    </div>
</section>

<section class="section section-alt" id="kontak">
    <div class="container" style="max-width:640px">
        <div class="section-head center reveal"><span class="eyebrow">Hubungi kami</span><h2>Ada pertanyaan?</h2></div>
        @if($errors->any()) <div class="alert alert-err">Periksa kembali formulir Anda.</div> @endif
        <form method="POST" action="{{ route('contact.store') }}" class="panel reveal" data-loading>
            @csrf
            <div class="field"><label for="c-name">Nama</label>
                <input id="c-name" class="input @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                @error('name')<div class="error">{{ $message }}</div>@enderror</div>
            <div class="field"><label for="c-email">Email</label>
                <input id="c-email" type="email" class="input @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required>
                @error('email')<div class="error">{{ $message }}</div>@enderror</div>
            <div class="field"><label for="c-phone">No. WhatsApp (opsional)</label>
                <input id="c-phone" class="input" name="phone" value="{{ old('phone') }}">
                @error('phone')<div class="error">{{ $message }}</div>@enderror</div>
            <div class="field"><label for="c-msg">Pesan</label>
                <textarea id="c-msg" rows="4" class="input @error('message') is-invalid @enderror" name="message" required>{{ old('message') }}</textarea>
                @error('message')<div class="error">{{ $message }}</div>@enderror</div>
            <button class="btn btn-dark" type="submit">Kirim Pesan</button>
        </form>
    </div>
</section>
@endsection
