<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;

class StarterSecurityValidator
{
    public function validate(Command $command, bool $production = false): bool
    {
        $checks = $this->checks($production);
        $command->table(['Pemeriksaan', 'Status', 'Keterangan'], array_map(
            fn (array $check): array => [
                $check['label'],
                $check['passed'] ? '<fg=green;options=bold>PASS</>' : '<fg=red;options=bold>FAIL</>',
                $check['detail'],
            ],
            $checks,
        ));

        if (collect($checks)->contains('passed', false)) {
            $command->line('<fg=red;options=bold>Validasi keamanan gagal. Perbaiki seluruh status FAIL sebelum melanjutkan.</>');

            return false;
        }

        $command->info($production
            ? 'Seluruh validasi environment production berhasil.'
            : 'Seluruh validasi keamanan aplikasi berhasil.');

        return true;
    }

    /** @return list<array{label: string, passed: bool, detail: string}> */
    public function checks(bool $production = false): array
    {
        $sameSite = strtolower((string) config('session.same_site'));
        $checks = [
            $this->check(
                'Application encryption key',
                $this->hasValidApplicationKey(),
                'APP_KEY harus sesuai dengan cipher aplikasi.',
            ),
            $this->check(
                'Encrypted session payload',
                config('session.encrypt') === true,
                'SESSION_ENCRYPT harus true.',
            ),
            $this->check(
                'HTTP-only session cookie',
                config('session.http_only') === true,
                'SESSION_HTTP_ONLY harus true.',
            ),
            $this->check(
                'SameSite session cookie',
                in_array($sameSite, ['lax', 'strict'], true),
                'SESSION_SAME_SITE harus lax atau strict.',
            ),
            $this->check(
                'Application domain consistency',
                $this->domainMatchesApplicationUrl(),
                'Host APP_URL harus sama dengan APP_DOMAIN.',
            ),
            $this->check(
                'Starter UI theme',
                class_exists(StarterTheme::powerGridTheme()),
                'STARTER_THEME harus terdaftar dan memiliki adapter PowerGrid.',
            ),
            $this->check(
                'Starter theme layout registry',
                StarterTheme::hasCompleteLayoutRegistry(),
                'Theme aktif harus menyediakan layout vertical dan horizontal.',
            ),
            $this->check(
                'Starter UI layout',
                StarterTheme::hasLayoutView((string) config('starter.layout')),
                'STARTER_LAYOUT harus terdaftar dan view-nya tersedia.',
            ),
            $this->check(
                'Database session driver',
                config('session.driver') === 'database',
                'SESSION_DRIVER harus database agar session root dan subdomain konsisten.',
            ),
            $this->check(
                'Database cache driver',
                config('cache.default') === 'database',
                'CACHE_STORE harus database.',
            ),
            $this->check(
                'Synchronous queue driver',
                config('queue.default') === 'sync',
                'QUEUE_CONNECTION harus sync pada baseline shared hosting.',
            ),
        ];

        return $production ? [...$checks, ...$this->productionChecks()] : $checks;
    }

    /** @return list<array{label: string, passed: bool, detail: string}> */
    private function productionChecks(): array
    {
        $domain = strtolower(trim((string) config('app.domain'), '.'));
        $scheme = strtolower((string) parse_url((string) config('app.url'), PHP_URL_SCHEME));

        return [
            $this->check('Production environment', app()->isProduction(), 'APP_ENV harus production.'),
            $this->check('Debug mode disabled', config('app.debug') === false, 'APP_DEBUG harus false.'),
            $this->check('HTTPS application URL', $scheme === 'https', 'APP_URL harus menggunakan HTTPS.'),
            $this->check('Secure session cookie', config('session.secure') === true, 'SESSION_SECURE_COOKIE harus true.'),
            $this->check(
                'Production application domain',
                $domain !== '' && ! in_array($domain, ['localhost', '127.0.0.1'], true),
                'APP_DOMAIN harus berisi root domain production.',
            ),
            $this->check('Internationalization extension', extension_loaded('intl'), 'PHP extension intl wajib tersedia.'),
            $this->check(
                'Database PDO extension',
                $this->databaseExtensionIsLoaded(),
                'PHP extension untuk driver database aktif wajib tersedia.',
            ),
            $this->check(
                'Writable runtime directories',
                is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')),
                'storage dan bootstrap/cache harus writable.',
            ),
        ];
    }

    /** @return array{label: string, passed: bool, detail: string} */
    private function check(string $label, bool $passed, string $detail): array
    {
        return compact('label', 'passed', 'detail');
    }

    private function hasValidApplicationKey(): bool
    {
        $key = (string) config('app.key');

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true) ?: '';
        }

        return Encrypter::supported($key, (string) config('app.cipher'));
    }

    private function domainMatchesApplicationUrl(): bool
    {
        $urlHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $domain = strtolower(trim((string) config('app.domain'), '.'));

        return $urlHost !== '' && $domain !== '' && hash_equals($domain, $urlHost);
    }

    private function databaseExtensionIsLoaded(): bool
    {
        $extension = match ((string) config('database.connections.'.config('database.default').'.driver')) {
            'sqlite' => 'pdo_sqlite',
            'mysql', 'mariadb' => 'pdo_mysql',
            'pgsql' => 'pdo_pgsql',
            'sqlsrv' => 'pdo_sqlsrv',
            default => null,
        };

        return is_string($extension) && extension_loaded($extension);
    }
}
