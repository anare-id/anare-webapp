<?php

namespace App\Http\Controllers;

use App\Services\InstagramFeed;
use App\Services\GoogleReviews;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(InstagramFeed $feed, GoogleReviews $reviews): View
    {
        return view('pages.home', ['instagramPosts' => $feed->initial(), 'instagramAccounts' => $feed->profiles(), 'reviewFeed' => $reviews->initial()]);
    }
}
