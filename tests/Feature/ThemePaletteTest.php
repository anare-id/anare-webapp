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

}
