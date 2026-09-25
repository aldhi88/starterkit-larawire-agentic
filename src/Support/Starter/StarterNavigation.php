<?php

namespace Aldhi88\StarterKit\Support\Starter;

use Illuminate\Http\Request;

class StarterNavigation
{
    public static function rootHost(): string
    {
        return (string) config('app.domain');
    }

    public static function rootAuthority(): string
    {
        $port = parse_url((string) config('app.url'), PHP_URL_PORT);

        return self::rootHost().(is_int($port) ? ':'.$port : '');
    }

    public static function authUrl(string $path = ''): string
    {
        $path = trim($path, '/');
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME);

        if (! in_array($scheme, ['http', 'https'], true)) {
            $scheme = request()->isSecure() ? 'https' : 'http';
        }

        return $scheme.'://'.self::rootAuthority().'/auth'.($path === '' ? '' : '/'.$path);
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

        if ($host !== $domain && ! str_ends_with((string) $host, '.'.$domain)) {
            return false;
        }

        return ! self::isInternalEndpointPath((string) parse_url($url, PHP_URL_PATH));
    }

    public static function safeRequestReturnUrl(Request $request): ?string
    {
        $candidates = [
            $request->headers->get('X-Starter-Page-Url'),
            $request->headers->get('referer'),
            $request->isMethod('GET') ? $request->fullUrl() : null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && self::isSafeRedirect($candidate)) {
                return $candidate;
            }
        }

        return null;
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

    private static function isInternalEndpointPath(string $path): bool
    {
        $path = strtolower(trim(rawurldecode($path), '/'));

        if ($path === '') {
            return false;
        }

        if (preg_match('#^livewire(?:-[^/]+)?(?:/|$)#', $path) === 1) {
            return true;
        }

        if (in_array($path, [
            'auth',
            'auth/login',
            'auth/logout',
            'confirm-password',
            'lock-screen',
            'session/activity',
            'up',
        ], true)) {
            return true;
        }

        return preg_match('#^(?:_debugbar|_ignition|api|broadcasting/auth|horizon|sanctum/csrf-cookie|telescope)(?:/|$)#', $path) === 1;
    }
}
