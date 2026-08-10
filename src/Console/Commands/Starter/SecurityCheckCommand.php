<?php

namespace Altekno\StarterKit\Console\Commands\Starter;

use Altekno\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;

#[Signature('starter:security-check
    {--production : Enforce every production-only security requirement}')]
#[Description('Validate application security configuration without changing server state')]
class SecurityCheckCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $production = app()->isProduction() || (bool) $this->option('production');
        $checks = $this->baseChecks();

        if ($production) {
            $checks = [...$checks, ...$this->productionChecks()];
        }

        $rows = collect($checks)
            ->map(fn (array $check): array => [
                $check['label'],
                $check['passed'] ? 'PASS' : 'FAIL',
                $check['detail'],
            ])
            ->all();

        $this->table(['Check', 'Status', 'Detail'], $rows);

        $failures = collect($checks)->where('passed', false);

        if ($failures->isNotEmpty()) {
            $this->error('Security check failed. Fix every FAIL before deployment.');

            return self::FAILURE;
        }

        $this->info($production
            ? 'Production security configuration is valid.'
            : 'Local security configuration is valid. Use --production to simulate production requirements.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{label: string, passed: bool, detail: string}>
     */
    private function baseChecks(): array
    {
        $sameSite = strtolower((string) config('session.same_site'));

        return [
            $this->check(
                'Application encryption key',
                $this->hasValidApplicationKey(),
                'APP_KEY must match the configured cipher.',
            ),
            $this->check(
                'Encrypted session payload',
                config('session.encrypt') === true,
                'SESSION_ENCRYPT must be true.',
            ),
            $this->check(
                'HTTP-only session cookie',
                config('session.http_only') === true,
                'SESSION_HTTP_ONLY must be true.',
            ),
            $this->check(
                'SameSite session cookie',
                in_array($sameSite, ['lax', 'strict'], true),
                'SESSION_SAME_SITE must be lax or strict.',
            ),
            $this->check(
                'Application domain consistency',
                $this->domainMatchesApplicationUrl(),
                'APP_URL host must equal APP_DOMAIN.',
            ),
            $this->check(
                'Starter UI theme',
                class_exists(StarterTheme::powerGridTheme()),
                'STARTER_THEME must reference a registered theme with a PowerGrid adapter.',
            ),
            $this->check(
                'Starter theme layout registry',
                StarterTheme::hasCompleteLayoutRegistry(),
                'The active theme must provide existing Blade views for vertical and horizontal layouts.',
            ),
            $this->check(
                'Starter UI layout',
                StarterTheme::hasLayoutView((string) config('starter.layout')),
                'STARTER_LAYOUT must be registered by the active theme and its Blade view must exist.',
            ),
        ];
    }

    /**
     * @return list<array{label: string, passed: bool, detail: string}>
     */
    private function productionChecks(): array
    {
        $domain = strtolower(trim((string) config('app.domain'), '.'));
        $scheme = strtolower((string) parse_url((string) config('app.url'), PHP_URL_SCHEME));
        $superuserPassword = (string) config('starter.superuser.password');

        return [
            $this->check(
                'Production environment',
                app()->isProduction(),
                'APP_ENV must be production.',
            ),
            $this->check(
                'Debug mode disabled',
                config('app.debug') === false,
                'APP_DEBUG must be false.',
            ),
            $this->check(
                'HTTPS application URL',
                $scheme === 'https',
                'APP_URL must use https.',
            ),
            $this->check(
                'Secure session cookie',
                config('session.secure') === true,
                'SESSION_SECURE_COOKIE must be true.',
            ),
            $this->check(
                'Production application domain',
                $domain !== '' && ! in_array($domain, ['localhost', '127.0.0.1'], true),
                'APP_DOMAIN must contain the production root domain.',
            ),
            $this->check(
                'Superuser password is not local default',
                ! in_array($superuserPassword, ['superuser123', 'rahasia123'], true),
                'STARTER_SUPERUSER_PASSWORD must never use the local default.',
            ),
            $this->check(
                'Internationalization extension',
                extension_loaded('intl'),
                'PHP extension intl must be available.',
            ),
            $this->check(
                'Writable runtime directories',
                is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')),
                'storage and bootstrap/cache must be writable.',
            ),
        ];
    }

    /**
     * @return array{label: string, passed: bool, detail: string}
     */
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
}
