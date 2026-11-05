<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use HTMLPurifier;
use HTMLPurifier_Config;

final class ArticleHtml
{
    public function sanitize(string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'p,br,h1,h2,h3,strong,b,em,i,u,s,ul,ol,li,a[href|title],img[src|alt|width|height],blockquote');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        $clean = (new HTMLPurifier($config))->purify($html);
        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="article-root">'.$clean.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        foreach (iterator_to_array($doc->getElementsByTagName('img')) as $image) {
            $path = $image->getAttribute('src');
            if (!preg_match('~^/(?:images|storage)/[^?#\\\\]+\.(?:jpe?g|png|webp|gif|avif)$~iD', $path) || str_contains($path, '..') || str_contains($path, '%')) {
                $image->parentNode->removeChild($image);
                continue;
            }
            $image->setAttribute('alt', $image->getAttribute('alt'));
            $image->setAttribute('loading', 'lazy');
            $image->setAttribute('decoding', 'async');
        }
        $root = $doc->getElementById('article-root');
        $output = '';
        foreach ($root?->childNodes ?? [] as $node) $output .= $doc->saveHTML($node);
        return $output;
    }

    public function render(string $body, string $format): string
    {
        return $format === 'html' ? $this->sanitize($body) : nl2br(e($body));
    }
}
