<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SaveAdminRoleRequest;
use App\Services\AccountManager;
use App\Services\CmsAccess;
use App\Services\CmsContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $editing = $request->filled('edit') ? Role::where('guard_name', 'web')->with('permissions')->findOrFail($request->integer('edit')) : null;
        return view('admin.roles', [
            'section' => 'roles',
            'page' => ['title' => 'Role & Hak Akses', 'description' => 'Atur izin per modul. Pemeriksaan akses berlaku pada setiap request server.'],
            'seed' => $content->state($request->user()),
            'roles' => Role::where('guard_name', 'web')->with('permissions')->withCount('users')->orderBy('name')->get(),
            'permissions' => collect(CmsAccess::permissions())->except(['users.manage', 'roles.manage'])->all(),
            'editing' => $editing,
        ]);
    }

    public function store(SaveAdminRoleRequest $request): RedirectResponse
    {
        $this->accounts->saveRole($request->user(), null, $request->validated());
        return redirect()->route('admin.roles.index')->with('status', 'Role berhasil dibuat.');
    }

    public function update(SaveAdminRoleRequest $request, Role $role): RedirectResponse
    {
        $this->accounts->saveRole($request->user(), $role, $request->validated());
        return redirect()->route('admin.roles.index')->with('status', 'Role berhasil diperbarui.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $this->accounts->deleteRole($request->user(), $role);
        return redirect()->route('admin.roles.index')->with('status', 'Role berhasil dihapus.');
    }
}
