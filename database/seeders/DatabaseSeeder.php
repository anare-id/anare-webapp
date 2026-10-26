<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\Doctor;
use App\Models\HeroSlide;
use App\Models\Review;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Treatment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Arr;

final class DatabaseSeeder extends Seeder
{
    private array $sources = [];

    public function run(): void
    {
        $this->call(CmsAccessSeeder::class);
        DB::transaction(function (): void {
            // This marker also preserves deliberate deletions when seeding again.
            if (SiteSetting::where('group', '_seed')->exists()) {
                return;
            }
            foreach (['skin', 'shape', 'doctors', 'hero'] as $source) {
                $this->sources[$source] = require __DIR__.'/data/'.$source.'.php';
            }
            foreach (['skin', 'shape'] as $service) {
                foreach ($this->source($service.'.categories') as $slug => $title) {
                    Category::firstOrCreate(['group' => $service, 'slug' => $slug], [
                        'title' => $this->text('categories.'.$slug, 'id', $title),
                        'title_en' => $this->text('categories.'.$slug, 'en', $title),
                        'sort_order' => array_search($slug, array_keys($this->source($service.'.categories')), true),
                    ]);
                }
                $items = $this->source($service.'.treatments') + $this->source($service.'.legacy_treatments', []);
                foreach ($items as $slug => $item) {
                    $fields = [
                        'category_id' => Category::where('group', $service)->where('slug', $item['category'])->firstOrFail()->id,
                        'image' => $this->image($item['image']),
                        'catalog_image' => $this->image($item['catalog_image'] ?? $item['image']),
                        'image_position' => $item['image_position'] ?? 'center',
                        'catalog_image_position' => $item['image_position'] ?? 'center',
                        'comparison_before' => $this->image($this->source($service.'.comparison.before')),
                        'comparison_after' => $this->image($this->source($service.'.comparison.after')),
                        'comparison_position' => $this->source($service.'.comparison.image_position', 'center'),
                        'comparison_illustrative' => $this->source($service.'.comparison.illustrative', true),
                        'legacy' => array_key_exists($slug, $this->source($service.'.legacy_treatments', [])),
                        'status' => 'published', 'sort_order' => array_search($slug, array_keys($items), true),
                    ];
                    foreach (['id' => '', 'en' => '_en'] as $locale => $suffix) {
                        foreach (['title' => 'name', 'subtitle' => 'subtitle', 'duration' => 'duration', 'description' => 'description'] as $field => $key) {
                            $fields[$field.$suffix] = $this->text('treatments.'.$service.'.'.$slug.'.'.$key, $locale, $item[$key] ?? '');
                        }
                        foreach (['pre', 'post', 'downtime'] as $condition) {
                            $fields[$condition.$suffix] = str_replace(':service', $this->source($service.'.name'), $this->text('treatment.conditions.'.$condition.'.body', $locale));
                        }
                    }
                    Treatment::firstOrCreate(['service' => $service, 'slug' => $slug], $fields);
                }
            }
            foreach (['skin' => 'Skin', 'beauty' => 'Beauty & Wellness'] as $slug => $title) {
                Category::firstOrCreate(['group' => 'articles', 'slug' => $slug], ['title' => $title, 'title_en' => $title, 'sort_order' => $slug === 'skin' ? 0 : 1]);
            }
            $homeOrder = array_flip($this->source('doctors.home_order'));
            foreach ($this->source('doctors.profiles') as $index => $doctor) {
                $fields = [
                    'title' => $doctor['name'], 'title_en' => $doctor['name'],
                    'image' => $this->image($doctor['profile_image']),
                    'home_image' => $this->image($doctor['home_image']), 'home_source' => $this->image($doctor['home_source']),
                    'home_image_width' => $this->source('doctors.home_image_width'), 'home_image_height' => $this->source('doctors.home_image_height'),
                    'schedule_image' => $this->image($doctor['schedule_image'] ?? ''),
                    'schedule_width' => $doctor['schedule_width'] ?? null, 'schedule_height' => $doctor['schedule_height'] ?? null,
                    'featured' => true, 'status' => !empty($doctor['is_dummy']) ? 'draft' : 'published',
                    'sort_order' => $index - 1, 'home_sort_order' => $homeOrder[$index] ?? $index,
                ];
                foreach (['id' => '', 'en' => '_en'] as $locale => $suffix) {
                    $fields['specialty'.$suffix] = $locale === 'en' ? 'Anare Clinic Doctor' : 'Dokter Anare Clinic';
                    $fields['description'.$suffix] = $this->text('doctors.profile_intro', $locale);
                    $fields['schedule'.$suffix] = $this->text('doctors.schedules.'.$index, $locale, $doctor['schedule_summary'] ?? '');
                    $fields['education'.$suffix] = $this->text('doctors.profiles.'.$index.'.education', $locale, $this->text('doctors.education_placeholder', $locale));
                    $fields['expertise'.$suffix] = $this->text('doctors.profiles.'.$index.'.expertise', $locale, $this->text('doctors.expertise_placeholder', $locale));
                }
                Doctor::firstOrCreate(['slug' => 'doctor-'.$index], $fields);
            }
            foreach (['article.JPG', 'IMG_0543.JPG', 'IMG_0645.JPG'] as $index => $image) {
                $fields = [
                    'category_id' => Category::where('group', 'articles')->where('slug', $index === 1 ? 'beauty' : 'skin')->firstOrFail()->id,
                    'image' => $this->image($image), 'author' => 'Anare Clinic', 'date' => '2026-09-30',
                    'featured' => $index === 0, 'status' => 'published', 'sort_order' => $index,
                ];
                foreach (['id' => '', 'en' => '_en'] as $locale => $suffix) {
                    $fields['title'.$suffix] = $this->text('articles.headline', $locale);
                    $fields['description'.$suffix] = $this->text('articles.intro', $locale);
                    $fields['body'.$suffix] = $this->text('articles.body', $locale);
                }
                Article::firstOrCreate(['slug' => 'article-'.($index + 1)], $fields);
            }
            foreach ($this->source('hero.slides') as $section => $slides) {
                foreach ($slides as $index => $slide) {
                    HeroSlide::firstOrCreate(['section' => $section, 'sort_order' => $index], [
                        'title' => $slide['alt'], 'title_en' => $slide['alt'],
                        'description' => $section === 'home' ? 'Lorem ipsum dolor sit amet, cupidatat mollit aute excepteur pariatur. Aliquip officia enim in velit fugiat dolore ut in irure cupidatat velit. Proident deserunt sunt dolore aliquip nostrud' : $this->source($section.'.hero_description', $this->text('doctors.intro', 'id')),
                        'description_en' => $section === 'home' ? 'Lorem ipsum dolor sit amet, cupidatat mollit aute excepteur pariatur. Aliquip officia enim in velit fugiat dolore ut in irure cupidatat velit. Proident deserunt sunt dolore aliquip nostrud' : ($section === 'doctors' ? $this->text('doctors.intro', 'en') : $this->source($section.'.hero_description', '')),
                        'image' => $this->image($slide['image']), 'mobile_image' => $this->image($slide['mobile_image'] ?? ''),
                        'position' => $slide['position'] ?? 'center', 'mobile_position' => $slide['mobile_position'] ?? null,
                        'team' => $slide['team'] ?? false, 'fit' => $slide['fit'] ?? 'cover', 'background' => $slide['background'] ?? null,
                        'button' => $this->text('common.book', 'id'), 'button_en' => $this->text('common.book', 'en'),
                        'url' => 'https://wa.me/628113210288', 'status' => 'published',
                    ]);
                }
            }
            foreach (['skin', 'shape'] as $index => $service) {
                Service::firstOrCreate(['url' => '/'.$service], [
                    'title' => $service === 'skin' ? 'Anare Clinic' : 'Anare Shape',
                    'title_en' => $service === 'skin' ? 'Anare Clinic' : 'Anare Shape',
                    'description' => $this->text('home.'.$service.'_description', 'id'),
                    'description_en' => $this->text('home.'.$service.'_description', 'en'),
                    'image' => $this->image('anare-'.$service.'.png'), 'status' => 'published', 'sort_order' => $index,
                ]);
            }
            $reviewSource = json_decode(file_get_contents(resource_path('data/google-reviews.json')), true, 512, JSON_THROW_ON_ERROR);
            foreach ($reviewSource['reviews'] as $index => $review) {
                Review::firstOrCreate(['title' => $review['author']], [
                    'title_en' => $review['author'], 'description' => $review['text'], 'description_en' => '',
                    'rating' => $review['rating'], 'url' => $review['author_url'] ?? $reviewSource['source_url'],
                    'status' => 'published', 'sort_order' => $index,
                ]);
            }
            foreach ($this->settings($reviewSource) as $group => $values) {
                SiteSetting::firstOrCreate(['group' => $group], ['values' => $values]);
            }
            SiteSetting::create(['group' => '_seed', 'values' => ['version' => 1]]);
        });
    }

    private function settings(array $reviewSource): array
    {
        $home = ['slide_interval' => (string) ($this->source('hero.interval') / 1000), 'controls_style' => $this->source('hero.controls_style')];
        foreach (['id' => '', 'en' => '_en'] as $locale => $suffix) {
            foreach (['services_title' => 'services', 'doctors_title' => 'doctors', 'doctors_description' => 'doctors_intro', 'doctors_invitation' => 'doctors_invitation', 'instagram_title' => 'instagram', 'reviews_title' => 'clients', 'location_title' => 'location'] as $field => $key) {
                $home[$field.$suffix] = $this->text('home.'.$key, $locale);
            }
        }
        return [
            'home' => $home,
            'contact' => [
                'name' => 'Anare Clinic', 'email' => '', 'whatsapp' => '628113210288',
                'address' => $this->text('footer.address', 'id'), 'address_en' => $this->text('footer.address', 'en'),
                'days' => $this->text('footer.days', 'id'), 'days_en' => $this->text('footer.days', 'en'),
                'hours' => '09.00 – 19.00 WIB', 'hours_en' => '09.00 – 19.00 WIB',
                'maps' => $reviewSource['source_url'], 'map_embed' => 'https://maps.google.com/maps?cid=3421534276469252101&ll=-7.3234692%2C112.776346&z=17&output=embed',
                'location_image' => '/images/anare-clinic-location.webp',
            ],
            'social' => ['clinic' => 'https://www.instagram.com/anareclinic.id/', 'shape' => 'https://www.instagram.com/anareshape.id/'],
            'skin' => $this->catalogSettings('skin'), 'shape' => $this->catalogSettings('shape'),
            'doctors' => ['title' => $this->text('doctors.hero', 'id'), 'title_en' => $this->text('doctors.hero', 'en'), 'description' => $this->text('doctors.intro', 'id'), 'description_en' => $this->text('doctors.intro', 'en')],
        ];
    }

    private function catalogSettings(string $service): array
    {
        return [
            'name' => $this->source($service.'.name'), 'name_en' => $this->source($service.'.name'),
            'title' => $this->text('catalog.'.$service.'.title', 'id', $this->source($service.'.hero_title')),
            'title_en' => $this->text('catalog.'.$service.'.title', 'en', $this->source($service.'.hero_title')),
            'description' => $this->source($service.'.hero_description'), 'description_en' => $this->source($service.'.hero_description'),
            'per_page' => (string) $this->source($service.'.per_page'), 'default_category' => $this->source($service.'.default_category'),
            'pagination_slots' => $this->source($service.'.pagination_slots'), 'show_pagination' => $this->source($service.'.show_pagination'),
        ];
    }

    private function source(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->sources, $key, $default);
    }

    private function text(string $key, string $locale, string $fallback = ''): string
    {
        if (!Lang::has('site.'.$key, $locale)) {
            return $fallback;
        }
        $value = Lang::get('site.'.$key, [], $locale);
        return is_array($value) ? implode("\n", $value) : (string) $value;
    }

    private function image(string $path): string
    {
        return $path === '' ? '' : '/images/'.$path;
    }
}
