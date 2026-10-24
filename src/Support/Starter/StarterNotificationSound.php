<?php

namespace Aldhi88\StarterKit\Support\Starter;

final class StarterNotificationSound
{
    public const DEFAULT = 'chime';

    /** @return array<string, array{label: string, notes: list<array{int, float}>}> */
    public static function catalog(): array
    {
        return [
            'chime' => ['label' => 'Ting tung lembut · 2,4 detik (default)', 'notes' => [[880, .75], [659, 1.6]]],
            'bell' => ['label' => 'Bel singkat', 'notes' => [[1047, .35]]],
            'gentle' => ['label' => 'Nada tenang', 'notes' => [[440, .18], [554, .25]]],
        ];
    }

    public static function normalize(mixed $sound): string
    {
        return is_string($sound) && array_key_exists($sound, self::catalog()) ? $sound : self::DEFAULT;
    }
}
