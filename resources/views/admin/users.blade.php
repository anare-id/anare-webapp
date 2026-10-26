@extends('admin.layout')
@section('content')
@if(session('status'))<p class="panel access-message" role="status" data-server-success>{{ session('status') }}</p>@endif
@if($errors->any() && !$formShown)<div class="form-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="management-page">
@if($formShown)
@component('admin.management-modal', ['title' => $viewing ? 'Detail user '.$viewing->username : ($editing ? 'Edit user '.$editing->username : 'Tambah user'), 'returnUrl' => route('admin.users.index', request()->except(['view', 'edit', 'create']))])
    @if($viewing)
        <dl class="management-detail"><dt>Nama</dt><dd>{{ $viewing->name }}</dd><dt>Username</dt><dd>{{ $viewing->username }}</dd><dt>Email</dt><dd>{{ $viewing->email ?: '—' }}</dd><dt>Role</dt><dd>{{ $viewing->roles->pluck('name')->join(', ') ?: 'Tanpa role' }}</dd><dt>Status</dt><dd>{{ $viewing->is_active ? 'Aktif' : 'Nonaktif' }}</dd></dl>
        <div class="settings-form-footer"><button type="button" class="button button-white" data-dialog-close>Tutup</button><a class="button button-dark" href="{{ route('admin.users.index', ['edit' => $viewing->id]) }}">Edit user</a></div>
    @else
    <p class="management-form-note">Password kosong saat edit mempertahankan password lama. Setiap akun menggunakan satu role.</p>
    <form class="settings-form" data-admin-action method="POST" action="{{ $editing ? route('admin.users.update', $editing) : route('admin.users.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="field-grid">
            <label class="field"><span>Nama *</span><input name="name" required maxlength="255" value="{{ old('name', $editing?->name) }}"></label>
            <label class="field"><span>Username *</span><input name="username" required maxlength="100" autocomplete="off" value="{{ old('username', $editing?->username) }}"></label>
            <label class="field"><span>Email</span><input type="email" name="email" maxlength="255" value="{{ old('email', $editing?->email) }}"></label>
            <label class="field"><span>Role *</span><select name="role" required>@foreach($roles as $role)<option value="{{ $role->name }}" @selected(old('role', $editing?->roles->first()?->name ?? 'editor') === $role->name)>{{ $role->name }}</option>@endforeach</select></label>
            <label class="field"><span>{{ $editing ? 'Password baru (opsional)' : 'Password *' }}</span><input type="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" @required(!$editing)><small>12–72 karakter, maksimal 72 byte, huruf besar/kecil, angka, dan simbol.</small></label>
            <label class="field"><span>Konfirmasi password</span><input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="72" @required(!$editing)></label>
            <label class="field"><span>Status akun</span><select name="is_active"><option value="1" @selected((string) old('is_active', $editing?->is_active ?? true) === '1')>Aktif</option><option value="0" @selected((string) old('is_active', $editing?->is_active ?? true) !== '1')>Nonaktif</option></select></label>
        </div>
        <div class="settings-form-footer"><button type="submit" class="button button-dark">{{ $editing ? 'Simpan akun' : 'Buat akun' }}</button><button type="button" class="button button-white" data-dialog-close>Batal</button></div>
    </form>
    @endif
@endcomponent
@endif
<section class="panel">
    <div class="panel-heading"><div><h2>User <span class="management-count">{{ $accounts->total() }}</span></h2><p>Kelola akun administrator, role, dan status akses.</p></div><a class="button button-dark" href="{{ route('admin.users.index', ['create' => 1]) }}">Tambah user</a></div>
    <form class="management-filters" method="GET" action="{{ route('admin.users.index') }}">
        <label class="field management-search"><span>Cari user</span><input type="search" name="q" value="{{ request('q') }}" placeholder="Nama, username, atau email"></label>
        <label class="field"><span>Role</span><select name="role"><option value="">Semua role</option>@foreach($roles as $role)<option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->name }}</option>@endforeach</select></label>
        <label class="field"><span>Status</span><select name="status"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select></label>
        <label class="field"><span>Per halaman</span><select name="per_page">@foreach([10, 20, 50] as $size)<option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}</option>@endforeach</select></label>
        <button class="button button-dark" type="submit">Terapkan</button><a class="button button-white" href="{{ route('admin.users.index') }}">Reset</a>
    </form>
    <div class="table-scroll" role="region" aria-label="Daftar user" tabindex="0"><table class="content-table"><thead><tr><th scope="col">Nama / Username</th><th scope="col">Email</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col">Diperbarui</th><th scope="col" class="actions-heading">Aksi</th></tr></thead><tbody>
        @forelse($accounts as $account)<tr><td><strong class="table-title">{{ $account->name }}</strong><small class="table-slug">{{ $account->username }}</small></td><td>{{ $account->email ?: '—' }}</td><td><span class="table-category">{{ $account->roles->pluck('name')->join(', ') ?: 'Tanpa role' }}</span></td><td><span class="status-badge {{ $account->is_active ? '' : 'draft' }}">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td>{{ $account->updated_at?->format('d M Y') ?? '—' }}</td><td><div class="table-actions"><a class="icon-button" href="{{ route('admin.users.index', [...request()->except(['view', 'edit', 'create']), 'view' => $account->id]) }}" aria-label="Lihat user {{ $account->username }}" title="Lihat"><svg class="icon" aria-hidden="true"><use href="#a-eye"/></svg></a><a class="icon-button" href="{{ route('admin.users.index', [...request()->except(['view', 'edit', 'create']), 'edit' => $account->id]) }}" aria-label="Edit user {{ $account->username }}" title="Edit"><svg class="icon" aria-hidden="true"><use href="#a-edit"/></svg></a></div></td></tr>
        @empty<tr><td colspan="6" class="management-empty"><strong>Tidak ada user ditemukan</strong><span>Coba kata kunci atau filter lain.</span><a href="{{ route('admin.users.index') }}">Reset pencarian</a></td></tr>@endforelse
    </tbody></table></div>
    @include('admin.management-pagination', ['rows' => $accounts, 'noun' => 'user'])
</section>
</div>
@endsection
