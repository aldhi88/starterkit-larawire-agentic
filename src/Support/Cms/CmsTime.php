<?php

namespace Aldhi88\StarterKit\Support\Cms;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CmsTime
{
    public static function timezone(): string
    {
        return 'Asia/Jakarta';
    }

    public static function label(): string
    {
        return 'WIB';
    }

    public static function nowForInput(): string
    {
        return now()->timezone(self::timezone())->format('Y-m-d\TH:i');
    }

    public static function forMaskedInput(?string $value): string
    {
        if (! $value) {
            return '';
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new DateTimeZone(self::timezone()));

        return $date ? $date->format('d/m/Y H:i') : '';
    }

    public static function fromMaskedInput(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (! preg_match('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/', $value)) {
            throw ValidationException::withMessages(['published_at' => 'Isi waktu terbit dengan format DD/MM/YYYY HH:mm.']);
        }
        $date = DateTimeImmutable::createFromFormat('!d/m/Y H:i', $value, new DateTimeZone(self::timezone()));
        $errors = DateTimeImmutable::getLastErrors();
        if (! $date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw ValidationException::withMessages(['published_at' => 'Tanggal atau jam terbit tidak valid.']);
        }

        return $date->setTimezone(new DateTimeZone((string) config('app.timezone', 'UTC')))->format('Y-m-d H:i:s');
    }

    public static function forInput(DateTimeInterface $date): string
    {
        return Carbon::instance($date)->timezone(self::timezone())->format('Y-m-d\TH:i');
    }

    public static function forTable(DateTimeInterface $date): string
    {
        return Carbon::instance($date)->timezone(self::timezone())->format('d/m/Y H:i');
    }

    public static function fromInput(mixed $value): mixed
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new DateTimeZone(self::timezone()));
        $errors = DateTimeImmutable::getLastErrors();
        if (! $date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return $value;
        }

        return $date->setTimezone(new DateTimeZone((string) config('app.timezone', 'UTC')))->format('Y-m-d H:i:s');
    }
}
