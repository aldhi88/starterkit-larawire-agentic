<?php

namespace Aldhi88\StarterKit\Support\Starter;

use PowerComponents\LivewirePowerGrid\Themes\Theme;
use RuntimeException;

class StarterThemeRegistry
{
    /** @var list<string> */
    private const REQUIRED_LAYOUTS = ['vertical', 'horizontal'];

    /** @var array<string, array<string, mixed>> */
    private static array $themes = [];

    /** @param array<string, mixed> $definition */
    public static function register(string $key, array $definition): void
    {
        self::assertKey($key);
        self::assertDefinition($key, $definition);
        self::$themes[$key] = $definition;
    }

    public static function flushRegistered(): void
    {
        self::$themes = [];
    }

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        $merged = array_replace((array) config('starter.themes', []), self::$themes);
        $themes = [];

        foreach ($merged as $key => $definition) {
            if (! is_string($key) || ! is_array($definition)) {
                throw new RuntimeException('Every starter theme must use a valid string key and array definition.');
            }

            self::assertKey($key);
            self::assertDefinition($key, $definition);
            $themes[$key] = $definition;
        }

        return $themes;
    }

    /** @return array<string, mixed> */
    public static function get(string $key): array
    {
        self::assertKey($key);
        $definition = self::all()[$key] ?? null;

        if (! is_array($definition)) {
            throw new RuntimeException("Unsupported starter theme [{$key}].");
        }

        return $definition;
    }

    public static function path(string $theme, string $key, string $path = ''): string
    {
        $definition = self::get($theme);
        $root = $definition['root'] ?? StarterPaths::root();
        $relative = $definition[$key] ?? null;

        if (! is_string($root) || $root === '' || ! self::isAbsolutePath($root)) {
            throw new RuntimeException("Starter theme [{$theme}] does not define an absolute package root.");
        }

        if (! is_string($relative) || ! self::isSafeRelativePath($relative)) {
            throw new RuntimeException("Starter theme [{$theme}] does not define a safe [{$key}] path.");
        }

        if ($path !== '' && ! self::isSafeRelativePath($path)) {
            throw new RuntimeException("Starter theme [{$theme}] received an unsafe child path.");
        }

        return rtrim($root, '/\\').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, trim($relative, '/\\'))
            .($path === '' ? '' : DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, ltrim($path, '/\\')));
    }

    private static function assertKey(string $key): void
    {
        if (preg_match('/^[a-z0-9][a-z0-9_-]*$/', $key) !== 1) {
            throw new RuntimeException("Invalid starter theme key [{$key}].");
        }
    }

    /** @param array<string, mixed> $definition */
    private static function assertDefinition(string $key, array $definition): void
    {
        $label = $definition['label'] ?? null;
        $root = $definition['root'] ?? null;

        if (! is_string($label) || trim($label) === '') {
            throw new RuntimeException("Starter theme [{$key}] must define a label.");
        }

        if (! is_string($root) || ! self::isAbsolutePath($root) || ! is_dir($root)) {
            throw new RuntimeException("Starter theme [{$key}] must define an existing absolute package root.");
        }

        foreach (['views', 'assets', 'docs'] as $pathKey) {
            $relative = $definition[$pathKey] ?? null;

            if (! is_string($relative) || ! self::isSafeRelativePath($relative)) {
                throw new RuntimeException("Starter theme [{$key}] must define a safe [{$pathKey}] directory.");
            }

            if (! is_dir(rtrim($root, '/\\').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative))) {
                throw new RuntimeException("Starter theme [{$key}] [{$pathKey}] directory was not found.");
            }
        }

        $powerGrid = $definition['powergrid'] ?? null;

        if (! is_string($powerGrid) || ! class_exists($powerGrid) || ! is_a($powerGrid, Theme::class, true)) {
            throw new RuntimeException("Starter theme [{$key}] must define a valid PowerGrid theme class.");
        }

        $layouts = $definition['layouts'] ?? null;

        if (! is_array($layouts)) {
            throw new RuntimeException("Starter theme [{$key}] must define its layouts.");
        }

        $viewsRoot = rtrim($root, '/\\').DIRECTORY_SEPARATOR
            .str_replace('/', DIRECTORY_SEPARATOR, (string) $definition['views']);

        foreach (self::REQUIRED_LAYOUTS as $layout) {
            $view = $layouts[$layout] ?? null;

            if (! is_string($view) || ! self::isSafeViewName($view)) {
                throw new RuntimeException("Starter theme [{$key}] must define a safe [{$layout}] layout view.");
            }

            $viewFile = $viewsRoot.DIRECTORY_SEPARATOR
                .str_replace('.', DIRECTORY_SEPARATOR, $view).'.blade.php';

            if (! is_file($viewFile)) {
                throw new RuntimeException("Starter theme [{$key}] [{$layout}] layout view was not found.");
            }
        }
    }

    private static function isSafeRelativePath(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);
        $trimmed = trim($normalized, '/');

        return $trimmed !== ''
            && ! self::isAbsolutePath($normalized)
            && preg_match('~(^|/)\.\.?(?:/|$)~', $trimmed) !== 1;
    }

    private static function isSafeViewName(string $view): bool
    {
        return $view !== ''
            && ! str_contains($view, '..')
            && preg_match('/^[a-z0-9][a-z0-9_.-]*$/i', $view) === 1;
    }

    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
