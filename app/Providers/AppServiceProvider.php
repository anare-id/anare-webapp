<?php

namespace App\Providers;

use App\Services\WebsiteContent;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as BladeView;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(WebsiteContent::class);
    }

    public function boot(): void
    {
        View::composer(['layouts.app', 'pages.*', 'partials.*'], function (BladeView $view): void {
            $website = app(WebsiteContent::class);
            $view->with([
                'website' => $website,
                'siteSettings' => $website->settings(),
                'whatsappUrl' => $website->whatsappUrl(),
                'socialProfiles' => $website->socialProfiles(),
                'heroInterval' => max(1, (int) $website->setting('home', 'slide_interval', 5)) * 1000,
            ]);
        });
    }
}
