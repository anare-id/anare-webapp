<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final class CreateAdmin extends Command
{
    protected $signature = 'admin:create {--role=admin : Existing web role to assign (use super-admin explicitly for initial setup)}';
    protected $description = 'Create a database-backed administrator using securely prompted credentials';

    public function handle(): int
    {
        $role = Role::where('name', (string) $this->option('role'))->where('guard_name', 'web')->first();
        if ($role === null) {
            $this->error('Unknown role. Run database migrations and choose an existing web role.');
            return self::FAILURE;
        }
        $name = $this->ask('Full name');
        $username = $this->ask('Username');
        $email = $this->ask('Email (optional)');
        $password = $this->secret('Password (12–72 characters, mixed case, numbers and symbols)');
        $confirmation = $this->secret('Confirm password');
        $validator = Validator::make([
            'name' => $name, 'username' => $username, 'email' => $email,
            'password' => $password, 'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_.-]+$/D', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'max:72', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols(), function (string $attribute, mixed $value, \Closure $fail): void {
                if (is_string($value) && (strlen($value) > 72 || str_contains($value, "\0"))) {
                    $fail('Password must contain at most 72 bytes and no null characters.');
                }
            }],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }
        DB::transaction(function () use ($name, $username, $email, $password, $role): void {
            $user = User::create(['name' => $name, 'username' => $username, 'email' => $email ?: null, 'password' => $password, 'is_active' => true]);
            $user->assignRole($role);
        });
        $this->info('Administrator created. Sign in at /admin/login.');
        return self::SUCCESS;
    }
}
