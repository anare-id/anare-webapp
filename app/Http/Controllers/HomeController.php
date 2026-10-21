<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Doctor;
use App\Services\InstagramFeed;
use App\Services\WebsiteContent;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(InstagramFeed $feed, WebsiteContent $website): View
    {
        return view('pages.home', [
            'instagramPosts' => $feed->initial(), 'instagramAccounts' => $website->socialProfiles(),
            'reviewFeed' => $website->reviews(), 'heroSlides' => $website->heroes('home'),
            'services' => $website->services(),
            'featuredDoctors' => Doctor::published()->where('featured', true)->orderBy('home_sort_order')->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn (Doctor $doctor): array => $website->doctor($doctor))->all(),
            'featuredArticles' => Article::published()->with('category')->where('featured', true)->orderBy('sort_order')->orderByDesc('date')->orderBy('id')->get()
                ->map(fn (Article $article): array => $website->article($article))->all(),
        ]);
    }
}
