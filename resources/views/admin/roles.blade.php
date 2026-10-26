@extends('admin.layout')
@section('content')
@if(session('status'))<p class="panel access-message" role="status">{{ session('status') }}</p>@endif
@if($errors->any() && !$formShown)<div class="form-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="management-page">
@if($formShown)
@component('admin.management-modal', ['title' => $viewing ? 'Detail role '.$viewing->name : ($editing ? 'Edit role '.$editing->name : 'Tambah role'), 'returnUrl' => route('admin.roles.index', request()->except(['view', 'edit', 'create']))])
    @if($viewing)
        <dl class="management-detail"><dt>Role</dt><dd>{{ $viewing->name }}</dd><dt>User</dt><dd>{{ $viewing->users_count }}</dd><dt>Hak akses</dt><dd>{{ $viewing->permissions->count() }} izin</dd></dl>
        <div class="table-scroll"><table class="content-table"><thead><tr><th scope="col">Permission</th><th scope="col">Nama hak akses</th></tr></thead><tbody>@forelse($viewing->permissions as $permission)<tr><td>{{ $permission->name }}</td><td>{{ \App\Services\CmsAccess::permissions()[$permission->name] ?? $permission->name }}</td></tr>@empty<tr><td colspan="2">Belum ada hak akses.</td></tr>@endforelse</tbody></table></div>
        <div class="settings-form-footer"><button type="button" class="button button-white" data-dialog-close>Tutup</button></div>
    @elseif($editing?->name === 'super-admin')
        <p>Role Super Admin dilindungi dan tidak dapat diubah.</p>
    @else
        <form class="settings-form" method="POST" action="{{ $editing ? route('admin.roles.update', $editing) : route('admin.roles.store') }}">
            @csrf
            @if($editing) @method('PUT') @endif
            <label class="field"><span>Nama role *</span><input name="name" required maxlength="100" value="{{ old('name', $editing?->name) }}"></label>
            <p class="management-form-note">{{ $editing ? 'Mengubah nama tidak mengubah permission yang sudah ditetapkan.' : 'Role baru dibuat tanpa permission. Tetapkan izin melalui Hak Akses setelah menyimpan.' }}</p>
            <div class="settings-form-footer"><button type="submit" class="button button-dark">{{ $editing ? 'Simpan role' : 'Buat role' }}</button><button type="button" class="button button-white" data-dialog-close>Batal</button></div>
        </form>
    @endif
@endcomponent
@endif
<section class="panel">
    <div class="panel-heading"><div><h2>Role <span class="management-count">{{ $roles->total() }}</span></h2><p>Role yang masih digunakan user tidak dapat dihapus.</p></div><a class="button button-dark" href="{{ route('admin.roles.index', ['create' => 1]) }}">Tambah role</a></div>
    <form class="management-filters" method="GET" action="{{ route('admin.roles.index') }}">
        <label class="field management-search"><span>Cari role</span><input type="search" name="q" value="{{ request('q') }}" placeholder="Nama role"></label>
        <label class="field"><span>Per halaman</span><select name="per_page">@foreach([10, 20, 50] as $size)<option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}</option>@endforeach</select></label>
        <button class="button button-dark" type="submit">Terapkan</button><a class="button button-white" href="{{ route('admin.roles.index') }}">Reset</a>
    </form>
    <div class="table-scroll" role="region" aria-label="Daftar role" tabindex="0"><table class="content-table"><thead><tr><th scope="col">Role</th><th scope="col">User</th><th scope="col">Hak akses</th><th scope="col">Diperbarui</th><th scope="col" class="actions-heading">Aksi</th></tr></thead><tbody>
        @forelse($roles as $role)<tr><td><strong class="table-title">{{ $role->name }}</strong>@if($role->name === 'super-admin')<span class="table-category">Dilindungi</span>@endif</td><td>{{ $role->users_count }} user</td><td>{{ $role->permissions_count }} izin</td><td>{{ $role->updated_at?->format('d M Y') ?? '—' }}</td><td><div class="table-actions"><a class="icon-button" href="{{ route('admin.roles.index', [...request()->except(['view', 'edit', 'create']), 'view' => $role->id]) }}" aria-label="Lihat role {{ $role->name }}" title="Lihat"><svg class="icon" aria-hidden="true"><use href="#a-eye"/></svg></a><a class="icon-button" href="{{ route('admin.permissions.index', ['role' => $role->id, 'edit' => 1]) }}" aria-label="Hak akses role {{ $role->name }}" title="Hak akses"><svg class="icon" aria-hidden="true"><use href="#a-settings"/></svg></a>@if($role->name !== 'super-admin')<a class="icon-button" href="{{ route('admin.roles.index', [...request()->except(['view', 'edit', 'create']), 'edit' => $role->id]) }}" aria-label="Edit role {{ $role->name }}" title="Edit"><svg class="icon" aria-hidden="true"><use href="#a-edit"/></svg></a><form method="POST" action="{{ route('admin.roles.destroy', $role) }}" data-confirm-role="{{ $role->name }}">@csrf @method('DELETE')<button class="icon-button" type="submit" aria-label="Hapus role {{ $role->name }}" title="{{ $role->users_count > 0 ? 'Role masih digunakan user' : 'Hapus' }}" @disabled($role->users_count > 0)><svg class="icon" aria-hidden="true"><use href="#a-trash"/></svg></button></form>@endif</div></td></tr>
        @empty<tr><td colspan="5" class="management-empty"><strong>Tidak ada role ditemukan</strong><span>Coba kata kunci lain atau tambahkan role baru.</span><a href="{{ route('admin.roles.index') }}">Reset pencarian</a></td></tr>@endforelse
    </tbody></table></div>
    @include('admin.management-pagination', ['rows' => $roles, 'noun' => 'role'])
</section>
</div>
@endsection
