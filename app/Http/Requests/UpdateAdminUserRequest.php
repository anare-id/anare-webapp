<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;

final class UpdateAdminUserRequest extends StoreAdminUserRequest
{
    public function rules(): array
    {
        $account = $this->route('user');
        abort_unless($account instanceof User, 404);

        return $this->accountRules($account);
    }

    protected function getRedirectUrl(): string
    {
        return route('admin.users.index', ['edit' => $this->route('user')->getKey()]);
    }
}
