<?php

namespace Aldhi88\StarterKit\Support\Starter;

use RuntimeException;

final class StarterBrandStylesheet
{
    private function __construct(
        private readonly StarterBrandPalette $palette,
        private readonly string $theme,
        private readonly string $adapter,
        private readonly string $feedback,
        /** @var array<string, string> */
        private readonly array $statusSeeds,
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

        $seeds = [];
        foreach (['success', 'warning', 'info', 'danger'] as $status) {
            if (preg_match('/--starter-native-'.$status.'\s*:\s*(#[0-9a-fA-F]{6})\s*;/', $adapter, $match) !== 1) {
                throw new RuntimeException("Brand palette CSS must declare native [{$status}] HEX seed for theme [{$theme}].");
            }
            $seeds[$status] = strtoupper($match[1]);
        }
        $feedback = file_get_contents(StarterPaths::path('resources/css/brand-feedback.css'));
        if ($feedback === false || trim($feedback) === '') {
            throw new RuntimeException('Shared brand feedback CSS is missing or empty.');
        }

        return new self($palette, $theme, $adapter, $feedback, $seeds);
    }

    public function fingerprint(): string
    {
        return substr(hash('sha256', $this->theme.'|'.$this->palette->css($this->statusSeeds).'|'.$this->feedback.'|'.$this->adapter), 0, 12);
    }

    public function css(): string
    {
        return $this->palette->css($this->statusSeeds)."\n".$this->feedback."\n".$this->adapter;
    }
}
