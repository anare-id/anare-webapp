<?php

namespace App\Http\Controllers;

use App\Services\InstagramFeed;
use Illuminate\Http\JsonResponse;

class InstagramFeedController extends Controller
{
    public function __invoke(InstagramFeed $feed): JsonResponse
    {
        $posts = $feed->latest();

        return response()->json([
            'mode' => $posts === [] ? 'unavailable' : 'feed',
            'accounts' => $feed->profiles(),
            'posts' => $posts,
        ]);
    }
}
