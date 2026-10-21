<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Models\User;
use App\Services\AccountManager;
use App\Services\CmsContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

final class AdminUserController extends Controller
{
    public function __construct(private readonly AccountManager $accounts)
    {
    }

    public function index(Request $request, CmsContent $content): View
    {
        $this->accounts->authorize($request->user(), 'users.manage');
        $editing = $request->filled('edit') ? User::with('roles')->findOrFail($request->integer('edit')) : null;
        return view('admin.users', [
            'section' => 'users',
            'page' => ['title' => 'Akun Admin', 'description' => 'Kelola akun, role, status aktif, dan kata sandi administrator.'],
            'seed' => $content->state($request->user()),
            'accounts' => User::with('roles')->orderBy('id')->paginate(20)->withQueryString(),
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->get(),
            'editing' => $editing,
        ]);
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $this->accounts->saveUser($request->user(), null, $request->validated());
        return redirect()->route('admin.users.index')->with('status', 'Akun administrator berhasil dibuat.');
    }

    public function update(UpdateAdminUserRequest $request, User $user): RedirectResponse
    {
        $this->accounts->saveUser($request->user(), $user, $request->validated());
        return redirect()->route('admin.users.index')->with('status', 'Akun administrator berhasil diperbarui.');
    }
}
