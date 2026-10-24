@inject('cartSvc', 'App\Services\CartService')
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ruang Rasa — Dessert UMKM') </title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ @filemtime(public_path('favicon.svg')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/ruangrasa.css') }}?v={{ @filemtime(public_path('css/ruangrasa.css')) }}">
</head>
<body>
<header class="nav no-print">
    <div class="container nav-inner">
        <a href="{{ route('home') }}" class="brand">Ruang<span>Rasa</span></a>
        <button class="nav-toggle" aria-label="Menu">☰</button>
        <nav class="nav-links">
            <a href="{{ route('home') }}">Beranda</a>
            <a href="{{ route('products.index') }}">Produk</a>
            <a href="{{ route('home') }}#kontak">Kontak</a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline btn-sm">🛠 Panel Admin</a>
            <a href="{{ route('cart.index') }}" class="cart-link" aria-label="Keranjang">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                <span class="cart-badge" data-count="{{ $cartSvc->count() }}">{{ $cartSvc->count() }}</span>
            </a>
        </nav>
    </div>
</header>

<main>@yield('content')</main>

<footer class="footer no-print">
    <div class="container footer-grid">
        <div>
            <div class="brand" style="color:var(--cream)">Ruang<span>Rasa</span></div>
            <p>Dessert UMKM dibuat dengan sepenuh rasa.</p>
            @if(\App\Models\Setting::get('shop_address', config('shop.contact.address')))
                <p>📍 {{ \App\Models\Setting::get('shop_address', config('shop.contact.address')) }}</p>
            @endif
        </div>
        <div>
            <strong>Jelajahi</strong><br>
            <a href="{{ route('products.index') }}">Semua Produk</a><br>
            <a href="{{ route('cart.index') }}">Keranjang</a>
        </div>
        <div>
            <strong>Kontak</strong><br>
            @if($p = \App\Models\Setting::get('shop_phone', config('shop.contact.phone')))
                <span>📞 {{ $p }}</span><br>
            @endif
            <a href="{{ route('home') }}#kontak">Kirim pesan</a>
        </div>
        @php($grp = config('shop.group'))
        <small style="grid-column:1/-1" id="group-info">
            <strong>Kelompok {{ $grp['number'] !== '' ? $grp['number'] : '—' }}</strong>
            · Anggota: {{ count($grp['members']) ? implode(', ', $grp['members']) : '(isi SHOP_GROUP_MEMBERS di .env)' }}
            <br>© {{ date('Y') }} Ruang Rasa. Website demo tugas kuliah.
        </small>
    </div>
</footer>

@if(session('success')) <div data-toast="ok" data-message="{{ session('success') }}" hidden></div> @endif
@if(session('error')) <div data-toast="error" data-message="{{ session('error') }}" hidden></div> @endif

<script src="{{ asset('js/ruangrasa.js') }}?v={{ @filemtime(public_path('js/ruangrasa.js')) }}"></script>
@stack('scripts')
</body>
</html>
