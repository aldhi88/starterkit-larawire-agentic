<?php

namespace Altekno\StarterKit\Support\Starter;

class StarterPaths
{
    private static ?string $root = null;

    public static function root(): string
    {
        return self::$root ??= dirname(__DIR__, 3);
    }

    public static function path(string $path = ''): string
    {
        return self::root().($path === '' ? '' : DIRECTORY_SEPARATOR.ltrim($path, '/\\'));
    }

    public static function isEmbedded(): bool
    {
        return realpath(self::root()) !== realpath(base_path());
    }
}
