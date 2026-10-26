<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final class DemoUsersSeeder extends Seeder
{
    public const ACCOUNTS = [
        ['role' => 'super-admin', 'username' => 'superadmin', 'name' => 'Demo Super Admin', 'password' => 'SuperAdmin123!'],
        ['role' => 'admin', 'username' => 'admin', 'name' => 'Demo Admin', 'password' => 'AdminDemo123!'],
        ['role' => 'editor', 'username' => 'editor', 'name' => 'Demo Editor', 'password' => 'EditorDemo123!'],
        ['role' => 'viewer', 'username' => 'viewer', 'name' => 'Demo Viewer', 'password' => 'ViewerDemo123!'],
    ];

    public function run(): void
    {
        $this->call(CmsAccessSeeder::class);
        foreach (self::ACCOUNTS as $account) {
            Role::findByName($account['role'], 'web');
        }

        DB::transaction(function (): void {
            foreach (self::ACCOUNTS as $account) {
                $user = User::query()->firstOrCreate(['username' => $account['username']], [
                    'name' => $account['name'],
                    'email' => $account['username'].'@anare.example',
                    'password' => $account['password'],
                    'is_active' => true,
                ]);

                if ($user->wasRecentlyCreated) {
                    $user->assignRole($account['role']);
                    $this->command?->info('Akun demo dibuat: '.$user->username.' ('.$account['role'].')');
                } else {
                    $this->command?->line('Akun '.$user->username.' sudah ada; data dan role dipertahankan.');
                }
            }
        });
    }
}
