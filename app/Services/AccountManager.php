<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

final class AccountManager
{
    public function authorize(User $actor, string $permission): void
    {
        abort_unless($actor->is_active && $actor->hasRole('super-admin', 'web')
            && $actor->can('admin.access') && $actor->can($permission), 403);
    }

    public function saveUser(User $actor, ?User $account, array $data): void
    {
        DB::transaction(function () use ($actor, $account, $data): void {
            $this->lockGovernance();
            $this->authorize($this->freshActor($actor), 'users.manage');
            $role = Role::query()->where('guard_name', 'web')->where('name', $data['role'])->lockForUpdate()->firstOrFail();
            $target = $account === null ? new User() : User::query()->lockForUpdate()->findOrFail($account->id);
            if ($target->exists && $target->is_active && $target->hasRole('super-admin', 'web')
                && (!$data['is_active'] || $role->name !== 'super-admin')) {
                $others = User::query()->where('is_active', true)->whereKeyNot($target->id)
                    ->role('super-admin', 'web')->lockForUpdate()->get(['users.id']);
                if ($others->isEmpty()) {
                    throw ValidationException::withMessages(['role' => 'Super Admin aktif terakhir tidak dapat dinonaktifkan atau diturunkan rolenya.']);
                }
            }
            $attributes = collect($data)->only(['name', 'username', 'email', 'is_active'])->all();
            if (!empty($data['password'])) {
                $attributes['password'] = $data['password'];
            }
            $target->fill($attributes)->save();
            $target->syncRoles([$role]);
            $target->syncPermissions([]);
            $this->activity($actor, ($account === null ? 'Membuat akun ' : 'Memperbarui akun ').$target->username.' · '.$role->name);
        });
    }

    public function saveRole(User $actor, ?Role $role, array $data): void
    {
        DB::transaction(function () use ($actor, $role, $data): void {
            $this->lockGovernance();
            $this->authorize($this->freshActor($actor), 'roles.manage');
            $target = $role === null ? new Role(['guard_name' => 'web']) : Role::query()->where('guard_name', 'web')->lockForUpdate()->findOrFail($role->id);
            $this->editableRole($target);
            $permissions = $data['permissions'] ?? [];
            $allowed = array_diff(array_keys(CmsAccess::permissions()), ['users.manage', 'roles.manage']);
            $name = $data['name'] ?? $target->name;
            if ($name === 'super-admin' || array_diff($permissions, $allowed) !== []) {
                throw ValidationException::withMessages(['permissions' => 'Hak pengelolaan administrator tidak dapat diberikan ke role biasa.']);
            }
            $target->name = $name;
            $target->save();
            if ($role === null || array_key_exists('permissions', $data)) {
                $target->syncPermissions($permissions);
            }
            $this->activity($actor, ($role === null ? 'Membuat role ' : 'Memperbarui role ').$target->name);
        });
    }

    public function deleteRole(User $actor, Role $role): void
    {
        DB::transaction(function () use ($actor, $role): void {
            $this->lockGovernance();
            $this->authorize($this->freshActor($actor), 'roles.manage');
            $target = Role::query()->where('guard_name', 'web')->lockForUpdate()->findOrFail($role->id);
            $this->editableRole($target);
            if ($target->users()->exists()) {
                throw ValidationException::withMessages(['role' => 'Role masih dipakai akun. Pindahkan akun ke role lain terlebih dahulu.']);
            }
            $name = $target->name;
            $target->delete();
            $this->activity($actor, 'Menghapus role '.$name);
        });
    }

    private function lockGovernance(): void
    {
        Role::query()->where('guard_name', 'web')->where('name', 'super-admin')->lockForUpdate()->firstOrFail();
    }

    private function freshActor(User $actor): User
    {
        return User::query()->lockForUpdate()->findOrFail($actor->id);
    }

    private function editableRole(Role $role): void
    {
        if ($role->name === 'super-admin') {
            throw ValidationException::withMessages(['role' => 'Role Super Admin tidak dapat diubah atau dihapus.']);
        }
    }

    private function activity(User $actor, string $title): void
    {
        Activity::create(['user_id' => $actor->id, 'title' => mb_substr($title, 0, 255)]);
    }
}
