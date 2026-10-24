<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel Admin') — Ruang Rasa</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ @filemtime(public_path('favicon.svg')) }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/ruangrasa.css') }}?v={{ @filemtime(public_path('css/ruangrasa.css')) }}">
</head>
<body>
@php($unread = \App\Models\ContactMessage::whereNull('read_at')->count())
@php($toVerify = \App\Models\Payment::where('status', 'awaiting_verification')->count())
<div class="admin-shell">
    <aside class="sidebar no-print">
        <a href="{{ route('admin.dashboard') }}" class="brand">Ruang<span>Rasa</span></a>
        <a class="side-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">📊 Dashboard</a>
        <a class="side-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">🍰 Produk</a>
        <a class="side-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">🧾 Pesanan</a>
        <a class="side-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">💳 Pembayaran @if($toVerify)<span class="badge badge-awaiting_verification">{{ $toVerify }}</span>@endif</a>
        <a class="side-link {{ request()->routeIs('admin.messages.*') ? 'active' : '' }}" href="{{ route('admin.messages.index') }}">✉️ Pesan @if($unread)<span class="badge badge-pending">{{ $unread }}</span>@endif</a>
        <a class="side-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}">⚙️ Pengaturan</a>
        <hr style="border:0;border-top:1px solid rgba(255,255,255,.12);margin:1rem 0">
        <a class="side-link" href="{{ route('home') }}">← Ke Website</a>
    </aside>

    <div class="admin-main">
        <div class="alert alert-warn no-print" style="font-size:.85rem">⚠ Panel admin ini <strong>tanpa login</strong> (khusus demo tugas kuliah). Jangan dipublikasikan ke internet.</div>
        <div class="admin-top">
            <div style="display:flex;gap:.8rem;align-items:center">
                <button class="btn btn-outline btn-sm side-toggle no-print" type="button">☰ Menu</button>
                <h1>@yield('heading')</h1>
            </div>
            @yield('actions')
        </div>

        @if(session('success')) <div class="alert alert-ok">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="alert alert-err">{{ session('error') }}</div> @endif
        @if($errors->any()) <div class="alert alert-err">Periksa kembali isian formulir.</div> @endif

        @yield('content')
    </div>
</div>
<script src="{{ asset('js/ruangrasa.js') }}?v={{ @filemtime(public_path('js/ruangrasa.js')) }}"></script>
</body>
</html>
