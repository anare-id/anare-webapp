<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class CrudFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private function login(): void
    {
        $user = User::create(['name' => 'Feedback Admin', 'username' => 'feedback-admin', 'password' => 'Feedback-Password-2026!']);
        $user->assignRole('super-admin');
        $this->actingAs($user);
    }

    public function test_content_mutations_return_action_specific_success_messages(): void
    {
        $this->login();
        Category::create(['group' => 'articles', 'slug' => 'skin', 'title' => 'Skin']);
        $this->postJson('/admin/content/articles', [
            'title' => 'Feedback Article', 'slug' => 'feedback-article', 'category' => 'skin', 'status' => 'draft',
        ])->assertSuccessful()->assertJsonPath('message', 'Artikel berhasil ditambahkan.')->assertJsonStructure(['state']);
        $article = Article::firstOrFail();
        $this->putJson('/admin/content/articles/'.$article->id, ['title' => 'Updated Feedback'])
            ->assertOk()->assertJsonPath('message', 'Artikel berhasil diperbarui.');
        $this->postJson('/admin/content/articles/'.$article->id.'/duplicate')
            ->assertSuccessful()->assertJsonPath('message', 'Salinan Artikel berhasil dibuat sebagai draft.');
        $duplicate = Article::whereKeyNot($article->id)->firstOrFail();
        $this->deleteJson('/admin/content/articles/'.$duplicate->id)
            ->assertOk()->assertJsonPath('message', 'Artikel berhasil dihapus.');
        $this->assertDatabaseMissing('articles', ['id' => $duplicate->id]);
    }

    public function test_categories_settings_and_uploads_return_success_messages(): void
    {
        $this->login();
        $this->postJson('/admin/categories/skin', ['title' => 'Feedback', 'slug' => 'feedback'])
            ->assertSuccessful()->assertJsonPath('message', 'Kategori berhasil ditambahkan.');
        $category = Category::firstOrFail();
        $this->putJson('/admin/categories/skin/'.$category->id, ['title' => 'Updated Category', 'slug' => 'feedback'])
            ->assertOk()->assertJsonPath('message', 'Kategori berhasil diperbarui.');
        $this->deleteJson('/admin/categories/skin/'.$category->id)
            ->assertOk()->assertJsonPath('message', 'Kategori berhasil dihapus.');
        $this->putJson('/admin/settings/contact', ['name' => 'Feedback Clinic'])
            ->assertOk()->assertJsonPath('message', 'Pengaturan berhasil disimpan.');
        Storage::fake('public');
        $this->post('/admin/uploads', ['image' => UploadedFile::fake()->image('feedback.jpg')], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('message', 'Gambar berhasil diunggah.')->assertJsonStructure(['url']);
    }

    public function test_account_and_role_json_actions_include_feedback_and_a_redirect(): void
    {
        $this->login();
        $this->postJson('/admin/users', [
            'name' => 'New Account', 'username' => 'new-account', 'email' => '', 'role' => 'viewer', 'is_active' => true,
            'password' => 'Account-Password-2026!', 'password_confirmation' => 'Account-Password-2026!',
        ])->assertCreated()->assertJsonPath('message', 'Akun administrator berhasil dibuat.')
            ->assertJsonPath('redirect', route('admin.users.index'))
            ->assertSessionHas('status', 'Akun administrator berhasil dibuat.');
        $user = User::where('username', 'new-account')->firstOrFail();
        $this->putJson('/admin/users/'.$user->id, [
            'name' => 'Updated Account', 'username' => $user->username, 'email' => '', 'role' => 'editor', 'is_active' => true,
        ])->assertOk()->assertJsonPath('message', 'Akun administrator berhasil diperbarui.');
        $this->postJson('/admin/roles', ['name' => 'feedback-role', 'permissions' => ['admin.access', 'dashboard.view']])
            ->assertCreated()->assertJsonPath('message', 'Role berhasil dibuat.')
            ->assertJsonPath('redirect', route('admin.roles.index'))->assertSessionHas('status', 'Role berhasil dibuat.');
        $role = Role::where('name', 'feedback-role')->firstOrFail();
        $this->putJson('/admin/roles/'.$role->id, ['name' => 'updated-feedback-role', 'permissions' => ['admin.access']])
            ->assertOk()->assertJsonPath('message', 'Role berhasil diperbarui.');
        $this->putJson('/admin/permissions/'.$role->id, ['permissions' => ['admin.access', 'articles.view'], 'permissions_present' => 1])
            ->assertOk()->assertJsonPath('message', 'Hak akses role berhasil diperbarui.')
            ->assertJsonPath('redirect', route('admin.permissions.index', ['role' => $role->id]));
        $this->deleteJson('/admin/roles/'.$role->id)
            ->assertOk()->assertJsonPath('message', 'Role berhasil dihapus.')->assertSessionHas('status', 'Role berhasil dihapus.');
    }

    public function test_failed_requests_return_errors_without_a_success_message_or_partial_write(): void
    {
        $this->login();
        $this->postJson('/admin/content/articles', ['title' => 'Invalid Article', 'status' => 'draft'])
            ->assertUnprocessable()->assertJsonValidationErrors(['slug', 'category_id'])->assertJsonMissingPath('state');
        $this->assertDatabaseCount('articles', 0);
        $this->postJson('/admin/roles', ['name' => 'super-admin', 'permissions' => ['admin.access']])
            ->assertUnprocessable()->assertJsonStructure(['errors']);
        $viewer = User::create(['name' => 'Viewer', 'username' => 'feedback-viewer', 'password' => 'Viewer-Password-2026!']);
        $viewer->assignRole('viewer');
        $this->actingAs($viewer)->postJson('/admin/content/articles', ['title' => 'Forbidden'])
            ->assertForbidden()->assertJsonMissingPath('state');
        $this->assertDatabaseCount('articles', 0);
    }
}
