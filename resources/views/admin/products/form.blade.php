@extends('layouts.admin')
@section('title', $product->exists ? 'Edit Produk' : 'Tambah Produk')
@section('heading', $product->exists ? 'Edit Produk' : 'Tambah Produk')
@section('actions')<a href="{{ route('admin.products.index') }}" class="btn btn-outline btn-sm">← Kembali</a>@endsection

@section('content')
<form method="POST" enctype="multipart/form-data" data-loading class="panel" style="max-width:760px"
      action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
    @csrf
    @if($product->exists) @method('PUT') @endif

    <div class="field"><label for="name">Nama produk *</label>
        <input id="name" name="name" class="input @error('name') is-invalid @enderror" value="{{ old('name', $product->name) }}" required>
        @error('name')<div class="error">{{ $message }}</div>@enderror</div>

    <div class="field"><label for="category_id">Kategori</label>
        <select id="category_id" name="category_id" class="input">
            <option value="">— Tanpa kategori —</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}" @selected((string) old('category_id', $product->category_id) === (string) $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        @error('category_id')<div class="error">{{ $message }}</div>@enderror</div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="field"><label for="price">Harga (Rp) *</label>
            <input id="price" type="number" step="1" min="0" name="price" class="input @error('price') is-invalid @enderror" value="{{ old('price', $product->price !== null ? (int) $product->price : '') }}" required>
            @error('price')<div class="error">{{ $message }}</div>@enderror</div>
        <div class="field"><label for="stock">Stok *</label>
            <input id="stock" type="number" min="0" name="stock" class="input @error('stock') is-invalid @enderror" value="{{ old('stock', $product->stock ?? 0) }}" required>
            @error('stock')<div class="error">{{ $message }}</div>@enderror</div>
    </div>

    <div class="field"><label for="description">Deskripsi</label>
        <textarea id="description" rows="4" name="description" class="input">{{ old('description', $product->description) }}</textarea>
        @error('description')<div class="error">{{ $message }}</div>@enderror</div>

    <div class="field"><label for="image">Foto produk</label>
        @if($product->image_url)
            <img src="{{ $product->image_url }}" alt="" style="width:140px;border-radius:12px;margin-bottom:.5rem">
            <label class="check" style="margin-bottom:.5rem"><input type="checkbox" name="remove_image" value="1"> Hapus foto saat ini</label>
        @endif
        <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="input @error('image') is-invalid @enderror">
        <div class="hint">JPG/PNG/WEBP, maks. 3 MB. Mengunggah foto baru menggantikan yang lama.</div>
        @error('image')<div class="error">{{ $message }}</div>@enderror</div>

    <div style="display:flex;gap:2rem;flex-wrap:wrap;margin-bottom:1.2rem">
        <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active))> Produk aktif (tampil di toko)</label>
        <label class="check"><input type="checkbox" name="is_best_seller" value="1" @checked(old('is_best_seller', $product->is_best_seller))> Best Seller</label>
    </div>

    <button class="btn btn-primary" type="submit">{{ $product->exists ? 'Simpan Perubahan' : 'Tambah Produk' }}</button>
</form>
@endsection
