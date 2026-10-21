<?php

namespace App\Http\Controllers;

use App\Services\GoogleReviews;
use Illuminate\Http\JsonResponse;

class GoogleReviewsController extends Controller
{
    public function __invoke(GoogleReviews $reviews): JsonResponse
    {
        $feed = $reviews->latest();

        return response()->json([
            'mode' => $feed['reviews'] ? 'reviews' : 'empty',
            'source' => $feed['source'], 'fetched_at' => $feed['fetched_at'],
            'count' => count($feed['reviews']),
            'html' => view('partials.google-reviews', ['reviewFeed' => $feed])->render(),
        ])->header('Cache-Control', 'no-store, private');
    }
}
