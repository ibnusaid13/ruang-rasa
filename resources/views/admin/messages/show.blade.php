@extends('layouts.admin')
@section('title', 'Pesan')
@section('heading', 'Pesan dari ' . $message->name)
@section('actions')<a href="{{ route('admin.messages.index') }}" class="btn btn-outline btn-sm">← Kembali</a>@endsection

@section('content')
<div class="panel" style="max-width:720px">
    <dl class="dl">
        <dt>Nama</dt><dd>{{ $message->name }}</dd>
        <dt>Email</dt><dd>{{ $message->email }}</dd>
        <dt>WhatsApp</dt><dd>{{ $message->phone ?: '—' }}</dd>
        <dt>Diterima</dt><dd>{{ $message->created_at->format('d M Y, H:i') }}</dd>
    </dl>
    <hr style="border:0;border-top:1px dashed var(--sand);margin:1rem 0">
    <p style="white-space:pre-line">{{ $message->message }}</p>
    <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" data-confirm="Hapus pesan ini?">@csrf @method('DELETE')
        <button class="btn btn-danger btn-sm">Hapus Pesan</button></form>
</div>
@endsection
