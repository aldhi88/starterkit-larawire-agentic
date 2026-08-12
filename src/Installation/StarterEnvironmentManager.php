<?php

namespace Aldhi88\StarterKit\Installation;

use RuntimeException;

class StarterEnvironmentManager
{
    private const BLOCK_START = '# starterkit-larawire:begin';

    private const BLOCK_END = '# starterkit-larawire:end';

    /** @var list<string> */
    private const MANAGED_KEYS = [
        'APP_DOMAIN',
        'APP_LOCALE',
        'APP_FALLBACK_LOCALE',
        'APP_FAKER_LOCALE',
        'STARTER_THEME',
        'STARTER_LAYOUT',
        'STARTER_API_ENABLED',
        'SESSION_DRIVER',
        'CACHE_STORE',
        'QUEUE_CONNECTION',
        'DB_MIGRATIONS_TABLE',
        'DB_CACHE_TABLE',
        'DB_CACHE_LOCK_TABLE',
        'DB_QUEUE_TABLE',
        'DB_QUEUE_BATCH_TABLE',
        'DB_QUEUE_FAILED_TABLE',
        'SESSION_TABLE',
        'AUTH_PASSWORD_RESET_TOKEN_TABLE',
        'SESSION_DOMAIN',
        'SESSION_COOKIE',
        'SESSION_SECURE_COOKIE',
        'SESSION_ENCRYPT',
        'SESSION_HTTP_ONLY',
        'SESSION_SAME_SITE',
    ];

    public function apply(
        string $path,
        bool $example = false,
        string $theme = 'tabler',
        string $layout = 'vertical',
    ): void {
        if (! is_file($path)) {
            throw new RuntimeException("File environment tidak ditemukan: {$path}");
        }

        $contents = $this->read($path);
        $values = [];

        foreach (self::MANAGED_KEYS as $key) {
            $values[$key] = $this->value($contents, $key);
        }

        $appUrl = trim($this->value($contents, 'APP_URL'), "\"' ");
        $domain = strtolower(rtrim((string) parse_url($appUrl, PHP_URL_HOST), '.'));

        if ($domain === '') {
            throw new RuntimeException('APP_URL tidak memiliki host/domain yang valid.');
        }

        $scheme = strtolower((string) parse_url($appUrl, PHP_URL_SCHEME));
        $secure = $scheme === 'https' ? 'true' : 'false';
        $localDomain = in_array($domain, ['localhost', '127.0.0.1', '::1'], true);
        $sessionDomain = $localDomain ? 'null' : '.'.$domain;
        $sessionCookie = trim((string) preg_replace('/[^a-z0-9]+/', '_', $domain), '_').'_session';

        $defaults = [
            'APP_DOMAIN' => $domain,
            'APP_LOCALE' => 'id',
            'APP_FALLBACK_LOCALE' => 'id',
            'APP_FAKER_LOCALE' => 'id_ID',
            'STARTER_THEME' => $theme,
            'STARTER_LAYOUT' => $layout,
            'STARTER_API_ENABLED' => 'false',
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'sync',
            'DB_MIGRATIONS_TABLE' => 'x_migrations',
            'DB_CACHE_TABLE' => 'x_cache',
            'DB_CACHE_LOCK_TABLE' => 'x_cache_locks',
            'DB_QUEUE_TABLE' => 'x_jobs',
            'DB_QUEUE_BATCH_TABLE' => 'x_job_batches',
            'DB_QUEUE_FAILED_TABLE' => 'x_failed_jobs',
            'SESSION_TABLE' => 'x_sessions',
            'AUTH_PASSWORD_RESET_TOKEN_TABLE' => 'x_password_reset_tokens',
            'SESSION_DOMAIN' => $sessionDomain,
            'SESSION_COOKIE' => $sessionCookie,
            'SESSION_SECURE_COOKIE' => $secure,
            'SESSION_ENCRYPT' => 'true',
            'SESSION_HTTP_ONLY' => 'true',
            'SESSION_SAME_SITE' => 'lax',
        ];

        foreach ($defaults as $key => $default) {
            if (trim($values[$key], "\"' ") === ''
                || ($key === 'SESSION_COOKIE'
                    && in_array(trim($values[$key], "\"' "), ['laravel-session', 'laravel_session'], true))) {
                $values[$key] = $default;
            }
        }

        foreach ([
            'APP_DOMAIN',
            'APP_LOCALE',
            'APP_FALLBACK_LOCALE',
            'APP_FAKER_LOCALE',
            'SESSION_DOMAIN',
            'SESSION_SECURE_COOKIE',
            'SESSION_ENCRYPT',
            'SESSION_HTTP_ONLY',
            'SESSION_SAME_SITE',
            'STARTER_THEME',
            'STARTER_LAYOUT',
            'SESSION_DRIVER',
            'CACHE_STORE',
            'QUEUE_CONNECTION',
        ] as $derived) {
            $values[$derived] = $defaults[$derived];
        }

        if ($example) {
            $values['APP_DOMAIN'] = 'null';
            $values['SESSION_DOMAIN'] = 'null';
            $values['SESSION_COOKIE'] = 'larawire_session';
            $values['SESSION_SECURE_COOKIE'] = 'null';
        }

        $contents = preg_replace(
            '/\R?'.preg_quote(self::BLOCK_START, '/').'.*?'.preg_quote(self::BLOCK_END, '/').'\R?/s',
            "\n",
            $contents,
        ) ?? $contents;

        foreach (self::MANAGED_KEYS as $key) {
            $contents = preg_replace('/^'.preg_quote($key, '/').'=.*\R?/m', '', $contents) ?? $contents;
        }

        $block = $this->block($values);
        $updated = rtrim($contents).PHP_EOL.PHP_EOL.$block.PHP_EOL;

        if (file_put_contents($path, $updated, LOCK_EX) === false) {
            throw new RuntimeException("File environment tidak dapat ditulis: {$path}");
        }
    }

    public function appUrl(string $path): string
    {
        return trim($this->value($this->read($path), 'APP_URL'), "\"' ");
    }

    public function setApplicationName(string $path, string $name): void
    {
        $contents = $this->read($path);
        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], trim($name));
        $line = 'APP_NAME="'.$escaped.'"';

        if (preg_match('/^APP_NAME=.*$/m', $contents) === 1) {
            $contents = preg_replace('/^APP_NAME=.*$/m', $line, $contents, 1) ?? $contents;
        } else {
            $contents = $line.PHP_EOL.$contents;
        }

        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new RuntimeException("File environment tidak dapat ditulis: {$path}");
        }
    }

    /** @return array<string, string> */
    public function processEnvironment(string $path): array
    {
        $contents = $this->read($path);
        $values = [];

        foreach (self::MANAGED_KEYS as $key) {
            $value = $this->value($contents, $key);

            if ($value !== '') {
                $values[$key] = trim($value, "\"'");
            }
        }

        return $values;
    }

    /** @return array<string, string|null> */
    public function databaseSummary(): array
    {
        $connection = (string) config('database.default');
        $config = (array) config("database.connections.{$connection}", []);

        if ($connection === 'sqlite') {
            $database = (string) ($config['database'] ?? '');

            return [
                'Driver' => $connection,
                'File' => $database === ':memory:' ? $database : (realpath($database) ?: $database),
            ];
        }

        return [
            'Driver' => $connection,
            'Host' => isset($config['host']) ? (string) $config['host'] : null,
            'Port' => isset($config['port']) ? (string) $config['port'] : null,
            'Database' => isset($config['database']) ? (string) $config['database'] : null,
            'Username' => isset($config['username']) ? (string) $config['username'] : null,
        ];
    }

    /** @param array<string, string> $values */
    private function block(array $values): string
    {
        $groups = [
            'Application and localization' => [
                'APP_DOMAIN', 'APP_LOCALE', 'APP_FALLBACK_LOCALE', 'APP_FAKER_LOCALE',
            ],
            'Theme and layout' => ['STARTER_THEME', 'STARTER_LAYOUT'],
            'API gateway' => ['STARTER_API_ENABLED'],
            'Runtime drivers' => ['SESSION_DRIVER', 'CACHE_STORE', 'QUEUE_CONNECTION'],
            'Starterkit database tables' => [
                'DB_MIGRATIONS_TABLE', 'DB_CACHE_TABLE', 'DB_CACHE_LOCK_TABLE',
                'DB_QUEUE_TABLE', 'DB_QUEUE_BATCH_TABLE', 'DB_QUEUE_FAILED_TABLE',
                'SESSION_TABLE', 'AUTH_PASSWORD_RESET_TOKEN_TABLE',
            ],
            'Shared root and subdomain session' => [
                'SESSION_DOMAIN', 'SESSION_COOKIE', 'SESSION_SECURE_COOKIE',
                'SESSION_ENCRYPT', 'SESSION_HTTP_ONLY', 'SESSION_SAME_SITE',
            ],
        ];
        $lines = [
            self::BLOCK_START,
            '# ------------------------------------------------------------------------------',
            '# Starterkit Larawire',
            '# ------------------------------------------------------------------------------',
        ];

        foreach ($groups as $label => $keys) {
            $lines[] = '';
            $lines[] = '# '.$label;

            foreach ($keys as $key) {
                $lines[] = $key.'='.$values[$key];
            }
        }

        $lines[] = self::BLOCK_END;

        return implode(PHP_EOL, $lines);
    }

    private function value(string $contents, string $key): string
    {
        return preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $contents, $matches) === 1
            ? trim($matches[1])
            : '';
    }

    private function read(string $path): string
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("File environment tidak dapat dibaca: {$path}");
        }

        return $contents;
    }
}
