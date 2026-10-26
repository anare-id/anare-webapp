<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Services\CmsAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

final class UpdateAdminPermissionRequest extends FormRequest
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
        return [
            'permissions_present' => ['required', 'accepted'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [
                'required',
                'string',
                'distinct',
                Rule::in(array_diff(array_keys(CmsAccess::permissions()), ['users.manage', 'roles.manage'])),
                Rule::exists(config('permission.table_names.permissions'), 'name')->where('guard_name', 'web'),
            ],
            'name' => ['prohibited'],
            'guard_name' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return ['permissions' => 'hak akses', 'permissions.*' => 'hak akses'];
    }

    protected function getRedirectUrl(): string
    {
        $role = $this->route('role');

        return route('admin.permissions.index', $role instanceof Role ? ['role' => $role->getKey(), 'edit' => 1] : []);
    }
}
