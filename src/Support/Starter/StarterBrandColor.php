<?php

namespace Aldhi88\StarterKit\Support\Starter;

/** Perceptual tones with chroma reduction into sRGB; inputs are validated HEX seeds. */
final class StarterBrandColor
{
    /** @return array{int, int, int} */
    public static function rgb(string $hex): array
    {
        return [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
    }

    public static function contrast(string $first, string $second): float
    {
        $a = self::luminance($first);
        $b = self::luminance($second);

        return (max($a, $b) + .05) / (min($a, $b) + .05);
    }

    public static function ink(string $background): string
    {
        return self::contrast($background, '#FFFFFF') >= self::contrast($background, '#000000') ? '#FFFFFF' : '#000000';
    }

    public static function lightness(string $hex): float
    {
        return self::lab($hex)[0];
    }

    public static function tone(string $hex, float $lightness, float $maxChroma = .4): string
    {
        [, $a, $b] = self::lab($hex);
        $chroma = sqrt($a * $a + $b * $b);
        $scale = $chroma > 0 ? min(1, $maxChroma / $chroma) : 0;
        $a *= $scale;
        $b *= $scale;
        $lightness = max(0, min(1, $lightness));
        $low = 0;
        $high = 1;
        $rgb = self::linearRgb($lightness, $a, $b);
        // Reduce chroma at a fixed hue/lightness instead of clipping individual channels.
        if (min($rgb) < -.000001 || max($rgb) > 1.000001) {
            for ($step = 0; $step < 20; $step++) {
                $mid = ($low + $high) / 2;
                $candidate = self::linearRgb($lightness, $a * $mid, $b * $mid);
                if (min($candidate) >= 0 && max($candidate) <= 1) {
                    $low = $mid;
                } else {
                    $high = $mid;
                }
            }
            $rgb = self::linearRgb($lightness, $a * $low, $b * $low);
        }
        $channels = array_map(static function (float $value): int {
            $value = max(0, min(1, $value));

            return (int) round(255 * ($value <= .0031308 ? 12.92 * $value : 1.055 * ($value ** (1 / 2.4)) - .055));
        }, $rgb);

        return sprintf('#%02X%02X%02X', ...$channels);
    }

    /** @return array{float, float, float} */
    private static function lab(string $hex): array
    {
        [$r, $g, $b] = self::linearChannels($hex);
        // Public-domain Oklab matrices by Björn Ottosson: https://bottosson.github.io/posts/oklab/
        $l = (.4122214708 * $r + .5363325363 * $g + .0514459929 * $b) ** (1 / 3);
        $m = (.2119034982 * $r + .6806995451 * $g + .1073969566 * $b) ** (1 / 3);
        $s = (.0883024619 * $r + .2817188376 * $g + .6299787005 * $b) ** (1 / 3);

        return [.2104542553 * $l + .7936177850 * $m - .0040720468 * $s,
            1.9779984951 * $l - 2.4285922050 * $m + .4505937099 * $s,
            .0259040371 * $l + .7827717662 * $m - .8086757660 * $s];
    }

    /** @return array{float, float, float} */
    private static function linearRgb(float $lightness, float $a, float $b): array
    {
        $l = ($lightness + .3963377774 * $a + .2158037573 * $b) ** 3;
        $m = ($lightness - .1055613458 * $a - .0638541728 * $b) ** 3;
        $s = ($lightness - .0894841775 * $a - 1.2914855480 * $b) ** 3;

        return [4.0767416621 * $l - 3.3077115913 * $m + .2309699292 * $s,
            -1.2684380046 * $l + 2.6097574011 * $m - .3413193965 * $s,
            -.0041960863 * $l - .7034186147 * $m + 1.7076147010 * $s];
    }

    /** @return array{float, float, float} */
    private static function linearChannels(string $hex): array
    {
        return array_map(static function (int $channel): float {
            $value = $channel / 255;

            return $value <= .04045 ? $value / 12.92 : (($value + .055) / 1.055) ** 2.4;
        }, self::rgb($hex));
    }

    private static function luminance(string $hex): float
    {
        [$r, $g, $b] = self::linearChannels($hex);

        return .2126 * $r + .7152 * $g + .0722 * $b;
    }
}
