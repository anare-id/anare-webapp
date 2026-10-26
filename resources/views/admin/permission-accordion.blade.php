@php
    $moduleLabels = ['admin' => 'Akses Panel', 'dashboard' => 'Dashboard', 'activity' => 'Aktivitas', 'home' => 'Beranda', 'skin' => 'Anare Skin', 'shape' => 'Anare Shape', 'doctors' => 'Dokter', 'articles' => 'Artikel', 'settings' => 'Pengaturan', 'media' => 'Media', 'users' => 'User', 'roles' => 'Role'];
    $selected = $editable && old('permissions_present') ? old('permissions', []) : $assignedPermissions;
    $groups = $rows->groupBy(fn ($permission) => explode('.', $permission->name, 2)[0])->sortBy(fn ($items, $module) => array_search($module, array_keys($moduleLabels), true));
@endphp
<div class="permission-accordion" data-permission-accordion>
    @forelse($groups as $module => $items)
        <details class="permission-group" @if($loop->first) open @endif>
            <summary><span class="permission-group-heading"><strong>{{ $moduleLabels[$module] ?? $module }}</strong><small>{{ $module }}</small></span><span class="permission-group-count"><span data-permission-selected>{{ $items->whereIn('name', $selected)->count() }}</span> / {{ $items->count() }} dipilih</span><svg class="icon permission-group-chevron" aria-hidden="true"><use href="#a-chevron"/></svg></summary>
            <div class="permission-group-options">
                @foreach($items as $permission)
                    <label class="permission-option" for="role-permission-{{ $permission->id }}">
                        <input class="management-checkbox" id="role-permission-{{ $permission->id }}" type="checkbox" @if($editable) name="permissions[]" @else disabled @endif value="{{ $permission->name }}" @checked(in_array($permission->name, $selected, true))>
                        <span><strong>{{ $permissions[$permission->name] ?? $permission->name }}</strong><code>{{ $permission->name }}</code></span>
                    </label>
                @endforeach
            </div>
        </details>
    @empty
        <p class="management-form-note">Belum ada permission yang dapat ditetapkan.</p>
    @endforelse
</div>
