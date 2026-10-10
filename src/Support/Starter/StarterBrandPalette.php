<?php

namespace Aldhi88\StarterKit\Support\Starter;

final class StarterBrandPalette
{
    /** @param array{primary: string, secondary: string, accent: string} $colors */
    private function __construct(private readonly array $colors) {}

    public static function configured(): ?self
    {
        $colors = self::configuredColors();

        if (! self::validColors($colors) || self::allBlank($colors)) {
            return null;
        }

        foreach ($colors as $name => $color) {
            $colors[$name] = strtoupper($color);
        }

        return new self($colors);
    }

    public static function configurationValid(): bool
    {
        return self::validColors(self::configuredColors());
    }

    public function fingerprint(): string
    {
        return substr(hash('sha256', implode('|', $this->colors)), 0, 12);
    }

    public function css(): string
    {
        $variables = [];

        foreach ($this->colors as $name => $color) {
            $rgb = self::channels($color);
            $onColor = self::contrast($color, '#FFFFFF') >= self::contrast($color, '#111111')
                ? '#FFFFFF'
                : '#111111';
            $hover = self::mix($color, self::luminance($color) < .08 ? '#FFFFFF' : '#000000', .12);
            $text = $color;
            $onDark = $color;

            for ($step = 0; $step < 8 && self::contrast($text, '#FFFFFF') < 4.5; $step++) {
                $text = self::mix($text, '#000000', .15);
            }

            for ($step = 0; $step < 8 && self::contrast($onDark, '#1F2937') < 4.5; $step++) {
                $onDark = self::mix($onDark, '#FFFFFF', .18);
            }

            $soft = self::mix($color, '#FFFFFF', .88);
            $checkInk = str_replace('#', '%23', $onColor);

            $variables[] = "--starter-brand-{$name}:{$color}";
            $variables[] = "--starter-brand-{$name}-rgb:".implode(',', $rgb);
            $variables[] = "--starter-brand-{$name}-contrast:{$onColor}";
            $variables[] = "--starter-brand-{$name}-hover:{$hover}";
            $variables[] = "--starter-brand-{$name}-soft:{$soft}";
            $variables[] = "--starter-brand-{$name}-soft-rgb:".implode(',', self::channels($soft));
            $variables[] = "--starter-brand-{$name}-border:".self::mix($color, '#FFFFFF', .65);
            $variables[] = "--starter-brand-{$name}-text:{$text}";
            $variables[] = "--starter-brand-{$name}-text-rgb:".implode(',', self::channels($text));
            $variables[] = "--starter-brand-{$name}-on-dark:{$onDark}";
            $variables[] = "--starter-brand-{$name}-on-dark-rgb:".implode(',', self::channels($onDark));
            $variables[] = "--starter-brand-{$name}-check-svg:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='{$checkInk}' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 8l3 3 7-7'/%3E%3C/svg%3E\")";
            $variables[] = "--starter-brand-{$name}-radio-svg:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Ccircle cx='8' cy='8' r='3' fill='{$checkInk}'/%3E%3C/svg%3E\")";
        }

        return ':root{'.implode(';', $variables).";}\n";
    }

    /** @return array{int, int, int} */
    private static function channels(string $color): array
    {
        return [hexdec(substr($color, 1, 2)), hexdec(substr($color, 3, 2)), hexdec(substr($color, 5, 2))];
    }

    private static function mix(string $first, string $second, float $secondShare): string
    {
        $a = self::channels($first);
        $b = self::channels($second);

        return sprintf('#%02X%02X%02X',
            (int) round($a[0] * (1 - $secondShare) + $b[0] * $secondShare),
            (int) round($a[1] * (1 - $secondShare) + $b[1] * $secondShare),
            (int) round($a[2] * (1 - $secondShare) + $b[2] * $secondShare),
        );
    }

    private static function luminance(string $color): float
    {
        $channels = array_map(static function (int $channel): float {
            $value = $channel / 255;

            return $value <= .04045 ? $value / 12.92 : (($value + .055) / 1.055) ** 2.4;
        }, self::channels($color));

        return .2126 * $channels[0] + .7152 * $channels[1] + .0722 * $channels[2];
    }

    private static function contrast(string $first, string $second): float
    {
        $firstLuminance = self::luminance($first);
        $secondLuminance = self::luminance($second);

        return (max($firstLuminance, $secondLuminance) + .05)
            / (min($firstLuminance, $secondLuminance) + .05);
    }

    /** @return array{primary: string, secondary: string, accent: string} */
    private static function configuredColors(): array
    {
        $values = (array) config('starter.brand', []);

        return [
            'primary' => trim((string) ($values['primary'] ?? '')),
            'secondary' => trim((string) ($values['secondary'] ?? '')),
            'accent' => trim((string) ($values['accent'] ?? '')),
        ];
    }

    /** @param array{primary: string, secondary: string, accent: string} $colors */
    private static function allBlank(array $colors): bool
    {
        return count(array_filter($colors, fn (string $color): bool => $color !== '')) === 0;
    }

    /** @param array{primary: string, secondary: string, accent: string} $colors */
    private static function validColors(array $colors): bool
    {
        if (self::allBlank($colors)) {
            return true;
        }

        foreach ($colors as $color) {
            if (preg_match('/^#[0-9a-fA-F]{6}$/D', $color) !== 1) {
                return false;
            }
        }

        return true;
    }
}
