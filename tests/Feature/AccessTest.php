<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class AccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(?string $role = null, bool $active = true): User
    {
        $number = User::query()->count() + 1;
        $user = User::query()->create([
            'name' => 'Account '.$number,
            'username' => 'account'.$number,
            'email' => 'account'.$number.'@example.test',
            'password' => 'Actual-Password-2026!',
            'is_active' => $active,
        ]);
        if ($role !== null) {
            $user->assignRole($role);
        }
        return $user;
    }

    private function article(string $status = 'draft'): Article
    {
        $category = Category::query()->firstOrCreate(
            ['group' => 'articles', 'slug' => 'clinic'],
            ['title' => 'Klinik', 'title_en' => 'Clinic'],
        );
        return Article::query()->create([
            'category_id' => $category->id,
            'slug' => 'clinic-'.(Article::query()->count() + 1),
            'title' => 'Konten Klinik',
            'title_en' => 'Clinic Content',
            'body' => 'Konten asli dari database.',
            'status' => $status,
        ]);
    }

    private function accountPayload(User $user, string $role, bool $active = true): array
    {
        return [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $role,
            'is_active' => $active,
        ];
    }

    public function test_database_account_without_panel_permission_cannot_log_in(): void
    {
        $user = $this->user();
        $this->post('/admin/login', [
            'username' => $user->username, 'password' => 'Actual-Password-2026!',
        ])->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->actingAs($user)->getJson('/admin/content')->assertForbidden();
    }

    public function test_inactive_account_is_rejected_even_with_super_admin_role(): void
    {
        $user = $this->user('super-admin', false);
        $this->post('/admin/login', [
            'username' => $user->username, 'password' => 'Actual-Password-2026!',
        ])->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_viewer_reads_content_but_cannot_mutate_any_admin_resource(): void
    {
        $viewer = $this->user('viewer');
        $record = $this->article();
        $this->actingAs($viewer)->get('/admin')->assertOk();
        $this->getJson('/admin/content')->assertOk();
        $this->postJson('/admin/content/articles', ['title' => 'Intrusion'])->assertForbidden();
        $this->putJson('/admin/content/articles/'.$record->id, ['title' => 'Intrusion'])->assertForbidden();
        $this->deleteJson('/admin/content/articles/'.$record->id)->assertForbidden();
        $this->postJson('/admin/content/articles/'.$record->id.'/duplicate')->assertForbidden();
        $this->postJson('/admin/categories/articles', ['title' => 'Intrusion', 'slug' => 'intrusion'])->assertForbidden();
        $this->putJson('/admin/settings/contact', ['name' => 'Intrusion'])->assertForbidden();
        $this->post('/admin/uploads', [], ['Accept' => 'application/json'])->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/roles')->assertForbidden();
        $this->assertDatabaseHas('articles', ['id' => $record->id, 'title' => 'Konten Klinik']);
    }

    public function test_editor_can_edit_drafts_but_cannot_publish_or_change_live_content(): void
    {
        $editor = $this->user('editor');
        $draft = $this->article();
        $published = $this->article('published');
        $this->actingAs($editor)->putJson('/admin/content/articles/'.$draft->id, ['title' => 'Draft Edited'])
            ->assertSuccessful();
        $this->putJson('/admin/content/articles/'.$draft->id, ['status' => 'published'])->assertForbidden();
        $this->putJson('/admin/content/articles/'.$published->id, ['title' => 'Live Bypass'])->assertForbidden();
        $this->putJson('/admin/content/articles/'.$published->id, ['featured' => true])->assertForbidden();
        $this->deleteJson('/admin/content/articles/'.$published->id)->assertForbidden();
        $this->assertDatabaseHas('articles', ['id' => $draft->id, 'status' => 'draft', 'title' => 'Draft Edited']);
        $this->assertDatabaseHas('articles', ['id' => $published->id, 'title' => 'Konten Klinik', 'featured' => false]);
    }

    public function test_editor_can_duplicate_live_article_as_draft_without_modifying_original(): void
    {
        $editor = $this->user('editor');
        $published = $this->article('published');
        $this->actingAs($editor)->postJson('/admin/content/articles/'.$published->id.'/duplicate')->assertSuccessful();
        $copy = Article::query()->whereKeyNot($published->id)->firstOrFail();
        $this->assertSame('draft', $copy->status);
        $this->assertNotSame($published->slug, $copy->slug);
        $this->get('/articles/'.$copy->slug)->assertNotFound();
        $this->assertDatabaseHas('articles', ['id' => $published->id, 'status' => 'published']);
    }

    public function test_editor_cannot_relabel_categories_or_settings_affecting_published_content(): void
    {
        $editor = $this->user('editor');
        $article = $this->article('published');
        $this->actingAs($editor)->putJson('/admin/categories/articles/'.$article->category_id, [
            'title' => 'Published Bypass', 'slug' => 'changed',
        ])->assertForbidden();
        $this->putJson('/admin/settings/home', ['services_title' => 'Live Bypass'])->assertForbidden();
        $this->putJson('/admin/settings/contact', ['name' => 'Live Bypass'])->assertForbidden();
        $this->assertDatabaseHas('categories', ['id' => $article->category_id, 'title' => 'Klinik']);
    }

    public function test_admin_can_publish_but_cannot_manage_accounts_or_privileged_roles(): void
    {
        $admin = $this->user('admin');
        $record = $this->article();
        $this->actingAs($admin)->putJson('/admin/content/articles/'.$record->id, ['status' => 'published'])
            ->assertSuccessful();
        $this->get('/admin/users')->assertForbidden();
        $this->postJson('/admin/users', ['username' => 'intruder'])->assertForbidden();
        $this->get('/admin/roles')->assertForbidden();
        $this->postJson('/admin/roles', ['name' => 'intruder'])->assertForbidden();
        $this->assertDatabaseHas('articles', ['id' => $record->id, 'status' => 'published']);
    }

    public function test_module_limited_role_cannot_read_unrelated_drafts_settings_or_activity(): void
    {
        $role = Role::create(['name' => 'article-reader', 'guard_name' => 'web']);
        $role->syncPermissions(['admin.access', 'dashboard.view', 'articles.view']);
        $user = $this->user('article-reader');
        $this->article();
        SiteSetting::create(['group' => 'contact', 'values' => ['email' => 'private@example.test']]);
        $response = $this->actingAs($user)->getJson('/admin/content')->assertOk();
        $response->assertJsonCount(1, 'articles')->assertJsonCount(0, 'skin')
            ->assertJsonCount(0, 'shape')->assertJsonCount(0, 'doctors')->assertJsonCount(0, 'activity');
        $this->assertStringNotContainsString('private@example.test', $response->getContent());
        $this->get('/admin/skin')->assertForbidden();
        $this->get('/admin/pengaturan')->assertForbidden();
        $this->get('/admin/artikel')->assertOk();
    }

    public function test_revoking_permission_invalidates_access_for_already_authenticated_user(): void
    {
        $role = Role::create(['name' => 'temporary-editor', 'guard_name' => 'web']);
        $role->syncPermissions(['admin.access', 'dashboard.view', 'articles.view', 'articles.update']);
        $user = $this->user('temporary-editor');
        $record = $this->article();
        $this->actingAs($user)->putJson('/admin/content/articles/'.$record->id, ['title' => 'Allowed'])
            ->assertSuccessful();
        $role->revokePermissionTo('articles.update');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->unsetRelation('roles')->unsetRelation('permissions');
        $this->putJson('/admin/content/articles/'.$record->id, ['title' => 'Revoked'])->assertForbidden();
        $this->assertDatabaseHas('articles', ['id' => $record->id, 'title' => 'Allowed']);
    }

    public function test_super_admin_creates_real_hashed_account_and_assigned_role(): void
    {
        $super = $this->user('super-admin');
        $this->actingAs($super)->post('/admin/users', [
            'name' => 'New Editor', 'username' => 'new-editor', 'email' => 'new-editor@example.test',
            'role' => 'editor', 'is_active' => true,
            'password' => 'Secure-New-Editor-2026!', 'password_confirmation' => 'Secure-New-Editor-2026!',
        ])->assertRedirect();
        $created = User::where('username', 'new-editor')->firstOrFail();
        $this->assertTrue($created->hasRole('editor'));
        $this->assertTrue(Hash::check('Secure-New-Editor-2026!', $created->getRawOriginal('password')));
        $this->assertNotSame('Secure-New-Editor-2026!', $created->getRawOriginal('password'));
    }

    public function test_last_active_super_admin_cannot_be_demoted_or_suspended(): void
    {
        $super = $this->user('super-admin');
        $this->actingAs($super)->putJson('/admin/users/'.$super->id, $this->accountPayload($super, 'admin'))
            ->assertUnprocessable();
        $this->putJson('/admin/users/'.$super->id, $this->accountPayload($super, 'super-admin', false))
            ->assertUnprocessable();
        $this->assertTrue($super->fresh()->is_active);
        $this->assertTrue($super->fresh()->hasRole('super-admin'));
    }

    public function test_another_active_super_admin_allows_account_suspension_and_blocks_existing_session(): void
    {
        $actor = $this->user('super-admin');
        $target = $this->user('super-admin');
        $this->actingAs($actor)->put('/admin/users/'.$target->id, $this->accountPayload($target, 'viewer', false))
            ->assertRedirect();
        $this->assertFalse($target->fresh()->is_active);
        $this->assertTrue($target->fresh()->hasRole('viewer'));
        $this->actingAs($target->fresh())->getJson('/admin/content')->assertForbidden();
    }

    public function test_super_admin_role_is_immutable_and_governance_cannot_be_assigned_to_custom_roles(): void
    {
        $super = $this->user('super-admin');
        $role = Role::findByName('super-admin', 'web');
        $this->actingAs($super)->putJson('/admin/roles/'.$role->id, ['name' => 'renamed', 'permissions' => ['admin.access']])
            ->assertUnprocessable();
        $this->deleteJson('/admin/roles/'.$role->id)->assertUnprocessable();
        $this->postJson('/admin/roles', ['name' => 'escalated', 'permissions' => ['admin.access', 'users.manage', 'roles.manage']])
            ->assertUnprocessable();
        $this->assertDatabaseMissing('roles', ['name' => 'escalated']);
    }

    public function test_assigned_role_cannot_be_deleted_and_unknown_permissions_are_rejected(): void
    {
        $super = $this->user('super-admin');
        $this->user('editor');
        $role = Role::findByName('editor', 'web');
        $this->actingAs($super)->deleteJson('/admin/roles/'.$role->id)->assertUnprocessable();
        $this->postJson('/admin/roles', ['name' => 'invalid', 'permissions' => ['anything.root']])->assertUnprocessable();
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
        $this->assertDatabaseMissing('roles', ['name' => 'invalid']);
    }

    public function test_direct_management_permission_without_super_admin_role_does_not_escalate_access(): void
    {
        $user = $this->user('viewer');
        $user->givePermissionTo(['users.manage', 'roles.manage']);
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->get('/admin/roles')->assertForbidden();
        $this->postJson('/admin/users', ['username' => 'bypass'])->assertForbidden();
    }

    public function test_password_reset_and_role_change_use_database_values_on_next_login(): void
    {
        $super = $this->user('super-admin');
        $editor = $this->user('editor');
        $this->actingAs($super)->put('/admin/users/'.$editor->id, [
            ...$this->accountPayload($editor, 'viewer'),
            'password' => 'Changed-Password-2026!',
            'password_confirmation' => 'Changed-Password-2026!',
        ])->assertRedirect();
        $this->post('/admin/logout');
        $this->post('/admin/login', ['username' => $editor->username, 'password' => 'Actual-Password-2026!'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->post('/admin/login', ['username' => $editor->username, 'password' => 'Changed-Password-2026!'])
            ->assertRedirect('/admin');
        $this->assertAuthenticatedAs($editor->fresh());
        $this->postJson('/admin/content/articles', ['title' => 'Denied'])->assertForbidden();
    }

    public function test_custom_role_changes_are_persistent_and_not_reset_by_content_seed(): void
    {
        $super = $this->user('super-admin');
        $this->actingAs($super)->post('/admin/roles', [
            'name' => 'clinic-writer',
            'permissions' => ['admin.access', 'dashboard.view', 'articles.view', 'articles.create'],
        ])->assertRedirect();
        $role = Role::findByName('clinic-writer', 'web');
        $writer = $this->user('clinic-writer');
        $this->assertTrue($writer->can('articles.create'));
        $this->put('/admin/roles/'.$role->id, [
            'name' => 'clinic-writer',
            'permissions' => ['admin.access', 'dashboard.view', 'articles.view'],
        ])->assertRedirect();
        $this->seed();
        $this->assertTrue($writer->fresh()->hasRole('clinic-writer'));
        $this->assertFalse($writer->fresh()->can('articles.create'));
    }

    public function test_creating_an_account_never_implicitly_grants_super_admin(): void
    {
        $user = $this->user();
        $this->assertFalse($user->hasRole('super-admin'));
        $this->assertFalse($user->can('admin.access'));
    }

    public function test_cli_account_creation_assigns_explicit_role_and_hashes_secret(): void
    {
        $this->artisan('admin:create', ['--role' => 'editor'])
            ->expectsQuestion('Full name', 'Command Editor')
            ->expectsQuestion('Username', 'command-editor')
            ->expectsQuestion('Email (optional)', '')
            ->expectsQuestion('Password (12–72 characters, mixed case, numbers and symbols)', 'Command-Password-2026!')
            ->expectsQuestion('Confirm password', 'Command-Password-2026!')
            ->assertSuccessful();
        $user = User::where('username', 'command-editor')->firstOrFail();
        $this->assertTrue($user->hasRole('editor'));
        $this->assertFalse($user->hasRole('super-admin'));
        $this->assertTrue(Hash::check('Command-Password-2026!', $user->getRawOriginal('password')));
    }

    public function test_editor_cannot_publish_through_any_collection_endpoint(): void
    {
        $this->seed();
        $editor = $this->user('editor');
        $this->actingAs($editor);
        $collections = [
            'skin' => \App\Models\Treatment::where('service', 'skin')->firstOrFail(),
            'shape' => \App\Models\Treatment::where('service', 'shape')->firstOrFail(),
            'doctors' => \App\Models\Doctor::firstOrFail(),
            'articles' => Article::firstOrFail(),
            'services' => \App\Models\Service::firstOrFail(),
            'reviews' => \App\Models\Review::firstOrFail(),
        ];
        foreach (['home', 'skin', 'shape', 'doctors'] as $section) {
            $collections['hero-'.$section] = \App\Models\HeroSlide::where('section', $section)->firstOrFail();
        }
        foreach ($collections as $collection => $record) {
            $record->update(['status' => 'draft']);
            $this->putJson('/admin/content/'.$collection.'/'.$record->id, ['status' => 'published'])
                ->assertForbidden();
            $this->assertSame('draft', $record->fresh()->status);
            $record->update(['status' => 'published']);
            $this->putJson('/admin/content/'.$collection.'/'.$record->id, ['title' => 'Bypassed'])
                ->assertForbidden();
            $this->assertNotSame('Bypassed', $record->fresh()->title);
        }
    }

    public function test_login_lands_on_authorized_module_when_dashboard_is_not_allowed(): void
    {
        $role = Role::create(['name' => 'articles-only', 'guard_name' => 'web']);
        $role->syncPermissions(['admin.access', 'articles.view']);
        $user = $this->user('articles-only');
        $this->post('/admin/login', ['username' => $user->username, 'password' => 'Actual-Password-2026!'])
            ->assertRedirect('/admin/artikel');
        $this->get('/admin/artikel')->assertOk();
        $this->get('/admin')->assertForbidden();
    }
}
