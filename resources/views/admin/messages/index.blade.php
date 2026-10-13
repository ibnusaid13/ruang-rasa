@extends('layouts.admin')
@section('title', 'Pesan Kontak')
@section('heading', 'Pesan Kontak')

@section('content')
@if($messages->isEmpty())
    <div class="empty panel"><div class="icon">✉️</div><p class="muted">Belum ada pesan masuk.</p></div>
@else
<div class="table-wrap"><table>
    <thead><tr><th>Pengirim</th><th>Pesan</th><th>Diterima</th><th>Aksi</th></tr></thead>
    <tbody>
    @foreach($messages as $m)
        <tr>
            <td>@if(!$m->read_at)<span class="badge badge-pending">Baru</span> @endif<strong>{{ $m->name }}</strong><div class="muted" style="font-size:.78rem">{{ $m->email }}</div></td>
            <td>{{ \Illuminate\Support\Str::limit($m->message, 80) }}</td>
            <td>{{ $m->created_at->format('d M Y, H:i') }}</td>
            <td><div class="actions">
                <a class="btn btn-outline btn-sm" href="{{ route('admin.messages.show', $m) }}">Baca</a>
                <form method="POST" action="{{ route('admin.messages.destroy', $m) }}" data-confirm="Hapus pesan ini?">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Hapus</button></form>
            </div></td>
        </tr>
    @endforeach
    </tbody>
</table></div>
{{ $messages->links() }}
@endif
@endsection
