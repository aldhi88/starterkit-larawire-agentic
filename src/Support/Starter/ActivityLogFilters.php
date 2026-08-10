<?php

namespace Altekno\StarterKit\Support\Starter;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final readonly class ActivityLogFilters
{
    public function __construct(
        public string $search,
        public ?CarbonImmutable $dateFrom,
        public ?CarbonImmutable $dateTo,
        public string $event,
        public ?int $actorId,
        public string $role,
        public string $app,
        public string $table,
        public string $route,
        public string $ipPrefix,
        public string $actionPrefix,
    ) {}

    public static function fromInput(
        string $search,
        string $dateFrom,
        string $dateTo,
        string $event,
        string $actor,
        string $role,
        string $app,
        string $table,
        string $route,
        string $ipPrefix,
        string $actionPrefix,
    ): self {
        return new self(
            search: Str::limit(trim($search), 100, ''),
            dateFrom: self::parseDate($dateFrom),
            dateTo: self::parseDate($dateTo),
            event: Str::limit(trim($event), 50, ''),
            actorId: ctype_digit($actor) && (int) $actor > 0 ? (int) $actor : null,
            role: Str::limit(trim($role), 255, ''),
            app: Str::limit(trim($app), 100, ''),
            table: Str::limit(trim($table), 255, ''),
            route: Str::limit(trim($route), 255, ''),
            ipPrefix: Str::limit(trim($ipPrefix), 45, ''),
            actionPrefix: Str::limit(trim($actionPrefix), 26, ''),
        );
    }

    private static function parseDate(string $date): ?CarbonImmutable
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return null;
        }

        $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date
            ? $parsed
            : null;
    }
}
