<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\ThemePalette;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ThemePaletteTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        $user = User::create(['name' => 'Theme Editor', 'username' => $role, 'password' => 'Theme-Password-2026!']);
        $user->assignRole($role);
        return $user;
    }

    public function test_admin_can_save_palette_and_public_pages_receive_separate_theme_colors(): void
    {
        $values = [...ThemePalette::defaults(), 'skin_accent' => '#123456', 'shape_accent' => '#654321', 'home_button' => '#224466'];
        $this->actingAs($this->account('admin'))->putJson('/admin/settings/theme', $values)->assertOk()
            ->assertJsonPath('state.settings.theme.skin_accent', '#123456');
        $this->assertSame('#123456', SiteSetting::where('group', 'theme')->firstOrFail()->values['skin_accent']);
        $this->get('/')->assertOk()->assertSee('--button:#224466', false);
        $this->get('/skin')->assertOk()->assertSee('body.skin.treatment-page{--accent:#123456', false);
        $this->get('/shape')->assertOk()->assertSee('body.shape.treatment-page{--accent:#654321', false);
    }

    public function test_invalid_color_cannot_inject_css_or_partially_change_saved_palette(): void
    {
        $values = ThemePalette::defaults();
        SiteSetting::create(['group' => 'theme', 'values' => $values]);
        $this->actingAs($this->account('admin'))->putJson('/admin/settings/theme', [
            ...$values, 'skin_accent' => '</style><script>alert(1)</script>', 'home_button' => '#112233',
        ])->assertUnprocessable()->assertJsonValidationErrors('skin_accent');
        $this->assertSame($values, SiteSetting::where('group', 'theme')->firstOrFail()->values);
        $this->assertStringNotContainsString('<script>', ThemePalette::css(['skin_accent' => '</style><script>']));
    }

    public function test_viewer_can_read_palette_but_cannot_change_it_and_editor_cannot_read_settings(): void
    {
        $this->actingAs($this->account('viewer'))->getJson('/admin/content')->assertOk()
            ->assertJsonStructure(['settings' => ['theme' => ['shared_footer']]]);
        $this->putJson('/admin/settings/theme', ThemePalette::defaults())->assertForbidden();
        $this->actingAs($this->account('editor'))->getJson('/admin/content')->assertOk()->assertJsonMissingPath('settings.theme');
    }

    public function test_anare_palette_covers_home_doctors_and_articles_and_keeps_brand_scopes_separate(): void
    {
        $this->seed();
        $values = [...ThemePalette::defaults(), 'home_ink' => '#112233', 'home_accent' => '#446688', 'home_canvas' => '#faf4ee', 'shared_footer_text' => '#223344', 'skin_accent' => '#123456', 'shape_accent' => '#654321'];
        $this->actingAs($this->account('admin'))->putJson('/admin/settings/theme', $values)->assertOk();
        foreach (['/', '/doctors', '/doctors/doctor-1', '/articles', '/articles/article-1'] as $path) {
            $this->get($path)->assertOk()->assertSee('body:not(.treatment-page){--ink:#112233', false)
                ->assertSee('--canvas:#faf4ee', false)->assertSee('--footer-text:#223344', false);
        }
        $this->get('/skin')->assertOk()->assertSee('body.skin.treatment-page{--accent:#123456', false);
        $this->get('/shape')->assertOk()->assertSee('body.shape.treatment-page{--accent:#654321', false);
        $this->getJson('/admin/content')->assertOk()->assertJsonPath('settings.theme.home_accent', '#446688')
            ->assertJsonPath('theme_sections.anare.label', 'Anare');
        $this->assertStringNotContainsString('body.skin{', ThemePalette::css($values));
        $this->assertStringNotContainsString('body.shape{', ThemePalette::css($values));
    }

    public function test_preset_palettes_cover_every_configured_color_and_legacy_values_are_preserved(): void
    {
        $defaults = ThemePalette::defaults();
        foreach (ThemePalette::presets() as $preset) {
            $this->assertSame(array_keys($defaults), array_keys($preset['values']));
            foreach ($preset['values'] as $color) $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/D', $color);
        }
        $resolved = ThemePalette::resolve(['home_button' => '#123ABC', 'shape_accent' => '#ab1234']);
        $this->assertSame('#123abc', $resolved['home_button']);
        $this->assertSame('#ab1234', $resolved['shape_accent']);
        $this->assertSame($defaults['home_canvas'], $resolved['home_canvas']);
        $this->assertCount(3, config('theme.sections'));
    }

    public function test_invalid_hex_formats_do_not_change_the_saved_theme(): void
    {
        $values = ThemePalette::defaults();
        SiteSetting::create(['group' => 'theme', 'values' => $values]);
        $this->actingAs($this->account('admin'));
        foreach (['#fff', '123456', '#gg0000', 'red', 'rgb(1,2,3)'] as $invalid) {
            $this->putJson('/admin/settings/theme', [...$values, 'shape_accent' => $invalid])
                ->assertUnprocessable()->assertJsonValidationErrors('shape_accent');
            $this->assertSame($values, SiteSetting::where('group', 'theme')->firstOrFail()->values);
        }
    }
}
