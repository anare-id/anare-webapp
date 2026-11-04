<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SeoContent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SearchIndexing
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (!app(SeoContent::class)->indexingAllowed($request) || $request->is('admin', 'admin/*') || $response->getStatusCode() >= 400) {
            $response->headers->set('X-Robots-Tag', 'noindex, follow');
        }
        return $response;
    }
}
