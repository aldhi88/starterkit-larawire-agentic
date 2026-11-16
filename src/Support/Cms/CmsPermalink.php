<?php

namespace Aldhi88\StarterKit\Support\Cms;

class CmsPermalink
{
    public static function for(string $kind, string $slug): ?string
    {
        $patterns = [
            'post' => '/konten/{slug}', 'page' => '/halaman/{slug}',
            'post_category' => '/konten/kategori/{code}', 'page_category' => '/halaman/kategori/{code}',
        ];
        $category = str_ends_with($kind, '_category');
        $placeholder = $category ? '{code}' : '{slug}';
        if (! isset($patterns[$kind]) || ! preg_match($category ? '/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/D' : '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug)) {
            return null;
        }
        $pattern = config('starter.cms.permalinks.'.$kind, $patterns[$kind]);
        $base = rtrim((string) config('app.url'), '/');
        if (! is_string($pattern) || ! str_starts_with($pattern, '/') || str_starts_with($pattern, '//')
            || ! str_contains($pattern, $placeholder) || preg_match('/[\\\\\\s?#]/', $pattern)
            || ! in_array(parse_url($base, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return null;
        }

        return $base.str_replace($placeholder, rawurlencode($slug), $pattern);
    }
}
