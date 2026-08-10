<?php

namespace Altekno\StarterKit\Services\Starter;

use Altekno\StarterKit\Contracts\Starter\StarterConfigInterface;
use Altekno\StarterKit\Models\Starter\StarterConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class StarterConfigService
{
    private const CACHE_PREFIX = 'starter.config.';

    /** @var array<string, bool|int|string> */
    private const DEFAULTS = [
        'security.remember_me_enabled' => true,
        'security.lock_screen_enabled' => true,
        'security.lock_screen_timeout_minutes' => 15,
        'security.login_max_attempts' => 5,
        'security.login_decay_seconds' => 60,
    ];

    private ?bool $tableExists = null;

    public function __construct(
        private readonly StarterConfigInterface $configs,
    ) {}

    public function value(string $key): bool|int|string|null
    {
        $fallback = self::DEFAULTS[$key] ?? null;

        if (! $this->hasTable()) {
            return $fallback;
        }

        $value = Cache::rememberForever(
            self::CACHE_PREFIX.$key,
            fn (): bool|int|string|null => $this->resolveValue(
                $this->configs->findByKey($key),
                $fallback,
            ),
        );

        return $value;
    }

    public function boolean(string $key): bool
    {
        return filter_var($this->value($key), FILTER_VALIDATE_BOOL);
    }

    public function integer(string $key): int
    {
        return (int) $this->value($key);
    }

    public function forget(string $key): void
    {
        Cache::forget(self::CACHE_PREFIX.$key);
    }

    /**
     * @param  array<string, bool|int|string>  $values
     */
    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            $config = $this->configs->findByKeyOrFail($key);
            $this->configs->updateValue($config, $this->serializeValue($value, $config->type));
            $this->forget($key);
        }
    }

    private function hasTable(): bool
    {
        return $this->tableExists ??= Schema::hasTable('starter_configs');
    }

    private function resolveValue(?StarterConfig $config, bool|int|string|null $fallback): bool|int|string|null
    {
        if (! $config instanceof StarterConfig) {
            return $fallback;
        }

        return match ($config->type) {
            'boolean' => filter_var($config->value, FILTER_VALIDATE_BOOL),
            'integer' => (int) $config->value,
            default => $config->value,
        };
    }

    private function serializeValue(bool|int|string $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) ((int) $value),
            default => (string) $value,
        };
    }
}
