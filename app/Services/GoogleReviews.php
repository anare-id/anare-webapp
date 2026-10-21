<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleReviews
{
    // Rendering Home never waits for a provider request.
    public function initial(): array
    {
        return $this->cached() ?? $this->snapshot();
    }

    public function latest(): array
    {
        $key = $this->cacheKey();
        $cached = $this->cached();
        $fallback = $cached ?? $this->snapshot();
        $token = trim((string) config('reviews.google.api_key'));
        if ($token === '' || ($cached['refresh_after'] ?? 0) > now()->timestamp
            || Cache::has($key.'.retry') || !Cache::add($key.'.lock', true, 30)) return $fallback;

        try {
            $response = Http::acceptJson()->withHeaders([
                'X-Goog-Api-Key' => $token,
                'X-Goog-FieldMask' => 'id,displayName,googleMapsUri,reviews',
            ])->connectTimeout(3)->timeout(5)->get('https://places.googleapis.com/v1/places/'.rawurlencode(config('reviews.google.place_id')), [
                'languageCode' => in_array(app()->getLocale(), ['id', 'en'], true) ? app()->getLocale() : 'en',
            ]);

            if (!$response->successful() || $response->json('id') !== config('reviews.google.place_id')
                || !is_array($response->json('reviews', []))) return $this->unavailable($key, $fallback);

            $items = [];
            foreach ($response->json('reviews', []) as $review) {
                if (!is_array($review)) continue;
                $original = is_array($review['originalText'] ?? null) ? $review['originalText'] : [];
                $translated = is_array($review['text'] ?? null) ? $review['text'] : [];
                $author = is_array($review['authorAttribution'] ?? null) ? $review['authorAttribution'] : [];
                $items[] = [
                    'id' => $review['name'] ?? '', 'rating' => $review['rating'] ?? 0,
                    'author' => $author['displayName'] ?? '', 'author_url' => $author['uri'] ?? '',
                    'text' => $original['text'] ?? $translated['text'] ?? '',
                    'language' => $original['languageCode'] ?? $translated['languageCode'] ?? '',
                    'review_url' => $review['googleMapsUri'] ?? '',
                ];
            }
            $payload = $this->payload($this->normalize($items), 'api', now()->toIso8601String());
            $payload['refresh_after'] = now()->timestamp + max(60, (int) config('reviews.google.cache_seconds', 21600));
            Cache::put($key, $payload, now()->addDay());
            Cache::forget($key.'.retry');

            return $payload;
        } catch (ConnectionException) {
            return $this->unavailable($key, $fallback);
        } finally {
            Cache::forget($key.'.lock');
        }
    }

    private function snapshot(): array
    {
        $path = config('reviews.google.snapshot_path');
        if (!is_string($path) || !File::isFile($path)) return $this->payload([], 'empty');
        $snapshot = json_decode(File::get($path), true);
        if (!is_array($snapshot) || ($snapshot['place_id'] ?? null) !== config('reviews.google.place_id')) return $this->payload([], 'empty');

        return $this->payload($this->normalize(is_array($snapshot['reviews'] ?? null) ? $snapshot['reviews'] : []), 'snapshot', $snapshot['fetched_at'] ?? null);
    }

    private function normalize(array $items): array
    {
        $reviews = [];
        $seen = [];
        foreach ($items as $item) {
            if (!is_array($item) || !is_numeric($item['rating'] ?? null) || (float) $item['rating'] !== 5.0
                || !is_string($item['author'] ?? null) || trim($item['author']) === ''
                || !is_string($item['text'] ?? null) || trim($item['text']) === '') continue;
            $id = is_string($item['id'] ?? null) && $item['id'] !== '' ? $item['id'] : hash('sha256', $item['author'].'|'.$item['text']);
            if (isset($seen[$id])) continue;
            $seen[$id] = true;
            $initials = collect(preg_split('/\s+/u', trim($item['author'])))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
            $reviews[] = [
                'id' => Str::limit($id, 500, ''), 'author' => Str::limit(trim($item['author']), 160, ''),
                'initials' => $initials, 'rating' => 5, 'text' => Str::limit(trim($item['text']), 10000, ''),
                'language' => is_string($item['language'] ?? null) && preg_match('/^[a-z]{2,3}(-[A-Za-z0-9]+)*$/D', $item['language']) ? $item['language'] : 'id',
                'author_url' => $this->googleUrl($item['author_url'] ?? '') ? $item['author_url'] : config('reviews.google.reviews_url'),
                'review_url' => $this->googleUrl($item['review_url'] ?? '') ? $item['review_url'] : config('reviews.google.reviews_url'),
            ];
            if (count($reviews) >= min(2, max(1, (int) config('reviews.google.limit', 2)))) break;
        }

        return $reviews;
    }

    private function payload(array $reviews, string $source, ?string $fetchedAt = null): array
    {
        return ['reviews' => $reviews, 'source' => $source, 'fetched_at' => $fetchedAt,
            'place_id' => config('reviews.google.place_id'), 'place_name' => config('reviews.google.place_name'),
            'reviews_url' => config('reviews.google.reviews_url')];
    }

    private function cached(): ?array
    {
        if (trim((string) config('reviews.google.api_key')) === '') return null;
        $cached = Cache::get($this->cacheKey());
        if (!is_array($cached) || ($cached['place_id'] ?? null) !== config('reviews.google.place_id') || !is_array($cached['reviews'] ?? null)) return null;
        $cached['reviews'] = $this->normalize($cached['reviews']);

        return $cached;
    }

    private function cacheKey(): string
    {
        return 'google.reviews.v1.'.hash('sha256', config('reviews.google.place_id').'|'.app()->getLocale().'|'.config('reviews.google.api_key'));
    }

    private function unavailable(string $key, array $fallback): array
    {
        Cache::put($key.'.retry', true, now()->addMinutes(5));

        return $fallback;
    }

    private function googleUrl(mixed $url): bool
    {
        return is_string($url) && filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https'
            && (bool) preg_match('/(^|\.)(google\.com|maps\.app\.goo\.gl)$/i', (string) parse_url($url, PHP_URL_HOST));
    }
}
