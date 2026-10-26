<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ManagementTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $name, string $role = 'super-admin', bool $active = true): User
    {
        $user = User::create(['name' => $name, 'username' => $name, 'email' => $name.'@example.test',
            'password' => 'Management-Password-2026!', 'is_active' => $active]);
        $user->assignRole($role);
        return $user;
    }

    public function test_user_search_role_and_status_filters_combine_without_leaking_other_rows(): void
    {
        $owner = $this->account('owner');
        $match = $this->account('clinic-viewer', 'viewer', false);
        $this->account('clinic-editor', 'editor', false);
        $this->account('clinic-active', 'viewer');
        $this->actingAs($owner)->get('/admin/users?q=clinic&role=viewer&status=inactive')
            ->assertOk()->assertViewHas('accounts', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $match->id);
    }

    public function test_user_pagination_uses_selected_page_size_and_keeps_filters(): void
    {
        $owner = $this->account('owner');
        for ($i = 1; $i <= 12; $i++) {
            $this->account('staff-'.$i, 'viewer');
        }
        $this->actingAs($owner)->get('/admin/users?q=staff&role=viewer&per_page=10&page=2')
            ->assertOk()->assertViewHas('accounts', fn ($rows) => $rows->total() === 12
                && $rows->currentPage() === 2 && $rows->count() === 2
                && str_contains($rows->previousPageUrl(), 'role=viewer'));
    }

    public function test_role_search_returns_real_matching_roles_and_counts(): void
    {
        $owner = $this->account('owner');
        $role = Role::create(['name' => 'medical-editor', 'guard_name' => 'web']);
        $role->syncPermissions(['admin.access', 'articles.view']);
        $this->account('writer', 'medical-editor');
        $this->actingAs($owner)->get('/admin/roles?q=medical')
            ->assertOk()->assertViewHas('roles', fn ($rows) => $rows->total() === 1
                && $rows->first()->name === 'medical-editor' && $rows->first()->users_count === 1
                && $rows->first()->permissions_count === 2);
    }

    public function test_renaming_role_without_permission_payload_preserves_existing_access(): void
    {
        $owner = $this->account('owner');
        $role = Role::create(['name' => 'medical-editor', 'guard_name' => 'web']);
        $role->syncPermissions(['admin.access', 'articles.view', 'articles.create']);
        $writer = $this->account('writer', 'medical-editor');
        $this->actingAs($owner)->put('/admin/roles/'.$role->id, ['name' => 'clinic-editor'])->assertRedirect();
        $this->assertTrue($writer->fresh()->hasRole('clinic-editor'));
        $this->assertTrue($writer->fresh()->can('articles.create'));
    }

    public function test_permission_list_combines_module_and_role_filters(): void
    {
        $owner = $this->account('owner');
        $role = Role::findByName('viewer', 'web');
        $this->actingAs($owner)->get('/admin/permissions?module=articles&role='.$role->id)
            ->assertOk()->assertViewHas('permissionRows', fn ($rows) => $rows->total() === 1
                && $rows->first()->name === 'articles.view');
    }

    public function test_permission_search_matches_code_and_catalog_label(): void
    {
        $owner = $this->account('owner');
        $this->actingAs($owner)->get('/admin/permissions?q=articles.publish')
            ->assertOk()->assertViewHas('permissionRows', fn ($rows) => $rows->total() === 1
                && $rows->first()->name === 'articles.publish');
        $this->get('/admin/permissions?q=Unggah')
            ->assertOk()->assertViewHas('permissionRows', fn ($rows) => $rows->total() === 1
                && $rows->first()->name === 'media.upload');
    }

    public function test_separate_permission_assignment_changes_real_access_and_can_clear_all(): void
    {
        $owner = $this->account('owner');
        $role = Role::create(['name' => 'writer-role', 'guard_name' => 'web']);
        $writer = $this->account('writer', 'writer-role');
        $this->actingAs($owner)->put('/admin/permissions/'.$role->id, [
            'permissions' => ['admin.access', 'articles.view'], 'permissions_present' => 1,
        ])->assertRedirect();
        $this->assertTrue($writer->fresh()->can('articles.view'));
        $this->assertFalse($writer->fresh()->can('articles.create'));
        $this->put('/admin/permissions/'.$role->id, ['permissions' => [], 'permissions_present' => 1])->assertRedirect();
        $this->assertFalse($writer->fresh()->can('admin.access'));
    }

    public function test_permission_management_does_not_weaken_governance_protections(): void
    {
        $owner = $this->account('owner');
        $superRole = Role::findByName('super-admin', 'web');
        $normalRole = Role::findByName('viewer', 'web');
        $this->actingAs($owner)->putJson('/admin/permissions/'.$superRole->id, ['permissions' => [], 'permissions_present' => 1])
            ->assertUnprocessable();
        $this->putJson('/admin/permissions/'.$normalRole->id, ['permissions' => ['users.manage'], 'permissions_present' => 1])
            ->assertUnprocessable();
        $viewer = $this->account('viewer-user', 'viewer');
        $this->actingAs($viewer)->get('/admin/permissions')->assertForbidden();
        $this->putJson('/admin/permissions/'.$normalRole->id, ['permissions' => [], 'permissions_present' => 1])->assertForbidden();
    }

    public function test_invalid_table_query_is_rejected_instead_of_unbounded_loading(): void
    {
        $owner = $this->account('owner');
        foreach (['/admin/users', '/admin/roles', '/admin/permissions'] as $path) {
            $this->actingAs($owner)->getJson($path.'?per_page=100000&page=0')->assertUnprocessable();
        }
    }

    public function test_user_detail_is_authorized_and_never_exposes_password_hash(): void
    {
        $owner = $this->account('owner');
        $target = $this->account('staff', 'viewer');
        $this->actingAs($owner)->get('/admin/users?view='.$target->id)
            ->assertOk()->assertViewHas('viewing', fn ($user) => $user->id === $target->id)
            ->assertDontSee($target->getRawOriginal('password'), false);
        $this->actingAs($target)->get('/admin/users?view='.$owner->id)->assertForbidden();
    }

    public function test_invalid_role_edit_returns_to_same_record_and_preserves_old_input(): void
    {
        $owner = $this->account('owner');
        $role = Role::findByName('editor', 'web');
        $this->actingAs($owner)->from('/admin/roles?edit='.$role->id)
            ->put('/admin/roles/'.$role->id, ['name' => 'admin'])
            ->assertRedirect('/admin/roles?edit='.$role->id)
            ->assertSessionHasErrors('name')->assertSessionHasInput('name', 'admin');
        $this->assertSame('editor', $role->fresh()->name);
    }
}
