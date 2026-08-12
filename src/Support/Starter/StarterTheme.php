<?php

namespace Aldhi88\StarterKit\Support\Starter;

use Illuminate\Support\Facades\View;
use RuntimeException;

class StarterTheme
{
    /** @var list<string> */
    private const REQUIRED_LAYOUTS = ['vertical', 'horizontal'];

    public static function key(): string
    {
        $key = strtolower(trim((string) config('starter.theme', 'tabler')));

        if (preg_match('/^[a-z0-9][a-z0-9_-]*$/', $key) !== 1
            || ! array_key_exists($key, StarterThemeRegistry::all())) {
            throw new RuntimeException("Unsupported starter theme [{$key}].");
        }

        return $key;
    }

    public static function viewPath(string $path = ''): string
    {
        return self::configuredPath('views', $path);
    }

    public static function layout(): string
    {
        $layout = strtolower(trim((string) config('starter.layout', 'vertical')));

        if (! self::supportsLayout($layout)) {
            throw new RuntimeException(
                'Starter theme ['.self::key()."] does not support layout [{$layout}].",
            );
        }

        return $layout;
    }

    public static function supportsLayout(string $layout): bool
    {
        $layout = strtolower(trim($layout));

        if (preg_match('/^[a-z0-9][a-z0-9_-]*$/', $layout) !== 1) {
            return false;
        }

        $view = StarterThemeRegistry::get(self::key())['layouts'][$layout] ?? null;

        return self::isSafeViewName($view);
    }

    public static function hasLayoutView(string $layout): bool
    {
        if (! self::supportsLayout($layout)) {
            return false;
        }

        $definition = StarterThemeRegistry::get(self::key());

        return View::exists((string) ($definition['layouts'][strtolower(trim($layout))] ?? ''));
    }

    public static function hasCompleteLayoutRegistry(): bool
    {
        foreach (self::REQUIRED_LAYOUTS as $layout) {
            if (! self::hasLayoutView($layout)) {
                return false;
            }
        }

        return true;
    }

    public static function layoutView(): string
    {
        $layout = self::layout();
        $definition = StarterThemeRegistry::get(self::key());
        $view = (string) ($definition['layouts'][$layout] ?? '');

        if (! View::exists($view)) {
            throw new RuntimeException(
                'Starter theme ['.self::key()."] layout [{$layout}] view [{$view}] was not found.",
            );
        }

        return $view;
    }

    /** @return view-string */
    public static function viewName(string $view): string
    {
        if (! self::isSafeViewName($view) || ! View::exists($view)) {
            throw new RuntimeException("Starter theme view [{$view}] was not found.");
        }

        return $view;
    }

    public static function assetPath(string $path = ''): string
    {
        return self::configuredPath('assets', $path);
    }

    public static function docsPath(string $path = ''): string
    {
        return self::configuredPath('docs', $path);
    }

    /** @return class-string */
    public static function powerGridTheme(): string
    {
        $theme = StarterThemeRegistry::get(self::key())['powergrid'] ?? null;

        if (! is_string($theme) || $theme === '') {
            throw new RuntimeException('The active starter theme does not define a PowerGrid theme.');
        }

        return $theme;
    }

    private static function configuredPath(string $key, string $path): string
    {
        return StarterThemeRegistry::path(self::key(), $key, $path);
    }

    private static function isSafeViewName(mixed $view): bool
    {
        return is_string($view)
            && $view !== ''
            && ! str_contains($view, '..')
            && preg_match('/^[a-z0-9][a-z0-9_.-]*$/i', $view) === 1;
    }
}
