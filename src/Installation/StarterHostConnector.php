<?php

namespace Aldhi88\StarterKit\Installation;

use Aldhi88\StarterKit\Providers\Starter\StarterServiceProvider;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use RuntimeException;

class StarterHostConnector
{
    private const AGENTS_BLOCK_START = '<!-- starterkit:agentic-connector:start -->';

    private const AGENTS_BLOCK_END = '<!-- starterkit:agentic-connector:end -->';

    public function __construct(
        private readonly StarterEnvironmentManager $environment,
    ) {}

    public function connect(string $theme = 'tabler', string $layout = 'vertical'): void
    {
        $this->assertRequiredFiles();
        $this->removeDefaultMigrations();
        $this->configureFrameworkTableNames();
        $this->write(
            base_path('bootstrap/app.php'),
            $this->read(StarterPaths::path('installer/templates/bootstrap-app.php')),
        );
        $this->connectProvider();
        $this->connectAgents();
        $this->ensureGitIgnored([
            '/public/assets/starter/',
            '/public/assets/'.$theme.'/',
            '/public/vendor/',
        ]);
        $this->environment->apply(base_path('.env'), theme: $theme, layout: $layout);
        $this->environment->apply(
            base_path('.env.example'),
            example: true,
            theme: $theme,
            layout: $layout,
        );
        StarterInstallState::write('installing');
    }

    private function assertRequiredFiles(): void
    {
        foreach ([
            'artisan',
            '.env',
            '.env.example',
            'bootstrap/app.php',
            'bootstrap/providers.php',
            'config/database.php',
            'config/cache.php',
            'config/queue.php',
            'config/session.php',
        ] as $relative) {
            $path = base_path($relative);

            if (! is_file($path) || ! is_readable($path) || ! is_writable($path)) {
                throw new RuntimeException("File Laravel wajib tidak tersedia atau tidak writable: {$relative}");
            }
        }
    }

    private function removeDefaultMigrations(): void
    {
        foreach (glob(database_path('migrations/*.php')) ?: [] as $path) {
            $name = pathinfo($path, PATHINFO_FILENAME);

            if (! str_contains($name, 'create_users_table')
                && ! str_contains($name, 'create_cache_table')
                && ! str_contains($name, 'create_jobs_table')) {
                continue;
            }

            if (! unlink($path)) {
                throw new RuntimeException('Tidak dapat menghapus migration bawaan Laravel: '.basename($path));
            }
        }
    }

    private function configureFrameworkTableNames(): void
    {
        $sessionDomain = <<<'PHP'
'domain' => env('SESSION_DOMAIN') ?: \Aldhi88\StarterKit\Support\Starter\StarterDomain::sessionDomain((string) env('APP_URL')),
PHP;
        $secureCookie = <<<'PHP'
'secure' => env('SESSION_SECURE_COOKIE') ?? \Aldhi88\StarterKit\Support\Starter\StarterDomain::secureCookie((string) env('APP_URL')),
PHP;
        $replacements = [
            'config/database.php' => [
                "'table' => 'migrations'," => "'table' => env('DB_MIGRATIONS_TABLE', 'x_migrations'),",
            ],
            'config/cache.php' => [
                "'table' => env('DB_CACHE_TABLE', 'cache')," => "'table' => env('DB_CACHE_TABLE', 'x_cache'),",
                "'lock_table' => env('DB_CACHE_LOCK_TABLE')," => "'lock_table' => env('DB_CACHE_LOCK_TABLE', 'x_cache_locks'),",
            ],
            'config/queue.php' => [
                "'table' => env('DB_QUEUE_TABLE', 'jobs')," => "'table' => env('DB_QUEUE_TABLE', 'x_jobs'),",
                "'table' => 'job_batches'," => "'table' => env('DB_QUEUE_BATCH_TABLE', 'x_job_batches'),",
                "'table' => 'failed_jobs'," => "'table' => env('DB_QUEUE_FAILED_TABLE', 'x_failed_jobs'),",
            ],
            'config/session.php' => [
                "'table' => env('SESSION_TABLE', 'sessions')," => "'table' => env('SESSION_TABLE', 'x_sessions'),",
                "'domain' => env('SESSION_DOMAIN')," => $sessionDomain,
                "'secure' => env('SESSION_SECURE_COOKIE')," => $secureCookie,
            ],
        ];

        foreach ($replacements as $relative => $pairs) {
            $path = base_path($relative);
            $contents = $this->read($path);

            foreach ($pairs as $from => $to) {
                if (str_contains($contents, $to)) {
                    continue;
                }

                if (! str_contains($contents, $from)) {
                    throw new RuntimeException("Struktur {$relative} tidak didukung oleh installer.");
                }

                $contents = str_replace($from, $to, $contents);
            }

            $this->write($path, $contents);
        }
    }

    private function connectProvider(): void
    {
        $path = base_path('bootstrap/providers.php');
        $contents = $this->read($path);

        if (str_contains($contents, 'StarterServiceProvider::class')) {
            return;
        }

        $use = 'use '.StarterServiceProvider::class.';';
        $contents = preg_replace('/<\?php\s*/', "<?php\n\n{$use}\n", $contents, 1, $count);

        if ($contents === null || $count !== 1 || ! str_contains($contents, 'return [')) {
            throw new RuntimeException('StarterServiceProvider tidak dapat didaftarkan.');
        }

        $contents = preg_replace(
            '/return\s*\[\s*/',
            "return [\n    StarterServiceProvider::class,\n",
            $contents,
            1,
        );

        if ($contents === null) {
            throw new RuntimeException('StarterServiceProvider tidak dapat ditulis.');
        }

        $this->write($path, $contents);
    }

    private function connectAgents(): void
    {
        $path = base_path('AGENTS.md');
        $contents = is_file($path) ? $this->read($path) : '';
        $connector = trim($this->read(StarterPaths::path('installer/templates/agents-connector.md')));
        $block = self::AGENTS_BLOCK_START.PHP_EOL
            .$connector.PHP_EOL
            .self::AGENTS_BLOCK_END;
        $pattern = '/'.preg_quote(self::AGENTS_BLOCK_START, '/').'.*?'
            .preg_quote(self::AGENTS_BLOCK_END, '/').'/s';

        if (preg_match($pattern, $contents) === 1) {
            $contents = preg_replace($pattern, $block, $contents, 1) ?? $contents;
        } else {
            $contents = trim($contents) === ''
                ? $block.PHP_EOL
                : rtrim($contents).PHP_EOL.PHP_EOL.$block.PHP_EOL;
        }

        $this->write($path, $contents);
    }

    /** @param list<string> $entries */
    private function ensureGitIgnored(array $entries): void
    {
        $path = base_path('.gitignore');
        $contents = is_file($path) ? $this->read($path) : '';
        $lines = preg_split('/\R/', $contents) ?: [];

        foreach ($entries as $entry) {
            if (! in_array($entry, $lines, true)) {
                $lines[] = $entry;
            }
        }

        $this->write($path, rtrim(implode(PHP_EOL, $lines)).PHP_EOL);
    }

    private function read(string $path): string
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("File tidak dapat dibaca: {$path}");
        }

        return $contents;
    }

    private function write(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new RuntimeException("File tidak dapat ditulis: {$path}");
        }
    }
}
