<?php

namespace App\Http\Controllers;

use App\Services\WebsiteContent;
use Illuminate\Http\JsonResponse;

class GoogleReviewsController extends Controller
{
    public function __invoke(WebsiteContent $website): JsonResponse
    {
        $feed = $website->reviews();

        return response()->json([
            'mode' => $feed['reviews'] ? 'reviews' : 'empty',
            'source' => $feed['source'], 'fetched_at' => $feed['fetched_at'],
            'count' => count($feed['reviews']),
            'html' => view('partials.google-reviews', ['reviewFeed' => $feed])->render(),
        ])->header('Cache-Control', 'no-store, private');
    }
}
