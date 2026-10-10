<?php

namespace Aldhi88\StarterKit\Support\Starter;

use RuntimeException;

final class StarterBrandStylesheet
{
    private function __construct(
        private readonly StarterBrandPalette $palette,
        private readonly string $theme,
        private readonly string $adapter,
    ) {}

    public static function configured(): ?self
    {
        $palette = StarterBrandPalette::configured();

        if ($palette === null) {
            return null;
        }

        $theme = StarterTheme::key();
        $path = StarterPaths::path("resources/themes/{$theme}/brand-palette.css");

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Brand palette CSS is missing for theme [{$theme}].");
        }

        $adapter = file_get_contents($path);

        if ($adapter === false || trim($adapter) === '') {
            throw new RuntimeException("Brand palette CSS is empty for theme [{$theme}].");
        }

        return new self($palette, $theme, $adapter);
    }

    public function fingerprint(): string
    {
        return substr(hash('sha256', $this->theme.'|'.$this->palette->fingerprint().'|'.$this->adapter), 0, 12);
    }

    public function css(): string
    {
        return $this->palette->css()."\n".$this->adapter;
    }
}
