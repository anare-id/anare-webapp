<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;

final class SeoContent
{
    public function __construct(private readonly WebsiteContent $website) {}

    public static function defaults(): array
    {
        $values = ['google_verification' => ''];
        foreach (config('seo.pages') as $key => $label) {
            foreach (['seo_title', 'seo_title_en', 'meta_description', 'meta_description_en'] as $field) $values[$key.'_'.$field] = '';
            $values[$key.'_is_indexable'] = true;
        }
        return $values;
    }

    public function indexingAllowed(Request $request): bool
    {
        return config('seo.allow_indexing') && !preg_match('/^(?:dev|staging|localhost)(?:\.|$)/i', $request->getHost());
    }

    public function page(Request $request, array $data): array
    {
        $settings = $this->website->settings()['seo'] ?? [];
        $route = $request->route()?->getName() ?? '';
        $record = match ($route) {
            'articles.detail' => $data['article'] ?? null,
            'doctors.detail' => $data['doctor'] ?? null,
            'skin.detail', 'shape.detail' => $data['treatment'] ?? null,
            default => null,
        };
        $key = $route === 'home' ? 'home' : explode('.', $route)[0];
        $english = app()->getLocale() === 'en';
        $field = static function (array $values, string $name) use ($english): string {
            return trim((string) (($english && !empty($values[$name.'_en'])) ? $values[$name.'_en'] : ($values[$name] ?? '')));
        };
        $meta = $record ?? [];
        if ($record === null) {
            foreach (['seo_title', 'seo_title_en', 'meta_description', 'meta_description_en', 'is_indexable'] as $name) $meta[$name] = $settings[$key.'_'.$name] ?? ($name === 'is_indexable' ? true : '');
            $contentGroup = $key === 'articles' ? 'home' : ($key === 'home' ? 'contact' : $key);
            $meta['description'] = $this->website->text($contentGroup, $key === 'home' ? 'address' : 'description');
        }
        $title = $field($meta, 'seo_title');
        $description = $field($meta, 'meta_description') ?: strip_tags($field($meta, 'description'));
        $indexed = $this->indexingAllowed($request) && ($meta['is_indexable'] ?? true);
        $query = $record === null ? $request->only(['category', 'page']) : [];
        $canonical = rtrim(config('app.url'), '/').$request->getPathInfo().($query ? '?'.http_build_query($query) : '');
        return ['title' => $title, 'description' => $description, 'robots' => $indexed ? 'index, follow' : 'noindex, follow', 'canonical' => $canonical, 'verification' => $settings['google_verification'] ?? ''];
    }
}
