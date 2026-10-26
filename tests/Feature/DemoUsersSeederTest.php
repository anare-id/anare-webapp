<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class DemoUsersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_demo_account_can_log_in_with_its_assigned_role(): void
    {
        $this->seed(DemoUsersSeeder::class);
        $this->assertDatabaseCount('users', 4);

        foreach (DemoUsersSeeder::ACCOUNTS as $account) {
            $user = User::where('username', $account['username'])->firstOrFail();
            $this->assertTrue($user->is_active);
            $this->assertSame([$account['role']], $user->getRoleNames()->all());
            $this->assertNotSame($account['password'], $user->getRawOriginal('password'));
            $this->assertTrue(Hash::check($account['password'], $user->password));

            $this->post('/admin/login', [
                'username' => $account['username'], 'password' => $account['password'],
            ])->assertSessionHasNoErrors()->assertRedirect('/admin');
            $this->assertAuthenticatedAs($user);
            $this->get('/admin')->assertOk();
            $this->get('/admin/users')->assertStatus($account['role'] === 'super-admin' ? 200 : 403);
            $this->post('/admin/logout')->assertRedirect('/admin/login');
            $this->assertGuest();
        }
    }

    public function test_reseeding_preserves_existing_password_status_and_roles(): void
    {
        $this->seed(DemoUsersSeeder::class);
        $user = User::where('username', 'admin')->firstOrFail();
        $user->update(['name' => 'Edited account', 'password' => 'Changed-Password-2026!', 'is_active' => false]);
        $user->syncRoles(['viewer']);
        $passwordHash = $user->getRawOriginal('password');

        $this->seed(DemoUsersSeeder::class);

        $user->refresh()->unsetRelation('roles');
        $this->assertDatabaseCount('users', 4);
        $this->assertSame('Edited account', $user->name);
        $this->assertFalse($user->is_active);
        $this->assertSame($passwordHash, $user->getRawOriginal('password'));
        $this->assertTrue(Hash::check('Changed-Password-2026!', $user->password));
        $this->assertSame(['viewer'], $user->getRoleNames()->all());
    }
}
