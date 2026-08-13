<?php

namespace Aldhi88\StarterKit\Installation;

use Illuminate\Support\Facades\DB;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class FreshLaravelChecker
{
    public const MINIMUM_LARAVEL = '13.8.0';

    public function __construct(
        private readonly ?string $root = null,
        private readonly ?string $laravelVersion = null,
    ) {}

    /** @return list<string> */
    public function sourceFindings(): array
    {
        $findings = [];

        $laravelVersion = $this->laravelVersion ?? (string) app()->version();

        if (version_compare($laravelVersion, self::MINIMUM_LARAVEL, '<')) {
            $findings[] = sprintf(
                'Versi Laravel %s belum memenuhi versi minimum %s.',
                $laravelVersion,
                self::MINIMUM_LARAVEL,
            );
        }

        if (is_file($this->path(StarterInstallState::PATH))) {
            $findings[] = 'Starterkit pernah memulai atau menyelesaikan instalasi pada project ini.';
        }

        $findings = [
            ...$findings,
            ...$this->unexpectedFiles($this->path('app'), [
                'Http/Controllers/Controller.php',
                'Models/User.php',
                'Providers/AppServiceProvider.php',
            ], 'app'),
            ...$this->unexpectedFiles($this->path('resources/views'), [
                'welcome.blade.php',
                'welcome.php',
            ], 'resources/views'),
            ...$this->unexpectedMigrations(),
        ];

        if (! $this->hasFreshProviders()) {
            $findings[] = 'bootstrap/providers.php sudah memiliki provider selain AppServiceProvider bawaan.';
        }

        if (! $this->hasFreshBootstrap()) {
            $findings[] = 'bootstrap/app.php sudah dikustomisasi atau strukturnya belum didukung.';
        }

        if (! $this->hasFreshWebRoutes()) {
            $findings[] = 'routes/web.php sudah memiliki route aplikasi.';
        }

        if (! $this->hasFreshConsoleRoutes()) {
            $findings[] = 'routes/console.php sudah memiliki command atau schedule aplikasi.';
        }

        if (! $this->hasFreshController()) {
            $findings[] = 'Controller bawaan Laravel sudah dikustomisasi.';
        }

        if (! $this->hasFreshAppServiceProvider()) {
            $findings[] = 'AppServiceProvider bawaan Laravel sudah dikustomisasi.';
        }

        if (! $this->hasFreshUserModel()) {
            $findings[] = 'Model User bawaan Laravel sudah dikustomisasi.';
        }

        return array_values(array_unique($findings));
    }

    public function inspectDatabase(): FreshLaravelReport
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'Koneksi database gagal: '.$exception->getMessage(),
                previous: $exception,
            );
        }

        $migrationsHaveRun = false;

        try {
            $connection = DB::connection();
            $schema = in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)
                ? $connection->getDatabaseName()
                : null;
            $migrationsHaveRun = $connection->getSchemaBuilder()->getTableListing($schema) !== [];
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'Status migration tidak dapat diperiksa: '.$exception->getMessage(),
                previous: $exception,
            );
        }

        return new FreshLaravelReport($this->sourceFindings(), $migrationsHaveRun);
    }

    /** @param list<string> $allowed
     * @return list<string>
     */
    private function unexpectedFiles(string $directory, array $allowed, string $label): array
    {
        if (! is_dir($directory)) {
            return ["Directory {$label} tidak ditemukan."];
        }

        $unexpected = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $item) {
            if (! $item->isFile()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($directory) + 1));

            if (in_array(basename($relative), ['.DS_Store', '.gitkeep'], true)
                || in_array($relative, $allowed, true)) {
                continue;
            }

            $unexpected[] = $relative;
        }

        sort($unexpected);

        return $unexpected === []
            ? []
            : ["{$label} memiliki file aplikasi: ".implode(', ', $unexpected)];
    }

    /** @return list<string> */
    private function unexpectedMigrations(): array
    {
        $directory = $this->path('database/migrations');

        if (! is_dir($directory)) {
            return [];
        }

        $unexpected = [];

        foreach (glob($directory.'/*.php') ?: [] as $path) {
            $name = pathinfo($path, PATHINFO_FILENAME);

            if (! $this->migrationIsDefault($name, $this->read($path))) {
                $unexpected[] = basename($path);
            }
        }

        sort($unexpected);

        return $unexpected === []
            ? []
            : ['database/migrations memiliki migration aplikasi: '.implode(', ', $unexpected)];
    }

    private function hasFreshProviders(): bool
    {
        $contents = $this->read($this->path('bootstrap/providers.php'));
        $normalized = $this->withoutComments($contents);

        return str_contains($normalized, 'AppServiceProvider::class')
            && substr_count($normalized, '::class') === 1
            && ! str_contains($normalized, 'StarterServiceProvider');
    }

    private function hasFreshBootstrap(): bool
    {
        $contents = $this->read($this->path('bootstrap/app.php'));

        if (! str_contains($contents, 'Application::configure(basePath: dirname(__DIR__))')
            || ! str_contains($contents, '->withRouting(')
            || ! str_contains($contents, '->withMiddleware(')
            || ! str_contains($contents, '->withExceptions(')
            || ! str_contains($contents, ')->create()')
            || str_contains($contents, 'StarterBootstrap')) {
            return false;
        }

        foreach (['withMiddleware', 'withExceptions'] as $method) {
            if (! $this->callbackIsFresh($contents, $method)) {
                return false;
            }
        }

        return true;
    }

    private function hasFreshWebRoutes(): bool
    {
        $contents = $this->read($this->path('routes/web.php'));
        $welcomeClosure = <<<'REGEX'
~Route::get\(\s*['"]\/['"]\s*,\s*function\s*\(\)\s*\{\s*return\s+view\(\s*['"]welcome['"]\s*\)\s*;\s*\}\s*\)\s*;~
REGEX;
        $welcomeView = '~Route::view\(\s*[\'"]\/[\'"]\s*,\s*[\'"]welcome[\'"]\s*\)\s*;~';
        $remaining = preg_replace([$welcomeClosure, $welcomeView], '', $contents);

        if ($remaining === null || $remaining === $contents) {
            return false;
        }

        $remaining = preg_replace([
            '/<\?php/',
            '/use\s+Illuminate\\\\Support\\\\Facades\\\\Route\s*;/',
        ], '', $this->withoutComments($remaining));

        return trim((string) $remaining) === '';
    }

    private function hasFreshConsoleRoutes(): bool
    {
        $contents = $this->withoutComments($this->read($this->path('routes/console.php')));
        $contents = preg_replace([
            '/<\?php/',
            '/use\s+Illuminate\\\\Foundation\\\\Inspiring\s*;/',
            '/use\s+Illuminate\\\\Support\\\\Facades\\\\Artisan\s*;/',
            '~Artisan::command\(\s*[\'"]inspire[\'"]\s*,\s*function\s*\(\)\s*\{.*?\}\s*\)\s*->purpose\(.*?\)\s*;~s',
        ], '', $contents);

        return trim((string) $contents) === '';
    }

    private function hasFreshController(): bool
    {
        $contents = $this->withoutComments($this->read($this->path('app/Http/Controllers/Controller.php')));

        return preg_match(
            '/class\s+Controller(?:\s+extends\s+BaseController)?\s*\{\s*\}/s',
            $contents,
        ) === 1;
    }

    private function hasFreshAppServiceProvider(): bool
    {
        $contents = $this->withoutComments($this->read($this->path('app/Providers/AppServiceProvider.php')));

        if (! str_contains($contents, 'class AppServiceProvider extends ServiceProvider')) {
            return false;
        }

        preg_match_all('/public\s+function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\([^)]*\)[^{]*\{(?P<body>.*?)\}/s', $contents, $matches);

        return $matches[1] === ['register', 'boot']
            && collect($matches['body'])->every(fn (string $body): bool => trim($body) === '');
    }

    private function hasFreshUserModel(): bool
    {
        $contents = $this->withoutComments($this->read($this->path('app/Models/User.php')));

        if (! str_contains($contents, 'class User extends Authenticatable')) {
            return false;
        }

        preg_match_all('/function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $contents, $methods);

        $declaredMethods = array_values(array_unique($methods[1]));

        if (array_diff($declaredMethods, ['casts']) !== []) {
            return false;
        }

        $fillable = $this->modelArray($contents, 'Fillable', 'fillable');
        $hidden = $this->modelArray($contents, 'Hidden', 'hidden');

        if ($fillable !== ['name', 'email', 'password']
            || $hidden !== ['password', 'remember_token']
            || ! str_contains($contents, "'email_verified_at'")
            || ! str_contains($contents, "'password'")) {
            return false;
        }

        return true;
    }

    private function migrationIsDefault(string $name, string $contents): bool
    {
        $allowedTables = match (true) {
            str_contains($name, 'create_users_table') => [
                'users', 'password_reset_tokens', 'sessions',
            ],
            str_contains($name, 'create_cache_table') => [
                'cache', 'cache_locks',
            ],
            str_contains($name, 'create_jobs_table') => [
                'jobs', 'job_batches', 'failed_jobs',
            ],
            default => [],
        };

        if ($allowedTables === []) {
            return false;
        }

        preg_match_all(
            '/Schema::(?:create|table|dropIfExists)\(\s*[\'"](?P<table>[^\'"]+)[\'"]/',
            $contents,
            $matches,
        );
        $tables = array_values(array_unique($matches['table']));

        return $tables !== [] && array_diff($tables, $allowedTables) === [];
    }

    /** @return list<string> */
    private function modelArray(string $contents, string $attribute, string $property): array
    {
        $pattern = '/#\['.preg_quote($attribute, '/').'\(\[(?P<values>.*?)\]\)\]/s';

        if (preg_match($pattern, $contents, $matches) !== 1) {
            $pattern = '/\$'.preg_quote($property, '/').'\s*=\s*\[(?P<values>.*?)\]/s';

            if (preg_match($pattern, $contents, $matches) !== 1) {
                return [];
            }
        }

        preg_match_all('/[\'"](?P<value>[A-Za-z0-9_]+)[\'"]/', $matches['values'], $values);

        return array_values(array_unique($values['value']));
    }

    private function callbackIsFresh(string $contents, string $method): bool
    {
        if (preg_match(
            '/->'.preg_quote($method, '/').'\(\s*function\s*\([^)]*\)\s*(?::\s*void\s*)?\{(?P<body>.*?)\}\s*\)/s',
            $contents,
            $matches,
        ) !== 1) {
            return false;
        }

        $body = $this->withoutComments($matches['body']);

        if ($method === 'withExceptions') {
            $body = preg_replace(
                '~\$[A-Za-z_][A-Za-z0-9_]*->shouldRenderJsonWhen\(\s*fn\s*\([^)]*\)\s*=>\s*\$[A-Za-z_][A-Za-z0-9_]*->is\(\s*[\'"]api/\*[\'"]\s*\)(?:\s*\|\|\s*\$[A-Za-z_][A-Za-z0-9_]*->expectsJson\(\s*\))?\s*,?\s*\)\s*;~s',
                '',
                $body,
            ) ?? $body;
        }

        return trim($body) === '';
    }

    private function withoutComments(string $contents): string
    {
        return (string) preg_replace([
            '~/\*.*?\*/~s',
            '/^\s*\/\/.*$/m',
            '/^\s*#(?!\[).*$/m',
        ], '', $contents);
    }

    private function read(string $path): string
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("File wajib tidak dapat dibaca: {$path}");
        }

        return $contents;
    }

    private function path(string $path = ''): string
    {
        $root = $this->root ?? base_path();

        return rtrim($root, '/\\').($path === '' ? '' : DIRECTORY_SEPARATOR.ltrim($path, '/\\'));
    }
}
