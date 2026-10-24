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

    /** @param array<string, string> $statusSeeds Native status hues supplied by the active adapter. */
    public function css(array $statusSeeds = []): string
    {
        $variables = [];
        foreach ([...$this->colors, ...$statusSeeds] as $name => $color) {
            $lightness = StarterBrandColor::lightness($color);
            $hover = StarterBrandColor::tone($color, $lightness < .3 ? $lightness + .08 : $lightness - .06);
            $onColor = StarterBrandColor::ink($color);
            $tokens = [
                '' => $color,
                '-rgb' => implode(',', StarterBrandColor::rgb($color)),
                '-contrast' => $onColor,
                '-hover' => $hover,
                '-hover-contrast' => StarterBrandColor::ink($hover),
                '-soft' => StarterBrandColor::tone($color, .97, .025),
                '-soft-dark' => StarterBrandColor::tone($color, .26, .035),
                '-border' => StarterBrandColor::tone($color, .84, .055),
                '-border-dark' => StarterBrandColor::tone($color, .48, .065),
                '-text' => StarterBrandColor::tone($color, .4, .12),
                '-on-dark' => StarterBrandColor::tone($color, .85, .09),
            ];
            foreach (['soft', 'text', 'on-dark'] as $variant) {
                $tokens["-{$variant}-rgb"] = implode(',', StarterBrandColor::rgb($tokens["-{$variant}"]));
            }
            foreach ($tokens as $suffix => $value) {
                $variables[] = "--starter-brand-{$name}{$suffix}:{$value}";
            }
            $checkInk = str_replace('#', '%23', $onColor);
            $variables[] = "--starter-brand-{$name}-check-svg:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='{$checkInk}' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 8l3 3 7-7'/%3E%3C/svg%3E\")";
            $variables[] = "--starter-brand-{$name}-radio-svg:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Ccircle cx='8' cy='8' r='3' fill='{$checkInk}'/%3E%3C/svg%3E\")";
        }
        $variables[] = '--starter-feedback-body:'.StarterBrandColor::tone($this->colors['primary'], .3, .012);
        $variables[] = '--starter-feedback-body-dark:'.StarterBrandColor::tone($this->colors['primary'], .94, .012);

        return ':root{'.implode(';', $variables).";}\n";
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
