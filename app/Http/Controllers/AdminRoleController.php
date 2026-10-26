<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SaveAdminRoleRequest;
use App\Services\AccountManager;
use App\Services\CmsContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

final class AdminRoleController extends Controller
{
    public function __construct(private readonly AccountManager $accounts)
    {
    }

    public function index(Request $request, CmsContent $content): View
    {
        $this->accounts->authorize($request->user(), 'roles.manage');
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50])],
            'page' => ['nullable', 'integer', 'min:1'],
            'edit' => ['nullable', 'integer', 'min:1'],
            'view' => ['nullable', 'integer', 'min:1'],
            'create' => ['nullable', 'boolean'],
        ]);
        $editing = !empty($filters['edit']) ? Role::where('guard_name', 'web')->with('permissions')->findOrFail($filters['edit']) : null;
        $viewing = !empty($filters['view']) ? Role::where('guard_name', 'web')->with('permissions')->withCount('users')->findOrFail($filters['view']) : null;
        $roles = Role::where('guard_name', 'web')->withCount(['users', 'permissions']);
        if (($query = trim($filters['q'] ?? '')) !== '') {
            $roles->where('name', 'like', '%'.$query.'%');
        }
        return view('admin.roles', [
            'section' => 'roles',
            'page' => ['title' => 'Role', 'description' => 'Kelola nama role administrator dan lihat penggunaan serta jumlah hak aksesnya.'],
            'seed' => $content->state($request->user()),
            'roles' => $roles->orderBy('name')->paginate($filters['per_page'] ?? 10)->withQueryString(),
            'editing' => $editing,
            'viewing' => $viewing,
            'formShown' => $viewing !== null || $editing !== null || $request->boolean('create') || $request->session()->has('errors'),
        ]);
    }

    public function store(SaveAdminRoleRequest $request): RedirectResponse|JsonResponse
    {
        $this->accounts->saveRole($request->user(), null, $request->validated());
        return $this->success($request, 'Role berhasil dibuat.', 201);
    }

    public function update(SaveAdminRoleRequest $request, Role $role): RedirectResponse|JsonResponse
    {
        $this->accounts->saveRole($request->user(), $role, $request->validated());
        return $this->success($request, 'Role berhasil diperbarui.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse|JsonResponse
    {
        $this->accounts->deleteRole($request->user(), $role);
        return $this->success($request, 'Role berhasil dihapus.');
    }

    private function success(Request $request, string $message, int $status = 200): RedirectResponse|JsonResponse
    {
        $request->session()->flash('status', $message);

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'redirect' => route('admin.roles.index')], $status)
            : redirect()->route('admin.roles.index');
    }
}
