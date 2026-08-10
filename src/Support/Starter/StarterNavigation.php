<?php

namespace Altekno\StarterKit\Support\Starter;

class StarterNavigation
{
    public static function rootHost(): string
    {
        return (string) config('app.domain');
    }

    public static function authUrl(string $path = ''): string
    {
        $path = trim($path, '/');
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME);

        if (! in_array($scheme, ['http', 'https'], true)) {
            $scheme = request()->isSecure() ? 'https' : 'http';
        }

        return $scheme.'://'.self::rootHost().'/auth'.($path === '' ? '' : '/'.$path);
    }

    public static function authLoginUrl(?string $redirect = null): string
    {
        $url = self::authUrl('login');

        return $redirect ? $url.'?'.http_build_query(['redirect' => $redirect]) : $url;
    }

    public static function isSafeRedirect(?string $url): bool
    {
        if (! $url) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $configuredScheme = strtolower((string) parse_url((string) config('app.url'), PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $domain = strtolower((string) config('app.domain'));

        if (! in_array($scheme, ['http', 'https'], true)
            || ! in_array($configuredScheme, ['http', 'https'], true)
            || ! hash_equals($configuredScheme, $scheme)
            || $host === ''
            || parse_url($url, PHP_URL_USER) !== null
            || parse_url($url, PHP_URL_PASS) !== null
            || ! self::hasSafePort($url, $scheme)) {
            return false;
        }

        return $host === $domain || str_ends_with((string) $host, '.'.$domain);
    }

    private static function hasSafePort(string $url, string $scheme): bool
    {
        $port = parse_url($url, PHP_URL_PORT);

        if ($port === null) {
            return true;
        }

        $configuredPort = parse_url((string) config('app.url'), PHP_URL_PORT);

        if ($configuredPort !== null) {
            return $port === $configuredPort;
        }

        return $port === ($scheme === 'https' ? 443 : 80);
    }
}
