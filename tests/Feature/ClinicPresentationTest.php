<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Services\WebsiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClinicPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_uses_the_saved_rungkut_embed_and_localizes_it(): void
    {
        SiteSetting::create(['group' => 'contact', 'values' => [
            'maps' => 'https://www.google.com/maps/place/?q=place_id:ChIJfTCB-Mv71y0RBeDF30u7ey8',
            'map_embed' => 'https://maps.google.com/maps?cid=3421534276469252101&ll=-7.3234692%2C112.776346&z=17&output=embed',
        ]]);
        foreach (['id', 'en'] as $locale) {
            app()->setLocale($locale);
            $url = (new WebsiteContent())->mapEmbedUrl();
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $this->assertSame('3421534276469252101', $query['cid']);
            $this->assertSame('-7.3234692,112.776346', $query['ll']);
            $this->assertSame('17', $query['z']);
            $this->assertSame('embed', $query['output']);
            $this->assertSame($locale, $query['hl']);
        }
    }

    public function test_legacy_place_id_links_are_converted_instead_of_showing_the_world_map(): void
    {
        foreach ([
            ['maps' => 'https://www.google.com/maps/place/?q=place_id:ChIJfTCB-Mv71y0RBeDF30u7ey8'],
            ['map_embed' => 'https://maps.google.com/maps?q=place_id:ChIJfTCB-Mv71y0RBeDF30u7ey8&output=embed'],
        ] as $values) {
            SiteSetting::query()->delete();
            SiteSetting::create(['group' => 'contact', 'values' => $values]);
            parse_str((string) parse_url((new WebsiteContent())->mapEmbedUrl(), PHP_URL_QUERY), $query);
            $this->assertSame('3421534276469252101', $query['cid']);
            $this->assertSame('-7.3234692,112.776346', $query['ll']);
            $this->assertSame('17', $query['z']);
            $this->assertArrayNotHasKey('q', $query);
        }
    }

    public function test_data_correction_removes_only_the_nonexistent_social_account_and_preserves_contact_details(): void
    {
        $initialSettingsCount = SiteSetting::count();
        $contact = SiteSetting::create(['group' => 'contact', 'values' => [
            'name' => 'Anare Clinic', 'whatsapp' => '628113210288',
            'maps' => 'https://www.google.com/maps/place/?q=place_id:ChIJfTCB-Mv71y0RBeDF30u7ey8',
            'location_image' => '/images/anare-clinic-location.webp',
        ]]);
        $social = SiteSetting::create(['group' => 'social', 'values' => [
            'clinic' => 'https://www.instagram.com/anareclinic.id/',
            'shape' => 'https://www.instagram.com/anareshape.id/',
            'skin' => 'https://www.instagram.com/anareskin.id/',
        ]]);
        $this->assertCount(2, (new WebsiteContent())->socialProfiles());
        $migration = require database_path('migrations/2026_10_27_000001_focus_clinic_map_and_remove_unused_social.php');
        $migration->up();
        $migration->up();
        $this->assertSame('628113210288', $contact->fresh()->values['whatsapp']);
        $this->assertSame('/images/anare-clinic-location.webp', $contact->fresh()->values['location_image']);
        $this->assertStringContainsString('cid=3421534276469252101', $contact->fresh()->values['map_embed']);
        $this->assertSame(['clinic', 'shape'], array_keys($social->fresh()->values));
        $this->assertDatabaseCount('site_settings', $initialSettingsCount + 2);
    }
}
