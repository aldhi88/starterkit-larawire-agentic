<?php

namespace Altekno\StarterKit\Support\Starter;

use Illuminate\Support\Number;

class StarterNumber
{
    public static function decimal(int|float $value, int $maxPrecision = 2): string
    {
        return Number::format(
            $value,
            maxPrecision: max(0, $maxPrecision),
            locale: self::locale(),
        );
    }

    public static function currency(int|float $value, string $currency = 'IDR'): string
    {
        $hasFraction = abs($value - (int) $value) > PHP_FLOAT_EPSILON;

        return Number::currency(
            $value,
            in: strtoupper($currency),
            locale: self::locale(),
            precision: $hasFraction ? 2 : 0,
        );
    }

    private static function locale(): string
    {
        return str_replace('_', '-', app()->getLocale());
    }
}
