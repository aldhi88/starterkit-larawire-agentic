<?php

namespace Aldhi88\StarterKit\Support\Starter;

final class StarterNotificationSound
{
    public const DEFAULT = 'chime';

    /** @return array<string, array{label: string, notes: list<array{int, float}>}> */
    public static function catalog(): array
    {
        return [
            'chime' => ['label' => 'Ting tung jelas · 2,2 detik (default)', 'notes' => [[880, .55], [659, 1.6]]],
            'bell' => ['label' => 'Bel singkat', 'notes' => [[1047, .35]]],
            'gentle' => ['label' => 'Nada tenang', 'notes' => [[440, .18], [554, .25]]],
        ];
    }

    public static function normalize(mixed $sound, bool $customAvailable = false): string
    {
        if ($sound === 'custom' && $customAvailable) {
            return 'custom';
        }

        return is_string($sound) && array_key_exists($sound, self::catalog()) ? $sound : self::DEFAULT;
    }

    public static function isOwnedPath(int $loginId, mixed $path): bool
    {
        return is_string($path) && preg_match(
            '~^starter/notification-sounds/'.preg_quote((string) $loginId, '~').'/[a-f0-9-]{36}\.(mp3|wav)$~',
            $path,
        ) === 1;
    }
}
