<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LegacyRedirectController extends Controller
{
    public static function pattern(): string
    {
        return implode('|', array_map(
            static fn (string $path): string => str_replace('\*', '[^/]+', preg_quote($path, '#')),
            array_keys(config('legacy-redirects')),
        ));
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $path = trim($request->path(), '/');
        foreach (config('legacy-redirects') as $legacy => $target) {
            if (preg_match('#^'.str_replace('\*', '[^/]+', preg_quote($legacy, '#')).'$#D', $path)) {
                return redirect($target, 301);
            }
        }
        abort(404);
    }
}
