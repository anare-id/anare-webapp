<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAdminPermissionRequest;
use App\Services\AccountManager;
use App\Services\CmsAccess;
use App\Services\CmsContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class AdminPermissionController extends Controller
{
    public function __construct(private readonly AccountManager $accounts)
    {
    }

    public function index(Request $request, CmsContent $content): View
    {
        $this->accounts->authorize($request->user(), 'roles.manage');
        $permissions = CmsAccess::permissions();
        $modules = collect(array_keys($permissions))->map(static fn (string $name): string => explode('.', $name, 2)[0])->unique()->sort()->values()->all();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'module' => ['nullable', 'string', Rule::in($modules)],
            'role' => ['nullable', 'integer', 'min:1', Rule::exists(config('permission.table_names.roles'), 'id')->where('guard_name', 'web')],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50])],
            'page' => ['nullable', 'integer', 'min:1'],
            'edit' => ['nullable', 'boolean'],
            'view' => ['nullable', 'integer', 'min:1'],
        ]);
        $roles = Role::where('guard_name', 'web')->with('permissions')->orderBy('name')->get();
        $selectedRole = !empty($filters['role']) ? $roles->firstWhere('id', (int) $filters['role']) : null;
        $isEditing = $request->boolean('edit') && $selectedRole !== null;
        $viewing = !empty($filters['view']) ? Permission::where('guard_name', 'web')->whereIn('name', array_keys($permissions))->with('roles')->findOrFail($filters['view']) : null;
        $rows = Permission::where('guard_name', 'web')->whereIn('name', array_keys($permissions))->with(['roles' => static function ($query): void {
            $query->where('guard_name', 'web')->orderBy('name');
        }]);
        if (($query = trim($filters['q'] ?? '')) !== '') {
            $matchingLabels = array_keys(array_filter($permissions, static fn (string $label): bool => mb_stripos($label, $query) !== false));
            $rows->where(function ($builder) use ($query, $matchingLabels): void {
                $builder->where('name', 'like', '%'.$query.'%')->orWhereIn('name', $matchingLabels);
            });
        }
        if (!empty($filters['module'])) {
            $rows->where('name', 'like', $filters['module'].'.%');
        }
        if ($selectedRole !== null) {
            $rows->whereHas('roles', static function ($query) use ($selectedRole): void {
                $query->whereKey($selectedRole->getKey());
            });
        }

        return view('admin.permissions', [
            'section' => 'permissions',
            'page' => ['title' => 'Hak Akses', 'description' => 'Lihat katalog hak akses sistem dan atur penugasannya pada role.'],
            'seed' => $content->state($request->user()),
            'permissionRows' => $rows->orderBy('name')->paginate($filters['per_page'] ?? 10)->withQueryString(),
            'assignmentRows' => $isEditing
                ? Permission::where('guard_name', 'web')->whereIn('name', array_diff(array_keys($permissions), ['users.manage', 'roles.manage']))->with('roles')->orderBy('name')->get()
                : collect(),
            'permissions' => $permissions,
            'modules' => $modules,
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'assignedPermissions' => $selectedRole?->permissions->pluck('name')->all() ?? [],
            'isEditing' => $isEditing,
            'viewing' => $viewing,
        ]);
    }

    public function update(UpdateAdminPermissionRequest $request, Role $role): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $this->accounts->saveRole($request->user(), $role, ['permissions' => $data['permissions'] ?? []]);

        $message = 'Hak akses role berhasil diperbarui.';
        $destination = route('admin.permissions.index', ['role' => $role->getKey()]);
        $request->session()->flash('status', $message);

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'redirect' => $destination])
            : redirect()->to($destination);
    }
}
