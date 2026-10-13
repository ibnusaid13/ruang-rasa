@extends('layouts.app')
@use('App\Support\Money')
@section('title', 'Checkout — Ruang Rasa')

@section('content')
<div class="container">
    <div class="page-title"><span class="eyebrow">Langkah terakhir</span><h1>Checkout</h1></div>

    @if($errors->any()) <div class="alert alert-err">Periksa kembali data yang diisi.</div> @endif

    <form method="POST" action="{{ route('checkout.store') }}" class="layout-2" data-loading>
        @csrf
        <div>
            <div class="panel" style="margin-bottom:1.5rem">
                <h3>Data Pelanggan</h3>
                <div class="field"><label for="customer_name">Nama lengkap *</label>
                    <input id="customer_name" name="customer_name" class="input @error('customer_name') is-invalid @enderror" value="{{ old('customer_name') }}" required>
                    @error('customer_name')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label for="customer_phone">Nomor WhatsApp *</label>
                    <input id="customer_phone" name="customer_phone" class="input @error('customer_phone') is-invalid @enderror" placeholder="081234567890" value="{{ old('customer_phone') }}" required>
                    @error('customer_phone')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label for="customer_email">Email (opsional)</label>
                    <input id="customer_email" type="email" name="customer_email" class="input @error('customer_email') is-invalid @enderror" value="{{ old('customer_email') }}">
                    @error('customer_email')<div class="error">{{ $message }}</div>@enderror</div>
            </div>

            <div class="panel" style="margin-bottom:1.5rem">
                <h3>Penerimaan Pesanan</h3>
                <label class="radio-card"><input type="radio" name="fulfillment_method" value="pickup" @checked(old('fulfillment_method', 'pickup') === 'pickup')>
                    <span><strong>Ambil sendiri (Pickup)</strong><span class="muted">Tanpa biaya kirim</span></span></label>
                <label class="radio-card"><input type="radio" name="fulfillment_method" value="delivery" @checked(old('fulfillment_method') === 'delivery')>
                    <span><strong>Diantar (Delivery)</strong><span class="muted">Tarif demo {{ $deliveryFeeLabel }}</span></span></label>
                @error('fulfillment_method')<div class="error">{{ $message }}</div>@enderror
                <div class="field" id="address-field" style="margin-top:1rem">
                    <label for="shipping_address">Alamat pengiriman *</label>
                    <textarea id="shipping_address" name="shipping_address" rows="3" class="input @error('shipping_address') is-invalid @enderror">{{ old('shipping_address') }}</textarea>
                    @error('shipping_address')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="field"><label for="notes">Catatan pesanan (opsional)</label>
                    <textarea id="notes" name="notes" rows="2" class="input">{{ old('notes') }}</textarea>
                    @error('notes')<div class="error">{{ $message }}</div>@enderror</div>
            </div>

            <div class="panel">
                <h3>Metode Pembayaran</h3>
                @forelse($methods as $key => $m)
                    <label class="radio-card"><input type="radio" name="payment_method" value="{{ $key }}" @checked(old('payment_method', array_key_first($methods)) === $key) required>
                        <span><strong>{{ $m['label'] }}</strong>
                        <span class="muted">@switch($key)
                            @case('bank_transfer') Transfer manual, lalu unggah bukti. Diverifikasi admin. @break
                            @case('qris') Scan QRIS resmi toko, lalu unggah bukti. Diverifikasi admin. @break
                            @case('cod') Bayar tunai saat pesanan diterima. @break
                            @default Bayar melalui halaman payment gateway. @endswitch</span></span></label>
                @empty
                    <div class="alert alert-warn">Belum ada metode pembayaran aktif. Hubungi admin toko.</div>
                @endforelse
                @error('payment_method')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>

        <aside class="panel sticky">
            <h3>Ringkasan Pesanan</h3>
            @foreach($cart['lines'] as $line)
                <div class="sum-row"><span>{{ $line['product']->name }} × {{ $line['item']->quantity }}</span><span>{{ Money::rp($line['line_total']) }}</span></div>
            @endforeach
            <div class="sum-row" style="margin-top:.6rem"><span>Subtotal</span><strong>{{ Money::rp($cart['subtotal']) }}</strong></div>
            <div class="sum-row" id="ship-row"><span>Ongkir (demo)</span><span>{{ $deliveryFeeLabel }}</span></div>
            <div class="sum-total sum-row"><span>Total</span>
                <span id="grand-total" data-subtotal="{{ (int) round((float) $cart['subtotal']) }}" data-fee="{{ $deliveryFee }}">{{ Money::rp($cart['subtotal']) }}</span></div>
            <p class="muted" style="font-size:.78rem">Total final dihitung ulang server saat pesanan dibuat.</p>
            <button type="submit" class="btn btn-primary" style="width:100%" @disabled(empty($methods))>Buat Pesanan</button>
            <a href="{{ route('cart.index') }}" class="btn btn-outline" style="width:100%;margin-top:.6rem">Kembali ke Keranjang</a>
        </aside>
    </form>
</div>
@endsection
