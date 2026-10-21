<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('app.supported_locales', ['id' => 'Bahasa Indonesia', 'en' => 'English']));
        $locale = $request->session()->get('locale');
        if (!is_string($locale) || !in_array($locale, $supported, true)) $locale = $request->cookie('site_locale');
        if (!is_string($locale) || !in_array($locale, $supported, true)) $locale = config('app.default_locale', 'en');
        App::setLocale(in_array($locale, $supported, true) ? $locale : 'en');

        return $next($request);
    }
}
