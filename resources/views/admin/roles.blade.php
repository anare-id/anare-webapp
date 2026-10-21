@extends('admin.layout')
@section('content')
@if(session('status'))<p class="panel access-message" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="form-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="panel settings-panel">
    <div class="panel-heading"><div><h2>{{ $editing ? 'Edit role '.$editing->name : 'Tambah role' }}</h2><p>Pilih izin secara eksplisit. Akses masuk panel memerlukan admin.access; akses baca modul terpisah dari izin perubahan.</p></div></div>
    @if($editing?->name === 'super-admin')
        <p class="settings-form">Role Super Admin dilindungi dan tidak dapat diubah. <a href="{{ route('admin.roles.index') }}">Kembali</a></p>
    @else
        <form class="settings-form" method="POST" action="{{ $editing ? route('admin.roles.update', $editing) : route('admin.roles.store') }}">
            @csrf
            @if($editing) @method('PUT') @endif
            <label class="field"><span>Nama role *</span><input name="name" required maxlength="100" value="{{ old('name', $editing?->name) }}"></label>
            <div class="access-permissions">
                @foreach(collect($permissions)->groupBy(fn($label, $name) => explode('.', $name)[0], preserveKeys: true) as $module => $items)
                    <fieldset><legend>{{ $module }}</legend>@foreach($items as $name => $label)<label class="access-permission"><input type="checkbox" name="permissions[]" value="{{ $name }}" @checked(in_array($name, old('permissions', $editing?->permissions->pluck('name')->all() ?? []), true))><span>{{ $label }}<small>{{ $name }}</small></span></label>@endforeach</fieldset>
                @endforeach
            </div>
            <div class="settings-form-footer"><button type="submit" class="button button-dark">{{ $editing ? 'Simpan role' : 'Buat role' }}</button>@if($editing)<a class="button button-white" href="{{ route('admin.roles.index') }}">Batal</a>@endif</div>
        </form>
    @endif
</section>
<section class="panel">
    <div class="panel-heading"><div><h2>Role tersimpan</h2><p>Role yang masih dipakai akun tidak dapat dihapus. Pengelolaan akun dan role khusus Super Admin.</p></div></div>
    <div class="table-scroll"><table class="content-table"><thead><tr><th>Role</th><th>Akun</th><th>Permission</th><th>Aksi</th></tr></thead><tbody>
        @foreach($roles as $role)<tr><td><strong class="table-title">{{ $role->name }}</strong></td><td>{{ $role->users_count }}</td><td>{{ $role->permissions->count() }} izin</td><td>@if($role->name === 'super-admin')Dilindungi @else<div class="table-actions"><a class="button button-white" href="{{ route('admin.roles.index', ['edit' => $role->id]) }}">Edit role</a><form method="POST" action="{{ route('admin.roles.destroy', $role) }}">@csrf @method('DELETE')<button class="button button-danger" type="submit" @disabled($role->users_count > 0)>Hapus role</button></form></div>@endif</td></tr>@endforeach
    </tbody></table></div>
</section>
@endsection
