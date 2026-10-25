<?php

namespace Aldhi88\StarterKit\Support\Starter;

final class StarterNotificationSound
{
    public const DEFAULT = 'chime';

    /** @return array<string, array{label: string, notes: list<array{int, float}>}> */
    public static function catalog(): array
    {
        return [
            'chime' => ['label' => 'Ting tung tegas (default)', 'notes' => [[988, .38], [784, .52], [988, .72]]],
            'bell' => ['label' => 'Bel ganda', 'notes' => [[1175, .28], [1175, .42]]],
            'gentle' => ['label' => 'Nada naik', 'notes' => [[698, .24], [880, .24], [1175, .52]]],
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
