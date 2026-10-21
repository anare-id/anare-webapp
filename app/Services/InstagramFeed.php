<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class InstagramFeed
{
    public function __construct(private readonly WebsiteContent $website)
    {
    }

    private function accounts(): array
    {
        $credentials = array_column(config('social.instagram.accounts', []), null, 'username');
        return array_map(fn (array $profile): array => [
            'username' => $profile['username'], 'profile_url' => $profile['url'],
            'access_token' => $credentials[$profile['username']]['access_token'] ?? '',
        ], $this->website->socialProfiles());
    }

    public function profiles(): array
    {
        return array_values(array_map(
            fn (array $account) => ['username' => $account['username'], 'profile_url' => $account['profile_url']],
            $this->accounts(),
        ));
    }

    // The initial page never waits on an external API.
    public function initial(): array
    {
        return $this->combined(false);
    }

    public function latest(): array
    {
        return $this->combined(true);
    }

    private function combined(bool $refresh): array
    {
        $snapshots = $this->snapshots();
        $buckets = [];
        foreach ($this->accounts() as $account) {
            $posts = $refresh ? $this->fetchAccount($account) : $this->cachedAccount($account);
            $buckets[] = $posts ?: ($snapshots[$account['username']] ?? []);
        }

        $limit = min(12, max(1, (int) config('social.instagram.limit', 6)));
        $posts = [];
        $seen = [];
        $rounds = $buckets ? max(array_map('count', $buckets)) : 0;
        for ($index = 0; $index < $rounds; $index++) {
            foreach ($buckets as $bucket) {
                $post = $bucket[$index] ?? null;
                if (!$post || isset($seen[$post['permalink']])) continue;
                $seen[$post['permalink']] = true;
                $posts[] = $post;
                if (count($posts) === $limit) return $posts;
            }
        }
        return $posts;
    }

    private function snapshots(): array
    {
        $path = config('social.instagram.snapshot_path');
        if (!is_string($path) || !File::isFile($path)) return [];
        $snapshot = json_decode(File::get($path), true);
        if (!is_array($snapshot)) return [];

        $allowed = array_column($this->profiles(), null, 'username');
        $accounts = [];
        foreach ($snapshot['accounts'] ?? [] as $account) {
            $username = $account['username'] ?? '';
            if (!isset($allowed[$username])) continue;
            foreach ($account['posts'] ?? [] as $post) {
                $file = $post['image'] ?? '';
                if (!is_string($file) || !preg_match('/^instagram-[A-Za-z0-9_-]+\.webp$/D', $file)
                    || !File::isFile(public_path('images/'.$file)) || !$this->instagramUrl($post['permalink'] ?? '')) continue;
                $accounts[$username][] = [
                    'id' => (string) $post['id'],
                    'image_url' => asset('images/'.$file),
                    'permalink' => $post['permalink'],
                    'caption' => Str::limit((string) ($post['caption'] ?? ''), 500),
                    'media_type' => $post['media_type'] ?? 'IMAGE',
                    'username' => $username,
                    'profile_url' => $allowed[$username]['profile_url'],
                    'source' => 'snapshot',
                    'fetched_at' => $snapshot['fetched_at'] ?? null,
                ];
            }
        }
        return $accounts;
    }

    private function token(array $account): string
    {
        $legacy = $account['username'] === config('social.instagram.username') ? config('social.instagram.access_token', '') : '';
        $token = trim((string) ($account['access_token'] ?? ''));
        return $token !== '' ? $token : trim((string) $legacy);
    }

    private function cacheKey(array $account): string
    {
        return 'instagram.feed.v2.'.hash('sha256', $account['username'].'|'.config('social.instagram.api_version').'|'.$this->token($account));
    }

    private function cachedAccount(array $account): array
    {
        if ($this->token($account) === '') return [];
        $cached = Cache::get($this->cacheKey($account), []);
        return is_array($cached['posts'] ?? null) ? $cached['posts'] : [];
    }

    private function fetchAccount(array $account): array
    {
        $token = $this->token($account);
        if ($token === '') return [];
        $key = $this->cacheKey($account);
        $cached = Cache::get($key, []);
        $stale = is_array($cached['posts'] ?? null) ? $cached['posts'] : [];
        if (($cached['refresh_after'] ?? 0) > now()->timestamp || Cache::has($key.'.retry')) return $stale;

        try {
            $client = Http::acceptJson()->withToken($token)->connectTimeout(3)->timeout(4);
            $base = 'https://graph.instagram.com/'.config('social.instagram.api_version', 'v22.0');
            $profile = $client->get($base.'/me', ['fields' => 'user_id,username']);
            $id = $profile->json('user_id') ?? $profile->json('id');
            $username = $profile->json('username');
            if (!$profile->successful() || (!is_string($id) && !is_int($id)) || !ctype_digit((string) $id)
                || !is_string($username) || strcasecmp($username, $account['username']) !== 0) return $this->unavailable($key, $stale);

            $response = $client->get($base.'/'.rawurlencode((string) $id).'/media', [
                'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
                'limit' => 6,
            ]);
            $data = $response->json('data');
            if (!$response->successful() || !is_array($data)) return $this->unavailable($key, $stale);
            $posts = [];
            foreach ($data as $item) {
                if (!is_array($item) || (!is_string($item['id'] ?? null) && !is_int($item['id'] ?? null))) continue;
                $type = $item['media_type'] ?? '';
                if (!in_array($type, ['IMAGE', 'VIDEO', 'CAROUSEL_ALBUM'], true)) continue;
                $image = $type === 'VIDEO' ? ($item['thumbnail_url'] ?? '') : ($item['media_url'] ?? '');
                if (!$this->httpsUrl($image) || !$this->instagramUrl($item['permalink'] ?? '')) continue;
                $posts[] = [
                    'id' => (string) $item['id'], 'image_url' => $image, 'permalink' => $item['permalink'],
                    'caption' => is_string($item['caption'] ?? null) ? Str::limit($item['caption'], 500) : '',
                    'media_type' => $type, 'username' => $account['username'], 'profile_url' => $account['profile_url'],
                    'source' => 'api', 'fetched_at' => now()->toIso8601String(),
                ];
                if (count($posts) === 6) break;
            }
            Cache::put($key, ['posts' => $posts, 'refresh_after' => now()->timestamp + max(60, (int) config('social.instagram.cache_seconds', 900))], now()->addDay());
            Cache::forget($key.'.retry');
            return $posts;
        } catch (ConnectionException) {
            return $this->unavailable($key, $stale);
        }
    }

    private function unavailable(string $key, array $stale): array
    {
        Cache::put($key.'.retry', true, now()->addSeconds(90));
        return $stale;
    }

    private function instagramUrl(mixed $url): bool
    {
        return $this->httpsUrl($url) && (bool) preg_match('/(^|\.)instagram\.com$/i', (string) parse_url($url, PHP_URL_HOST));
    }

    private function httpsUrl(mixed $url): bool
    {
        return is_string($url) && filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https';
    }
}
