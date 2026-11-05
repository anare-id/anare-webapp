<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Treatment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TreatmentComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_comparison_displays_both_treatment_photos(): void
    {
        $treatment = $this->treatment(['show_comparison' => true]);

        $this->get(route('skin.detail', $treatment->slug))->assertOk()
            ->assertSee('class="skin-comparison-section"', false)
            ->assertSee('comparison-before-test.jpg')
            ->assertSee('comparison-after-test.jpg');
    }

    public function test_disabled_comparison_keeps_photos_but_hides_the_section(): void
    {
        $treatment = $this->treatment(['show_comparison' => false]);

        $this->get(route('skin.detail', $treatment->slug))->assertOk()
            ->assertDontSee('class="skin-comparison-section"', false)
            ->assertDontSee('comparison-before-test.jpg')
            ->assertDontSee('comparison-after-test.jpg');
        $this->assertSame('/images/comparison-before-test.jpg', $treatment->fresh()->comparison_before);
    }

    public function test_enabled_comparison_without_both_photos_has_no_fallback(): void
    {
        foreach ([null, '', '   '] as $missing) {
            foreach (['comparison_before', 'comparison_after'] as $field) {
                $treatment = $this->treatment(['show_comparison' => true, $field => $missing]);

                $this->get(route('skin.detail', $treatment->slug))->assertOk()
                    ->assertDontSee('class="skin-comparison-section"', false)
                    ->assertDontSee('comparison-before-test.jpg')
                    ->assertDontSee('comparison-after-test.jpg');
            }
        }
    }

    public function test_comparison_visibility_is_independent_per_treatment(): void
    {
        $enabled = $this->treatment(['show_comparison' => true]);
        $disabled = $this->treatment();
        $this->assertFalse($disabled->fresh()->show_comparison);

        $this->get(route('skin.detail', $enabled->slug))->assertOk()
            ->assertSee('class="skin-comparison-section"', false);
        $this->get(route('skin.detail', $disabled->slug))->assertOk()
            ->assertDontSee('class="skin-comparison-section"', false);

        $disabled->update(['show_comparison' => true]);
        $enabled->update(['show_comparison' => false]);
        $this->get(route('skin.detail', $enabled->slug))->assertOk()
            ->assertDontSee('class="skin-comparison-section"', false);
        $this->get(route('skin.detail', $disabled->slug))->assertOk()
            ->assertSee('class="skin-comparison-section"', false);
    }

    public function test_migration_backfills_only_complete_existing_photo_pairs(): void
    {
        $complete = $this->treatment();
        $missing = $this->treatment(['comparison_before' => null]);
        $blank = $this->treatment(['comparison_after' => '   ']);
        $migration = require database_path('migrations/2026_11_05_000002_add_show_comparison_to_treatments.php');
        $migration->down();
        $migration->up();

        $this->assertTrue($complete->fresh()->show_comparison);
        $this->assertFalse($missing->fresh()->show_comparison);
        $this->assertFalse($blank->fresh()->show_comparison);
        $this->assertFalse($this->treatment()->fresh()->show_comparison);
    }

    private function treatment(array $attributes = []): Treatment
    {
        $category = Category::firstOrCreate(
            ['group' => 'skin', 'slug' => 'comparison-tests'],
            ['title' => 'Comparison tests'],
        );

        return Treatment::create(array_merge([
            'service' => 'skin',
            'category_id' => $category->id,
            'slug' => 'comparison-test-'.(Treatment::count() + 1),
            'title' => 'Comparison treatment',
            'status' => 'published',
            'comparison_before' => '/images/comparison-before-test.jpg',
            'comparison_after' => '/images/comparison-after-test.jpg',
        ], $attributes));
    }
}
