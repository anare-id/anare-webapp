<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Doctor;
use App\Models\HeroSlide;
use App\Models\Review;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Support\LocalizedContent;
use Illuminate\Database\Eloquent\Model;

class WebsiteContent
{
    private ?array $settings = null;

    public function settings(): array
    {
        return $this->settings ??= SiteSetting::query()->get()->mapWithKeys(
            fn (SiteSetting $setting): array => [$setting->group => $setting->values ?? []],
        )->all();
    }

    public function setting(string $group, string $key, mixed $default = ''): mixed
    {
        return $this->settings()[$group][$key] ?? $default;
    }

    public function text(string $group, string $key): string
    {
        return LocalizedContent::field($this->settings()[$group] ?? [], $key);
    }

    public function localize(Model $record, array $fields): array
    {
        $item = \App\Support\OptimizedImages::record($record->toArray());
        foreach ($fields as $field) {
            $item[$field] = LocalizedContent::field($item, $field);
        }
        return $item;
    }

    public function categories(string $group): array
    {
        return Category::query()->where('group', $group)->orderBy('sort_order')->orderBy('id')->get()
            ->mapWithKeys(fn (Category $category): array => [$category->slug => LocalizedContent::field($category->toArray(), 'title')])->all();
    }

    public function heroes(string $section): array
    {
        return HeroSlide::published()->where('section', $section)->orderBy('sort_order')->orderBy('id')->get()
            ->map(function (HeroSlide $slide): array {
                $item = $this->localize($slide, ['title', 'description', 'button']);
                $item['alt'] = $item['title'];
                return $item;
            })->all();
    }

    public function doctor(Doctor $doctor): array
    {
        $item = $this->localize($doctor, ['title', 'description', 'specialty', 'schedule', 'education', 'expertise']);
        $item['name'] = $item['title'];
        $item['profile_image'] = $item['image'];
        $item['home_image'] = $item['home_image'] ?: $item['image'];
        $item['schedule_summary'] = $item['schedule'];
        $item['expertise'] = preg_split('/\R/u', $item['expertise'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return $item;
    }

    public function article(Article $article): array
    {
        $item = $this->localize($article, ['title', 'description', 'body']);
        $item['category_title'] = $article->category ? LocalizedContent::field($article->category->toArray(), 'title') : '';
        $item['category_slug'] = $article->category?->slug;
        $item['date'] = $article->date?->format('Y-m-d');
        return $item;
    }

    public function services(): array
    {
        return Service::published()->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (Service $service): array => $this->localize($service, ['title', 'description']))->all();
    }

    public function reviews(): array
    {
        $reviewsUrl = (string) $this->setting('contact', 'maps');
        return [
            'source' => 'cms', 'fetched_at' => null, 'reviews_url' => $reviewsUrl,
            'reviews' => Review::published()->orderBy('sort_order')->orderBy('id')->limit(2)->get()->map(function (Review $review) use ($reviewsUrl): array {
                $item = $this->localize($review, ['title', 'description']);
                $initials = collect(preg_split('/\s+/u', trim($item['title']), -1, PREG_SPLIT_NO_EMPTY))->take(2)
                    ->map(fn (string $name): string => mb_substr($name, 0, 1))->implode('');
                return [
                    'id' => (string) $review->id, 'rating' => (int) $review->rating,
                    'author' => $item['title'], 'text' => $item['description'], 'initials' => $initials,
                    'language' => app()->getLocale(), 'author_url' => $review->url ?: $reviewsUrl,
                    'review_url' => $review->url ?: $reviewsUrl,
                ];
            })->all(),
        ];
    }

    public function whatsappUrl(): string
    {
        $number = (string) $this->setting('contact', 'whatsapp');
        return $number === '' ? '' : 'https://wa.me/'.preg_replace('/\D/', '', $number);
    }

    public function socialProfiles(): array
    {
        $profiles = [];
        foreach ($this->settings()['social'] ?? [] as $key => $url) {
            if (!is_string($url) || $url === '' || str_ends_with($key, '_en')) {
                continue;
            }
            $handle = trim((string) parse_url($url, PHP_URL_PATH), '/');
            if (strcasecmp(ltrim($handle, '@'), 'anareskin.id') === 0) continue;
            $profiles[] = ['key' => $key, 'url' => $url, 'profile_url' => $url, 'label' => $this->text('contact', 'name'), 'handle' => $handle, 'username' => $handle];
        }
        return $profiles;
    }

    public function mapEmbedUrl(): string
    {
        $embed = (string) $this->setting('contact', 'map_embed');
        if ($embed !== '') return $this->localizedMapUrl($embed);

        $url = (string) $this->setting('contact', 'maps');
        if ($url === '') {
            return '';
        }
        if (str_contains($url, '/maps/embed') || str_contains($url, 'output=embed')) {
            return $this->localizedMapUrl($url);
        }
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $location = $query['query'] ?? $query['q'] ?? $this->text('contact', 'address');
        // The legacy iframe does not resolve q=place_id:... and falls back to a world map.
        if ($location === 'place_id:ChIJfTCB-Mv71y0RBeDF30u7ey8' || ($query['query_place_id'] ?? '') === 'ChIJfTCB-Mv71y0RBeDF30u7ey8' || str_contains($url, '0x2dd7fbcbf881307d:0x2f7bbb4bdfc5e005')) {
            return $this->localizedMapUrl('https://maps.google.com/maps?cid=3421534276469252101&ll=-7.3234692%2C112.776346&z=17&output=embed');
        }
        if (str_starts_with($location, 'place_id:')) $location = $this->text('contact', 'address');
        return 'https://maps.google.com/maps?'.http_build_query(['q' => $location ?: $url, 'z' => 17, 'output' => 'embed', 'hl' => app()->getLocale()]);
    }

    private function localizedMapUrl(string $url): string
    {
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);
        if (($query['q'] ?? '') === 'place_id:ChIJfTCB-Mv71y0RBeDF30u7ey8') {
            unset($query['q']);
            $query = array_replace($query, ['cid' => '3421534276469252101', 'll' => '-7.3234692,112.776346', 'z' => 17, 'output' => 'embed']);
        }
        $query['hl'] = app()->getLocale();
        return ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? 'maps.google.com').($parts['path'] ?? '/').'?'.http_build_query($query);
    }
}
