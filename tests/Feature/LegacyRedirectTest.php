<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class LegacyRedirectTest extends TestCase
{
    public function test_former_wordpress_urls_redirect_permanently_to_matching_pages(): void
    {
        $this->get('/acne/')->assertStatus(301)->assertRedirect(url('/skin?category=acne'));
        $this->get('/our-doctors')->assertStatus(301)->assertRedirect(url('/doctors'));
        $this->get('/category/anare-clinic/skin-tightening/')->assertStatus(301)->assertRedirect(url('/shape?category=tightening'));
        $this->get('/tag/sunscreen/')->assertStatus(301)->assertRedirect(url('/articles'));
        $this->get('/wp-sitemap-posts-page-1.xml')->assertStatus(301)->assertRedirect(url('/sitemap.xml'));
    }

    public function test_unmapped_paths_are_not_redirected(): void
    {
        $this->get('/acne-injection')->assertNotFound();
        $this->get('/tag/a/b')->assertNotFound();
        $this->get('/wp-login.php')->assertNotFound();
    }
}
