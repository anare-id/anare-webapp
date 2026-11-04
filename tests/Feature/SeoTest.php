<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\ArticleHtml;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::create(['name' => 'SEO Editor', 'username' => 'seo-editor', 'password' => 'SEO-Password-2026!']);
        $user->assignRole('admin');
        return $user;
    }

    private function article(array $attributes = []): Article
    {
        $category = Category::create(['group' => 'articles', 'slug' => 'clinic', 'title' => 'Clinic']);
        return Article::create(['category_id' => $category->id, 'slug' => 'seo-guide', 'title' => 'Visible Article Title', 'description' => 'Visible summary.', 'body' => 'Original body.', 'status' => 'published', ...$attributes]);
    }

    public function test_independent_metadata_does_not_replace_visible_title_summary_or_alt(): void
    {
        $article = $this->article();
        $this->actingAs($this->admin())->putJson('/admin/content/articles/'.$article->id, [
            'seo_title' => 'Search Optimized Title', 'meta_description' => 'Search optimized description.',
            'image' => '/images/article.JPG', 'image_alt' => 'Doctor explaining treatment options',
        ])->assertOk();
        $this->get('/articles/seo-guide')->assertOk()
            ->assertSee('<title>Search Optimized Title</title>', false)
            ->assertSee('<meta name="description" content="Search optimized description.">', false)
            ->assertSee('<h1>Visible Article Title</h1>', false)
            ->assertSee('Visible summary.')->assertSee('alt="Doctor explaining treatment options"', false);
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'title' => 'Visible Article Title', 'description' => 'Visible summary.']);
    }

    public function test_metadata_falls_back_to_visible_content_and_localized_values(): void
    {
        $this->article(['title_en' => 'English title', 'description_en' => 'English summary.', 'seo_title_en' => 'English SEO']);
        $this->withSession(['locale' => 'en'])->get('/articles/seo-guide')->assertOk()
            ->assertSee('<title>English SEO</title>', false)
            ->assertSee('<meta name="description" content="English summary.">', false);
        $this->withSession(['locale' => 'id'])->get('/articles/seo-guide')->assertOk()
            ->assertSee('Visible Article Title |', false)->assertSee('content="Visible summary."', false);
    }

    public function test_sanitizer_preserves_editor_features_but_removes_script_links_and_remote_images(): void
    {
        $html = '<h2>Section</h2><h3>Detail</h3><p><strong>Bold</strong><a href="/skin/acne-injection">Internal</a><a href="javascript:alert(1)">Unsafe</a></p><ul><li>Bullet</li></ul><img src="/images/article.JPG" alt="Clinical consultation"><img src="https://evil.test/pixel.png" onerror="alert(2)"><script>alert(3)</script><iframe src="https://evil.test"></iframe>';
        $clean = app(ArticleHtml::class)->sanitize($html);
        $this->assertStringContainsString('<h2>Section</h2>', $clean);
        $this->assertStringContainsString('<h3>Detail</h3>', $clean);
        $this->assertStringContainsString('<strong>Bold</strong>', $clean);
        $this->assertStringContainsString('href="/skin/acne-injection"', $clean);
        $this->assertStringContainsString('<ul><li>Bullet</li></ul>', $clean);
        $this->assertStringContainsString('alt="Clinical consultation"', $clean);
        foreach (['javascript:', 'onerror', '<script', '<iframe', 'evil.test'] as $unsafe) $this->assertStringNotContainsString($unsafe, $clean);
    }

    public function test_rich_body_is_sanitized_on_save_and_render_while_legacy_text_remains_text(): void
    {
        $article = $this->article(['body' => '<strong>Literal old content</strong>']);
        $this->get('/articles/seo-guide')->assertSee('&lt;strong&gt;Literal old content&lt;/strong&gt;', false);
        $this->actingAs($this->admin())->putJson('/admin/content/articles/'.$article->id, ['body_format' => 'html', 'body' => '<h2>New section</h2><script>bad()</script><p>Safe</p>'])->assertOk();
        $this->assertStringNotContainsString('<script', $article->fresh()->body);
        $this->get('/articles/seo-guide')->assertSee('<h2>New section</h2>', false)->assertDontSee('<script>bad()', false);
    }

    public function test_converting_one_language_preserves_escaped_legacy_translation(): void
    {
        $article = $this->article(['body_en' => "Literal <strong>English</strong>\nSecond line"]);
        $this->actingAs($this->admin())->putJson('/admin/content/articles/'.$article->id, [
            'body_format' => 'html', 'body' => '<h2>Bagian baru</h2>',
        ])->assertOk();
        $this->withSession(['locale' => 'en'])->get('/articles/seo-guide')->assertOk()
            ->assertSee('Literal &lt;strong&gt;English&lt;/strong&gt;', false)
            ->assertDontSee('<strong>English</strong>', false);
    }

    public function test_article_listing_uses_page_metadata_and_detail_drops_listing_query(): void
    {
        $this->article(['seo_title' => 'Record SEO title']);
        SiteSetting::create(['group' => 'seo', 'values' => ['articles_seo_title' => 'Article listing SEO']]);
        $this->get('/articles')->assertOk()->assertSee('<title>Article listing SEO</title>', false);
        $this->get('/articles/seo-guide?category=clinic&page=2&utm_source=campaign')->assertOk()
            ->assertSee('<link rel="canonical" href="'.rtrim(config('app.url'), '/').'/articles/seo-guide">', false);
    }

    public function test_staging_is_noindex_even_when_record_and_global_flag_allow_indexing(): void
    {
        config(['seo.allow_indexing' => true]);
        $this->article(['is_indexable' => true]);
        $this->get('https://dev.anare.co.id/articles/seo-guide')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, follow')->assertSee('content="noindex, follow"', false);
        $this->get('https://dev.anare.co.id/sitemap.xml')->assertOk()->assertDontSee('<loc>', false);
        $this->get('https://dev.anare.co.id/robots.txt')->assertOk()->assertDontSee('Disallow: /', false);
    }

    public function test_sitemap_excludes_draft_noindex_and_admin_urls_and_updates_after_changes(): void
    {
        config(['seo.allow_indexing' => true, 'app.url' => 'https://anare.co.id']);
        $article = $this->article();
        Article::create(['category_id' => $article->category_id, 'slug' => 'draft', 'title' => 'Draft', 'status' => 'draft']);
        Article::create(['category_id' => $article->category_id, 'slug' => 'hidden', 'title' => 'Hidden', 'status' => 'published', 'is_indexable' => false]);
        SiteSetting::create(['group' => 'seo', 'values' => ['shape_is_indexable' => false]]);
        $xml = $this->get('https://anare.co.id/sitemap.xml')->assertOk()->getContent();
        $this->assertNotFalse(simplexml_load_string($xml));
        $this->assertStringContainsString('https://anare.co.id/articles/seo-guide', $xml);
        foreach (['/articles/draft', '/articles/hidden', '/admin', '/shape</loc>'] as $excluded) $this->assertStringNotContainsString($excluded, $xml);
        $article->update(['is_indexable' => false]);
        $this->get('https://anare.co.id/sitemap.xml')->assertDontSee('/articles/seo-guide');
        $this->get('https://anare.co.id/articles/seo-guide')->assertOk()->assertSee('content="noindex, follow"', false);
    }

    public function test_page_seo_verification_and_indexing_are_saved_with_settings_permissions(): void
    {
        config(['seo.allow_indexing' => true]);
        $this->actingAs($this->admin())->putJson('/admin/settings/seo', [
            'home_seo_title' => 'Clinic SEO Home', 'home_meta_description' => 'Custom home description',
            'home_is_indexable' => false, 'google_verification' => 'valid_google_token-123',
        ])->assertOk();
        $this->get('/')->assertOk()->assertSee('<title>Clinic SEO Home</title>', false)
            ->assertSee('name="google-site-verification" content="valid_google_token-123"', false)
            ->assertSee('content="noindex, follow"', false);
        $this->putJson('/admin/settings/seo', ['google_verification' => '<script>'])->assertUnprocessable();
        $viewer = User::create(['name' => 'Viewer', 'username' => 'viewer', 'password' => 'Viewer-Password-2026!']);
        $viewer->assignRole('viewer');
        $this->actingAs($viewer)->putJson('/admin/settings/seo', ['home_is_indexable' => true])->assertForbidden();
    }
}
