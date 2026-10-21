<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Doctor;
use App\Models\HeroSlide;
use App\Models\Review;
use App\Models\Service;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CmsTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        $user = User::query()->create([
            'name' => 'Clinic Editor',
            'username' => 'editor',
            'email' => 'editor@example.test',
            'password' => Hash::make('Unique-Editor-Password-2026'),
        ]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function category(string $group = 'skin', string $slug = 'care'): Category
    {
        return Category::query()->create([
            'group' => $group,
            'slug' => $slug,
            'title' => 'Perawatan',
            'title_en' => 'Care',
            'sort_order' => 0,
        ]);
    }

    private function treatmentPayload(string $slug = 'clinical-care'): array
    {
        return [
            'title' => 'Perawatan Klinik',
            'title_en' => 'Clinical Care',
            'description' => 'Informasi perawatan dari dokter.',
            'description_en' => 'Treatment information from the doctor.',
            'slug' => $slug,
            'category' => 'care',
            'image' => '/images/treatment3.JPG',
            'status' => 'published',
            'duration' => '30 menit',
            'pre' => 'Konsultasi sebelum tindakan.',
            'post' => 'Ikuti petunjuk dokter.',
            'downtime' => 'Sesuai evaluasi dokter.',
        ];
    }

    public function test_guests_cannot_read_or_mutate_admin_content(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->getJson('/admin/content')->assertUnauthorized();
        $this->postJson('/admin/content/skin', $this->treatmentPayload())->assertUnauthorized();
        $this->putJson('/admin/settings/contact', ['name' => 'Intruder'])->assertUnauthorized();
        $this->post('/admin/uploads', [], ['Accept' => 'application/json'])->assertUnauthorized();
        $this->assertDatabaseCount('treatments', 0);
    }

    public function test_login_checks_database_hash_instead_of_demo_credentials(): void
    {
        $user = $this->administrator();
        $this->post('/admin/login', ['username' => 'admin', 'password' => 'anare123'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->post('/admin/login', ['username' => 'editor', 'password' => 'Unique-Editor-Password-2026'])
            ->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
        $this->assertNotSame('Unique-Editor-Password-2026', $user->getRawOriginal('password'));
    }

    public function test_saved_treatment_is_published_and_draft_update_hides_it(): void
    {
        $this->category();
        $this->actingAs($this->administrator());
        $payload = $this->treatmentPayload();
        $this->postJson('/admin/content/skin', $payload)->assertSuccessful();
        $treatment = Treatment::query()->where('slug', 'clinical-care')->firstOrFail();
        $this->withSession(['locale' => 'id'])->get('/skin/clinical-care')
            ->assertOk()->assertSee('Perawatan Klinik')->assertSee('Konsultasi sebelum tindakan.');
        $this->putJson('/admin/content/skin/'.$treatment->id, [...$payload, 'status' => 'draft'])
            ->assertSuccessful();
        $this->get('/skin/clinical-care')->assertNotFound();
        $this->get('/skin?category=care')->assertOk()->assertDontSee('Perawatan Klinik');
    }

    public function test_treatment_slug_unique_within_service_but_not_between_services(): void
    {
        $this->category();
        $this->category('shape');
        $this->actingAs($this->administrator());
        $this->postJson('/admin/content/skin', $this->treatmentPayload())->assertSuccessful();
        $this->postJson('/admin/content/skin', $this->treatmentPayload())
            ->assertUnprocessable()->assertJsonValidationErrors('slug');
        $this->postJson('/admin/content/shape', $this->treatmentPayload())->assertSuccessful();
        $this->assertDatabaseHas('treatments', ['service' => 'skin', 'slug' => 'clinical-care']);
        $this->assertDatabaseHas('treatments', ['service' => 'shape', 'slug' => 'clinical-care']);
    }

    public function test_category_must_belong_to_the_treatment_service(): void
    {
        $this->category('shape', 'body');
        $this->actingAs($this->administrator())
            ->postJson('/admin/content/skin', [...$this->treatmentPayload(), 'category' => 'body'])
            ->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $this->assertDatabaseCount('treatments', 0);
    }

    public function test_delete_cannot_target_record_from_another_collection(): void
    {
        $this->category('shape');
        $this->actingAs($this->administrator());
        $this->postJson('/admin/content/shape', $this->treatmentPayload())->assertSuccessful();
        $record = Treatment::query()->firstOrFail();
        $this->deleteJson('/admin/content/skin/'.$record->id)->assertNotFound();
        $this->assertDatabaseHas('treatments', ['id' => $record->id]);
    }

    public function test_duplicate_gets_a_unique_slug_and_stays_unpublished(): void
    {
        $this->category();
        $this->actingAs($this->administrator());
        $this->postJson('/admin/content/skin', $this->treatmentPayload())->assertSuccessful();
        $original = Treatment::query()->firstOrFail();
        $this->postJson('/admin/content/skin/'.$original->id.'/duplicate')->assertSuccessful();
        $copy = Treatment::query()->where('id', '!=', $original->id)->firstOrFail();
        $this->assertNotSame($original->slug, $copy->slug);
        $this->assertSame('draft', $copy->status);
        $this->get('/skin/'.$copy->slug)->assertNotFound();
        $this->assertDatabaseHas('treatments', ['id' => $original->id, 'status' => 'published']);
    }

    public function test_category_cannot_be_deleted_while_referenced(): void
    {
        $category = $this->category();
        $this->actingAs($this->administrator());
        $this->postJson('/admin/content/skin', $this->treatmentPayload())->assertSuccessful();
        $this->deleteJson('/admin/categories/skin/'.$category->id)->assertUnprocessable();
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->get('/skin/clinical-care')->assertOk();
    }

    public function test_category_rename_preserves_treatment_relationship(): void
    {
        $category = $this->category();
        $this->actingAs($this->administrator());
        $this->postJson('/admin/content/skin', $this->treatmentPayload())->assertSuccessful();
        $this->putJson('/admin/categories/skin/'.$category->id, [
            'slug' => 'clinical', 'title' => 'Perawatan Medis', 'title_en' => 'Clinical Treatments',
        ])->assertSuccessful();
        $this->withSession(['locale' => 'en'])->get('/skin?category=clinical')
            ->assertOk()->assertSee('Clinical Care')->assertSee('Clinical Treatments');
        $this->assertSame($category->id, Treatment::query()->firstOrFail()->category_id);
    }

    public function test_unsafe_links_and_embedded_data_images_are_rejected(): void
    {
        $this->actingAs($this->administrator());
        $this->postJson('/admin/content/services', [
            'title' => 'Unsafe', 'status' => 'published',
            'image' => 'data:image/png;base64,AAAA', 'url' => 'javascript:alert(1)',
        ])->assertUnprocessable()->assertJsonValidationErrors(['image', 'url']);
        $this->assertDatabaseCount('services', 0);
    }

    public function test_real_image_upload_is_stored_and_invalid_files_are_rejected(): void
    {
        Storage::fake('public');
        $this->actingAs($this->administrator());
        $response = $this->post('/admin/uploads', ['image' => UploadedFile::fake()->image('clinic.png')], ['Accept' => 'application/json']);
        $response->assertSuccessful();
        $url = $response->json('url');
        $this->assertStringStartsWith('/storage/', $url);
        Storage::disk('public')->assertExists(substr($url, strlen('/storage/')));
        $this->post('/admin/uploads', ['image' => UploadedFile::fake()->create('script.php', 1, 'text/x-php')], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->post('/admin/uploads', ['image' => UploadedFile::fake()->image('large.jpg')->size(2049)], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('image');
    }

    public function test_contact_settings_reach_public_footer_and_are_not_executable_html(): void
    {
        $this->actingAs($this->administrator());
        $this->putJson('/admin/settings/contact', [
            'name' => 'Anare Database Clinic', 'email' => 'clinic@example.test',
            'whatsapp' => '6281234567890', 'days' => 'Senin–Jumat', 'hours' => '10.00–18.00',
            'address' => 'Alamat klinik dari database <script>alert(1)</script>',
            'maps' => 'https://maps.google.com/?q=Surabaya',
        ])->assertSuccessful();
        $this->withSession(['locale' => 'id'])->get('/')
            ->assertOk()->assertSee('Anare Database Clinic')->assertSee('6281234567890')
            ->assertSee('Alamat klinik dari database &lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->assertDatabaseHas('site_settings', ['group' => 'contact']);
    }

    public function test_published_article_reads_bilingual_content_and_unknown_slugs_are_404(): void
    {
        $this->category('articles', 'education');
        $this->actingAs($this->administrator());
        $this->postJson('/admin/content/articles', [
            'slug' => 'aftercare-guide', 'category' => 'education', 'title' => 'Panduan Perawatan',
            'title_en' => 'Aftercare Guide', 'description' => 'Ringkasan klinik.',
            'description_en' => 'Clinic summary.', 'body' => 'Petunjuk dokter dalam Bahasa Indonesia.',
            'body_en' => 'Instructions from the treating doctor.', 'image' => '/images/article.JPG',
            'date' => '2026-10-01', 'author' => 'Clinic Team', 'featured' => true, 'status' => 'published',
        ])->assertSuccessful();
        $this->withSession(['locale' => 'en'])->get('/articles/aftercare-guide')
            ->assertOk()->assertSee('Aftercare Guide')->assertSee('Instructions from the treating doctor.');
        $this->withSession(['locale' => 'id'])->get('/articles/aftercare-guide')
            ->assertOk()->assertSee('Panduan Perawatan')->assertSee('Petunjuk dokter dalam Bahasa Indonesia.');
        $this->get('/articles/not-in-database')->assertNotFound();
        $article = Article::query()->firstOrFail();
        $article->update(['status' => 'draft']);
        $this->get('/articles/aftercare-guide')->assertNotFound();
        $this->get('/articles')->assertOk()->assertDontSee('Panduan Perawatan');
    }

    public function test_doctors_use_arbitrary_slugs_and_featured_flag_controls_home(): void
    {
        $this->actingAs($this->administrator());
        $this->postJson('/admin/content/doctors', [
            'slug' => 'dr-sari', 'title' => 'dr. Sari', 'title_en' => 'Dr Sari',
            'description' => 'Dokter spesialis kulit.', 'description_en' => 'Dermatologist.',
            'image' => '/images/home-dokter1-normalized.png',
            'home_image' => '/images/home-dokter1-normalized.png',
            'specialty' => 'Spesialis kulit', 'schedule' => 'Senin 10.00',
            'education' => 'Pendidikan dokter.', 'expertise' => 'Konsultasi kulit',
            'featured' => true, 'status' => 'published',
        ])->assertSuccessful();
        $this->withSession(['locale' => 'id'])->get('/doctors/dr-sari')->assertOk()->assertSee('dr. Sari');
        $this->get('/')->assertOk()->assertSee('dr. Sari');
        Doctor::query()->firstOrFail()->update(['featured' => false]);
        $this->get('/')->assertOk()->assertDontSee('dr. Sari');
        $this->get('/doctors')->assertOk()->assertSee('dr. Sari');
        Doctor::query()->firstOrFail()->update(['status' => 'draft']);
        $this->get('/doctors/dr-sari')->assertNotFound();
    }

    public function test_manual_reviews_and_service_cards_are_authoritative_on_home(): void
    {
        $this->actingAs($this->administrator());
        $this->postJson('/admin/content/reviews', [
            'title' => 'Pasien Klinik', 'description' => 'Ulasan terkelola dari database.',
            'rating' => 4, 'status' => 'published',
        ])->assertSuccessful();
        $this->postJson('/admin/content/services', [
            'title' => 'Konsultasi Klinik', 'title_en' => 'Clinic Consultation',
            'description' => 'Layanan dari panel.', 'image' => '/images/anare-skin.png',
            'url' => '/skin', 'status' => 'published',
        ])->assertSuccessful();
        $this->withSession(['locale' => 'id'])->get('/')
            ->assertOk()->assertSee('Ulasan terkelola dari database.')->assertSee('Konsultasi Klinik');
        $this->getJson('/google/reviews')->assertOk()->assertSee('Ulasan terkelola dari database.');
        Review::query()->firstOrFail()->update(['status' => 'draft']);
        Service::query()->firstOrFail()->update(['status' => 'draft']);
        $this->get('/')->assertOk()->assertDontSee('Ulasan terkelola dari database.')->assertDontSee('Konsultasi Klinik');
        $this->getJson('/google/reviews')->assertOk()->assertDontSee('Ulasan terkelola dari database.');
    }

    public function test_hero_publication_and_bilingual_copy_are_read_from_database(): void
    {
        $this->actingAs($this->administrator());
        $this->postJson('/admin/content/hero-home', [
            'title' => 'Pesan Klinik', 'title_en' => 'Clinic Message',
            'description' => 'Deskripsi hero tersimpan.', 'description_en' => 'Saved hero copy.',
            'image' => '/images/hero.jpg', 'position' => 'center',
            'button' => 'Hubungi Klinik', 'button_en' => 'Contact Clinic',
            'url' => '/skin', 'status' => 'published',
        ])->assertSuccessful();
        $this->withSession(['locale' => 'en'])->get('/')
            ->assertOk()->assertSee('Saved hero copy.')->assertSee('Contact Clinic');
        HeroSlide::query()->firstOrFail()->update(['status' => 'draft']);
        $this->get('/')->assertOk()->assertDontSee('Saved hero copy.');
    }

    public function test_invalid_settings_do_not_partially_persist(): void
    {
        $this->actingAs($this->administrator());
        $this->putJson('/admin/settings/home', [
            'services_title' => 'Layanan', 'doctors_title' => 'Dokter',
            'instagram_title' => 'Instagram', 'reviews_title' => 'Ulasan', 'slide_interval' => 0,
        ])->assertUnprocessable()->assertJsonValidationErrors('slide_interval');
        $this->assertDatabaseMissing('site_settings', ['group' => 'home']);
    }

    public function test_empty_database_renders_public_pages_without_seed_fallbacks(): void
    {
        foreach (['/', '/skin', '/shape', '/doctors', '/articles'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->get('/doctors/doctor-1')->assertNotFound();
        $this->get('/skin/acne-injection')->assertNotFound();
        $this->get('/articles/article-1')->assertNotFound();
    }

    public function test_reseeding_preserves_editor_changes_and_deliberate_deletions(): void
    {
        $this->seed();
        $edited = Treatment::query()->where('service', 'skin')->firstOrFail();
        $edited->update(['title' => 'Judul hasil editor', 'status' => 'draft']);
        $deleted = Article::query()->firstOrFail();
        $deletedSlug = $deleted->slug;
        $deleted->delete();
        $this->seed();
        $this->assertDatabaseHas('treatments', ['id' => $edited->id, 'title' => 'Judul hasil editor', 'status' => 'draft']);
        $this->assertDatabaseMissing('articles', ['slug' => $deletedSlug]);
        $this->assertDatabaseCount('users', 0);
    }
}
