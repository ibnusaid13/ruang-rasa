@extends('layouts.admin')
@section('title', 'Pengaturan')
@section('heading', 'Pengaturan Toko')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="panel" style="max-width:720px" data-loading>
    @csrf @method('PUT')

    <h3>Info Usaha (tampil di struk)</h3>
    <div class="field"><label>Alamat</label><input class="input" name="shop_address" value="{{ old('shop_address', $values['shop_address']) }}">@error('shop_address')<div class="error">{{ $message }}</div>@enderror</div>
    <div class="field"><label>Telepon / WhatsApp</label><input class="input" name="shop_phone" value="{{ old('shop_phone', $values['shop_phone']) }}">@error('shop_phone')<div class="error">{{ $message }}</div>@enderror</div>

    <h3 style="margin-top:1.5rem">Pengiriman</h3>
    <div class="field"><label>Tarif delivery demo (Rp) *</label>
        <input class="input" type="number" min="0" name="delivery_fee" value="{{ old('delivery_fee', $deliveryFee) }}" required>
        <div class="hint">Tarif demo yang bisa diubah. Belum terhubung ke layanan ekspedisi.</div>
        @error('delivery_fee')<div class="error">{{ $message }}</div>@enderror</div>

    <h3 style="margin-top:1.5rem">Transfer Bank Manual</h3>
    <div class="field"><label>Nama bank</label><input class="input" name="bank_name" value="{{ old('bank_name', $values['bank_name']) }}">@error('bank_name')<div class="error">{{ $message }}</div>@enderror</div>
    <div class="field"><label>Nomor rekening</label><input class="input" name="bank_account" value="{{ old('bank_account', $values['bank_account']) }}">@error('bank_account')<div class="error">{{ $message }}</div>@enderror</div>
    <div class="field"><label>Atas nama</label><input class="input" name="bank_holder" value="{{ old('bank_holder', $values['bank_holder']) }}">@error('bank_holder')<div class="error">{{ $message }}</div>@enderror</div>
    <div class="field"><label>Catatan instruksi</label><textarea class="input" rows="2" name="transfer_note">{{ old('transfer_note', $values['transfer_note']) }}</textarea>@error('transfer_note')<div class="error">{{ $message }}</div>@enderror</div>
    <div class="hint" style="margin-bottom:1rem">Isi dengan data rekening asli milik pemilik usaha.</div>

    <h3 style="margin-top:1.5rem">QRIS Manual</h3>
    @if($qrisImage)
        <img src="{{ asset('storage/' . $qrisImage) }}" alt="QRIS" style="width:160px;border-radius:12px;margin-bottom:.5rem">
        <label class="check" style="margin-bottom:.5rem"><input type="checkbox" name="remove_qris" value="1"> Hapus QRIS</label>
    @endif
    <div class="field"><input class="input" type="file" name="qris_image" accept=".jpg,.jpeg,.png,.webp">
        <div class="hint">Unggah HANYA gambar QRIS resmi milik usaha. QRIS tampil di checkout jika gambar ada dan <code>PAY_QRIS_ENABLED=true</code> di .env.</div>
        @error('qris_image')<div class="error">{{ $message }}</div>@enderror</div>

    <button class="btn btn-primary" type="submit">Simpan Pengaturan</button>
</form>
@endsection
