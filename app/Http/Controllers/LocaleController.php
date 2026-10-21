<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('app.supported_locales')), 404);
        $request->session()->put('locale', $locale);

        $return = $request->input('return_to', '/');
        // Only a same-site relative path can become the redirect destination.
        $decoded = is_string($return) ? rawurldecode($return) : '';
        if (!is_string($return) || strlen($return) > 4096 || !str_starts_with($return, '/')
            || str_starts_with($decoded, '//') || preg_match('/[\x00-\x1f\x7f\\\\]/', $decoded)
            || parse_url($return, PHP_URL_HOST) !== null || parse_url($return, PHP_URL_SCHEME) !== null) $return = '/';

        return redirect()->to($return, 303)->withCookie(cookie('site_locale', $locale, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
    }
}
