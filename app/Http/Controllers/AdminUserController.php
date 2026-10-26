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
use Illuminate\Validation\Rule;
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
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', Rule::exists(config('permission.table_names.roles'), 'name')->where('guard_name', 'web')],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50])],
            'page' => ['nullable', 'integer', 'min:1'],
            'edit' => ['nullable', 'integer', 'min:1'],
            'view' => ['nullable', 'integer', 'min:1'],
            'create' => ['nullable', 'boolean'],
        ]);
        $editing = !empty($filters['edit']) ? User::with('roles')->findOrFail($filters['edit']) : null;
        $viewing = !empty($filters['view']) ? User::with('roles')->findOrFail($filters['view']) : null;
        $accounts = User::with('roles');
        if (($query = trim($filters['q'] ?? '')) !== '') {
            $accounts->where(function ($builder) use ($query): void {
                $builder->where('name', 'like', '%'.$query.'%')
                    ->orWhere('username', 'like', '%'.$query.'%')
                    ->orWhere('email', 'like', '%'.$query.'%');
            });
        }
        if (!empty($filters['role'])) {
            $accounts->role($filters['role'], 'web');
        }
        if (!empty($filters['status'])) {
            $accounts->where('is_active', $filters['status'] === 'active');
        }
        return view('admin.users', [
            'section' => 'users',
            'page' => ['title' => 'User', 'description' => 'Kelola akun, role, status aktif, dan kata sandi administrator.'],
            'seed' => $content->state($request->user()),
            'accounts' => $accounts->orderBy('id')->paginate($filters['per_page'] ?? 10)->withQueryString(),
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->get(),
            'editing' => $editing,
            'viewing' => $viewing,
            'formShown' => $viewing !== null || $editing !== null || $request->boolean('create') || $request->session()->has('errors'),
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
