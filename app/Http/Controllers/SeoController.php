<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Doctor;
use App\Models\Treatment;
use App\Services\SeoContent;
use App\Services\WebsiteContent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class SeoController extends Controller
{
    public function sitemap(Request $request, SeoContent $seo, WebsiteContent $website): Response
    {
        $urls = [];
        $root = rtrim(config('app.url'), '/');
        if ($seo->indexingAllowed($request)) {
            $settings = $website->settings()['seo'] ?? [];
            foreach (['home' => '/', 'skin' => '/skin', 'shape' => '/shape', 'doctors' => '/doctors', 'articles' => '/articles'] as $key => $path) {
                if ($settings[$key.'_is_indexable'] ?? true) $urls[] = ['url' => $root.$path, 'updated' => null];
            }
            foreach ([Article::class => 'articles', Doctor::class => 'doctors', Treatment::class => null] as $model => $prefix) {
                foreach ($model::published()->where('is_indexable', true)->orderBy('id')->cursor() as $record) {
                    $urls[] = ['url' => $root.'/'.($prefix ?? $record->service).'/'.$record->slug, 'updated' => $record->updated_at->toAtomString()];
                }
            }
        }
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        foreach ($urls as $url) {
            $xml->startElement('url');
            $xml->writeElement('loc', $url['url']);
            if ($url['updated']) $xml->writeElement('lastmod', $url['updated']);
            $xml->endElement();
        }
        $xml->endElement();
        return response($xml->outputMemory(), 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'no-cache']);
    }

    public function robots(Request $request, SeoContent $seo): Response
    {
        $allowed = $seo->indexingAllowed($request);
        $text = "User-agent: *\n".($allowed ? "Disallow: /admin\n" : "Disallow:\n# This staging site sends noindex via X-Robots-Tag and HTML meta.\n");
        if ($allowed) $text .= 'Sitemap: '.rtrim(config('app.url'), '/')."/sitemap.xml\n";
        return response($text, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
