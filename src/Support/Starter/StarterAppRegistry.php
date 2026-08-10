<?php

namespace Altekno\StarterKit\Support\Starter;

class StarterAppRegistry
{
    /** @var list<string>|null */
    private static ?array $discoveredKeys = null;

    /**
     * Discover runnable starter apps from config/apps/*.php.
     *
     * A file is runnable only when the filename is domain-safe, the app route file
     * exists, and at least one module is configured.
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return self::$discoveredKeys ??= collect(glob(config_path('apps/*.php')) ?: [])
            ->map(function (string $path): string {
                return pathinfo($path, PATHINFO_FILENAME);
            })
            ->filter(function (string $key): bool {
                if ($key === 'api') {
                    return false;
                }

                if (preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $key) !== 1) {
                    return false;
                }

                if (! is_file(base_path("routes/apps/{$key}.php"))) {
                    return false;
                }

                $config = require config_path("apps/{$key}.php");

                return is_array($config)
                    && ! empty($config['mods'])
                    && is_array($config['mods']);
            })
            ->sort()
            ->values()
            ->all();
    }
}
