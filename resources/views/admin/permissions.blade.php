@extends('admin.layout')
@section('content')
@if(session('status'))<p class="panel access-message" role="status" data-server-success>{{ session('status') }}</p>@endif
@if($errors->any() && !$isEditing)<div class="form-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="management-page">
@if($viewing)
@component('admin.management-modal', ['title' => 'Detail hak akses', 'returnUrl' => route('admin.permissions.index', request()->except(['view', 'edit']))])
    <dl class="management-detail"><dt>Kode</dt><dd>{{ $viewing->name }}</dd><dt>Nama hak akses</dt><dd>{{ $permissions[$viewing->name] }}</dd><dt>Modul</dt><dd>{{ explode('.', $viewing->name)[0] }}</dd><dt>Role</dt><dd>{{ $viewing->roles->pluck('name')->join(', ') ?: 'Belum ditetapkan' }}</dd></dl>
    <div class="settings-form-footer"><button type="button" class="button button-white" data-dialog-close>Tutup</button></div>
@endcomponent
@elseif($isEditing && $selectedRole)
@component('admin.management-modal', ['title' => 'Hak akses role '.$selectedRole->name, 'returnUrl' => route('admin.permissions.index', request()->except(['view', 'edit']))])
    @if($selectedRole->name === 'super-admin')
        <p class="management-form-note">Role Super Admin dilindungi dan tidak dapat diubah.</p>
        @include('admin.permission-accordion', ['rows' => $selectedRole->permissions, 'editable' => false])
    @else
    <p class="management-form-note">Buka modul untuk mengatur izin. Menutup accordion tidak menghapus pilihan; izin yang tidak dicentang akan dilepas saat disimpan.</p>
    <form data-admin-action method="POST" action="{{ route('admin.permissions.update', $selectedRole) }}">
        @csrf @method('PUT')
        <input type="hidden" name="permissions_present" value="1">
        @include('admin.permission-accordion', ['rows' => $assignmentRows, 'editable' => true])
        <div class="management-assignment-footer"><p>Pengelolaan user dan role khusus Super Admin.</p><div><button type="button" class="button button-white" data-dialog-close>Batal</button><button type="submit" class="button button-dark"><svg class="icon" aria-hidden="true"><use href="#a-check"/></svg>Simpan hak akses</button></div></div>
    </form>
    @endif
@endcomponent
@endif
<section class="panel">
    <div class="panel-heading"><div><h2>Hak Akses <span class="management-count">{{ $permissionRows->total() }}</span></h2><p>Katalog permission aplikasi yang tersimpan di database. Kode izin dikelola oleh aplikasi, bukan dibuat secara bebas.</p></div>@if($selectedRole && $selectedRole->name !== 'super-admin')<a class="button button-dark" href="{{ route('admin.permissions.index', ['role' => $selectedRole->id, 'edit' => 1]) }}">Edit hak akses</a>@endif</div>
    <form class="management-filters" method="GET" action="{{ route('admin.permissions.index') }}">
        <label class="field management-search"><span>Cari hak akses</span><input type="search" name="q" value="{{ request('q') }}" placeholder="Kode atau nama izin"></label>
        <label class="field"><span>Modul</span><select name="module"><option value="">Semua modul</option>@foreach($modules as $module)<option value="{{ $module }}" @selected(request('module') === $module)>{{ $module }}</option>@endforeach</select></label>
        <label class="field"><span>Role</span><select name="role"><option value="">Semua role</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected($selectedRole?->id === $role->id)>{{ $role->name }}</option>@endforeach</select></label>
        <label class="field"><span>Per halaman</span><select name="per_page">@foreach([10, 20, 50] as $size)<option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}</option>@endforeach</select></label>
        <button class="button button-dark" type="submit">Terapkan</button><a class="button button-white" href="{{ route('admin.permissions.index') }}">Reset</a>
    </form>
    @if($selectedRole)<div class="management-context"><strong>Role: {{ $selectedRole->name }}</strong><span>{{ count($assignedPermissions) }} izin ditetapkan.@if($selectedRole->name === 'super-admin') Role Super Admin dilindungi dan tidak dapat diubah.@endif</span></div>@else<div class="management-context">Pilih role untuk melihat dan mengelola penetapan hak aksesnya.</div>@endif
    <div class="table-scroll" role="region" aria-label="Katalog hak akses" tabindex="0"><table class="content-table"><thead><tr><th scope="col">Kode permission</th><th scope="col">Nama hak akses</th><th scope="col">Modul</th><th scope="col">Role yang memiliki akses</th>@if($selectedRole)<th scope="col">Status role</th>@endif<th class="actions-heading" scope="col">Aksi</th></tr></thead><tbody>
        @forelse($permissionRows as $permission)<tr><td><code>{{ $permission->name }}</code></td><td>{{ $permissions[$permission->name] ?? $permission->name }}</td><td><span class="management-badge">{{ explode('.', $permission->name)[0] }}</span></td><td><div class="management-badges">@forelse($permission->roles as $role)<span class="management-badge">{{ $role->name }}</span>@empty<span class="management-muted">Belum ditetapkan</span>@endforelse</div></td>@if($selectedRole)<td><span class="management-badge {{ in_array($permission->name, $assignedPermissions, true) ? 'is-active' : 'is-inactive' }}">{{ in_array($permission->name, $assignedPermissions, true) ? 'Ditetapkan' : 'Tidak ditetapkan' }}</span></td>@endif<td><div class="table-actions"><a class="icon-button" href="{{ route('admin.permissions.index', [...request()->except(['view', 'edit']), 'view' => $permission->id]) }}" aria-label="Lihat hak akses {{ $permission->name }}" title="Lihat"><svg class="icon" aria-hidden="true"><use href="#a-eye"/></svg></a></div></td></tr>
        @empty<tr><td colspan="{{ $selectedRole ? 6 : 5 }}" class="management-empty"><strong>Tidak ada hak akses ditemukan</strong><span>Coba kata kunci atau filter modul lain.</span><a href="{{ route('admin.permissions.index') }}">Reset pencarian</a></td></tr>@endforelse
    </tbody></table></div>
    @include('admin.management-pagination', ['rows' => $permissionRows, 'noun' => 'hak akses'])
</section>
</div>
@endsection
