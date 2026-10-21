@extends('admin.layout')
@section('content')
@if(session('status'))<p class="panel access-message" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="form-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="panel settings-panel">
    <div class="panel-heading"><div><h2>{{ $editing ? 'Edit akun '.$editing->username : 'Tambah akun administrator' }}</h2><p>Password kosong saat edit mempertahankan password lama. Setiap akun menggunakan satu role.</p></div></div>
    <form class="settings-form" method="POST" action="{{ $editing ? route('admin.users.update', $editing) : route('admin.users.store') }}">
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
        <div class="settings-form-footer"><button type="submit" class="button button-dark">{{ $editing ? 'Simpan akun' : 'Buat akun' }}</button>@if($editing)<a class="button button-white" href="{{ route('admin.users.index') }}">Batal</a>@endif</div>
    </form>
</section>
<section class="panel">
    <div class="panel-heading"><div><h2>Akun administrator</h2><p>Nonaktifkan akses tanpa menghapus riwayat perubahan.</p></div></div>
    <div class="table-scroll"><table class="content-table"><thead><tr><th>Nama / Username</th><th>Email</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @foreach($accounts as $account)<tr><td><strong class="table-title">{{ $account->name }}</strong>{{ $account->username }}</td><td>{{ $account->email ?: '—' }}</td><td>{{ $account->roles->pluck('name')->join(', ') ?: 'Tanpa role' }}</td><td>{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</td><td><a class="button button-white" href="{{ route('admin.users.index', ['edit' => $account->id]) }}">Edit akun</a></td></tr>@endforeach
    </tbody></table></div>
    <div class="table-footer"><span>{{ $accounts->total() }} akun</span><div class="pagination">@if($accounts->previousPageUrl())<a href="{{ $accounts->previousPageUrl() }}">Sebelumnya</a>@endif<span>{{ $accounts->currentPage() }}</span>@if($accounts->nextPageUrl())<a href="{{ $accounts->nextPageUrl() }}">Berikutnya</a>@endif</div></div>
</section>
@endsection
