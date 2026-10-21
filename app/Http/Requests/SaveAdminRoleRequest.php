<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Services\CmsAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

final class SaveAdminRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && $user->is_active
            && $user->hasRole('super-admin', 'web')
            && $user->can('admin.access')
            && $user->can('roles.manage');
    }

    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::notIn(['super-admin']),
                Rule::unique(config('permission.table_names.roles'), 'name')
                    ->where('guard_name', 'web')
                    ->ignore($role instanceof Role ? $role : null),
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [
                'required',
                'string',
                'distinct',
                Rule::in(array_diff(array_keys(CmsAccess::permissions()), ['users.manage', 'roles.manage'])),
            ],
            'guard_name' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama role', 'permissions' => 'izin', 'permissions.*' => 'izin'];
    }

    protected function getRedirectUrl(): string
    {
        $role = $this->route('role');

        return route('admin.roles.index', $role instanceof Role ? ['edit' => $role->getKey()] : []);
    }
}
