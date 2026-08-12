<?php

namespace Aldhi88\StarterKit\Support\Starter;

class StarterDomain
{
    public static function host(string $url): string
    {
        $host = strtolower(rtrim((string) parse_url($url, PHP_URL_HOST), '.'));

        return $host !== '' ? $host : 'localhost';
    }

    public static function sessionDomain(string $url): ?string
    {
        $host = self::host($url);

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return null;
        }

        return '.'.$host;
    }

    public static function secureCookie(string $url): bool
    {
        return strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https';
    }
}
