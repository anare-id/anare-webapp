<?php

namespace App\Support;

class AdminDemoContent
{
    public static function make(): array
    {
        $data = ['skin' => [], 'shape' => [], 'doctors' => [], 'articles' => [], 'heroes' => [], 'categories' => []];
        foreach (['skin', 'shape'] as $service) {
            $data['categories'][$service] = config($service.'.categories');
            foreach (config($service.'.treatments') as $slug => $item) {
                $data[$service][] = [
                    'id' => $slug, 'slug' => $slug,
                    'title' => __('site.treatments.'.$service.'.'.$slug.'.name', [], 'id'),
                    'title_en' => $item['name'], 'category' => $item['category'],
                    'image' => asset('images/'.($item['catalog_image'] ?? $item['image'])),
                    'description' => $slug === 'acne-injection' ? $item['description'] : '',
                    'description_en' => $slug === 'acne-injection' ? __('site.treatments.skin.acne-injection.description', [], 'en') : '',
                    'duration' => $item['duration'] ?? '', 'pre' => '', 'post' => '', 'downtime' => '',
                    'status' => 'published', 'updated_at' => '2026-10-20T08:00:00+07:00',
                ];
            }
        }
        foreach (config('doctors.profiles') as $index => $doctor) {
            $data['doctors'][] = [
                'id' => 'doctor-'.$index, 'slug' => 'doctor-'.$index,
                'title' => $doctor['name'], 'title_en' => $doctor['name'],
                'image' => asset('images/'.$doctor['profile_image']),
                'schedule_image' => isset($doctor['schedule_image']) ? asset('images/'.$doctor['schedule_image']) : '',
                'specialty' => 'Dokter Anare Clinic', 'schedule' => $doctor['schedule_summary'] ?? '',
                'description' => '', 'description_en' => '', 'education' => '', 'expertise' => '',
                'featured' => true, 'status' => !empty($doctor['is_dummy']) ? 'draft' : 'published',
                'updated_at' => '2026-10-20T08:00:00+07:00',
            ];
        }
        foreach (['article.JPG', 'IMG_0543.JPG', 'IMG_0645.JPG'] as $index => $image) {
            $data['articles'][] = [
                'id' => 'article-'.($index + 1), 'slug' => 'article-'.($index + 1),
                'title' => 'Judul Artikel '.($index + 1), 'title_en' => 'Article’s Title '.($index + 1),
                'image' => asset('images/'.$image), 'category' => $index === 1 ? 'beauty' : 'skin',
                'description' => 'Konten artikel contoh untuk website Anare Clinic. Lengkapi cerita, informasi perawatan, dan foto sebelum dipublikasikan.',
                'description_en' => 'Example article content for the Anare Clinic website.',
                'author' => 'Anare Clinic', 'date' => '2026-09-30', 'featured' => $index === 0,
                'status' => 'published', 'updated_at' => '2026-10-20T08:00:00+07:00',
            ];
        }
        foreach (config('hero.slides') as $section => $slides) {
            $data['heroes'][$section] = array_map(fn ($slide, $index) => [
                'id' => $section.'-slide-'.$index, 'title' => $slide['alt'], 'title_en' => $slide['alt'],
                'image' => asset('images/'.$slide['image']), 'description' => $section === 'home' ? 'Lorem ipsum dolor sit amet, cupidatat mollit aute excepteur pariatur.' : config($section.'.hero_description', ''),
                'description_en' => '', 'position' => $slide['position'] ?? 'center',
                'button' => 'Buat Janji', 'url' => 'https://wa.me/628113210288', 'status' => 'published',
            ], $slides, array_keys($slides));
        }
        $data['categories']['articles'] = ['skin' => 'Skin', 'beauty' => 'Beauty & Wellness'];
        $data['settings'] = [
            'home' => ['services_title' => 'Layanan Kami', 'doctors_title' => 'DOKTER KAMI', 'doctors_description' => __('site.home.doctors_intro', [], 'id'), 'instagram_title' => 'Instagram Kami', 'reviews_title' => 'Ulasan Klien Kami', 'slide_interval' => '5'],
            'contact' => ['name' => 'Anare Clinic', 'email' => 'hello@anareclinic.example', 'whatsapp' => '628113210288', 'address' => __('site.footer.address', [], 'id'), 'days' => 'Senin – Sabtu', 'hours' => '09.00 – 19.00 WIB', 'maps' => 'https://www.google.com/maps/search/?api=1&query=Anare+Clinic+Surabaya'],
            'social' => ['clinic' => 'https://www.instagram.com/anareclinic.id/', 'shape' => 'https://www.instagram.com/anareshape.id/', 'skin' => 'https://www.instagram.com/anareskin.id/'],
            'skin' => ['title' => __('site.catalog.skin.title', [], 'id'), 'title_en' => __('site.catalog.skin.title', [], 'en'), 'description' => config('skin.hero_description'), 'per_page' => '6'],
            'shape' => ['title' => config('shape.hero_title'), 'title_en' => config('shape.hero_title'), 'description' => config('shape.hero_description'), 'per_page' => '6'],
        ];
        $data['services'] = [
            ['id' => 'service-skin', 'title' => 'Anare Clinic', 'title_en' => 'Anare Clinic', 'description' => __('site.home.skin_description', [], 'id'), 'description_en' => __('site.home.skin_description', [], 'en'), 'image' => asset('images/anare-skin.png'), 'url' => '/skin', 'status' => 'published'],
            ['id' => 'service-shape', 'title' => 'Anare Shape', 'title_en' => 'Anare Shape', 'description' => __('site.home.shape_description', [], 'id'), 'description_en' => __('site.home.shape_description', [], 'en'), 'image' => asset('images/anare-shape.png'), 'url' => '/shape', 'status' => 'published'],
        ];
        $data['reviews'] = array_map(fn ($review) => [
            'id' => $review['id'], 'title' => $review['author'], 'title_en' => $review['author'],
            'description' => $review['text'], 'description_en' => '',
            'rating' => '5', 'status' => 'published',
        ], app(\App\Services\GoogleReviews::class)->initial()['reviews']);
        $data['activity'] = [];

        return $data;
    }
}
