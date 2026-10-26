<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && $user->is_active
            && $user->hasRole('super-admin', 'web')
            && $user->can('admin.access')
            && $user->can('users.manage');
    }

    public function rules(): array
    {
        return $this->accountRules();
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'username' => 'username',
            'email' => 'email',
            'role' => 'role',
            'is_active' => 'status akun',
            'password' => 'kata sandi',
        ];
    }

    protected function accountRules(?User $account = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_.-]+$/D', Rule::unique('users', 'username')->ignore($account)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($account)],
            'role' => ['required', 'string', Rule::exists(config('permission.table_names.roles'), 'name')->where('guard_name', 'web')],
            'is_active' => ['required', 'boolean'],
            'password' => [
                $account === null ? 'required' : 'nullable',
                'string',
                Password::min(12)->mixedCase()->numbers()->symbols(),
                'confirmed',
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && (strlen($value) > 72 || str_contains($value, "\0"))) {
                        $fail('Kata sandi maksimal 72 byte dan tidak boleh mengandung karakter null.');
                    }
                },
            ],
            'roles' => ['prohibited'],
            'permissions' => ['prohibited'],
            'direct_permissions' => ['prohibited'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('admin.users.index', ['create' => 1]);
    }
}
