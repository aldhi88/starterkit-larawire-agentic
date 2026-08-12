<?php

namespace Aldhi88\StarterKit\Installation;

use Illuminate\Support\Facades\File;
use RuntimeException;

class StarterHostSnapshot
{
    /** @var list<string> */
    private const PATHS = [
        '.env',
        '.env.example',
        '.gitignore',
        'AGENTS.md',
        'app',
        'bootstrap',
        'config',
        'database/migrations',
        'lang',
        'public/assets',
        'public/storage',
        'public/vendor',
        'resources/views',
        'routes',
        'storage/framework/cache/starterkit',
        'storage/app/public/starter',
        'tests',
    ];

    /** @var list<string> */
    private const MEMORY_ONLY_FILES = ['.env'];

    /** @var array<string, bool> */
    private array $existing = [];

    /** @var array<string, string> */
    private array $links = [];

    /** @var array<string, string> */
    private array $memoryFiles = [];

    /** @var array<string, int> */
    private array $fileModes = [];

    private function __construct(
        private readonly string $directory,
    ) {}

    public static function capture(): self
    {
        $directory = sys_get_temp_dir().'/starterkit-larawire-agentic-'.bin2hex(random_bytes(8));

        if (! File::makeDirectory($directory, 0700, true)) {
            throw new RuntimeException('Tidak dapat membuat snapshot instalasi sementara.');
        }

        $snapshot = new self($directory);

        foreach (self::PATHS as $relative) {
            $source = base_path($relative);
            $destination = $directory.'/'.$relative;
            $snapshot->existing[$relative] = file_exists($source) || is_link($source);

            if (! $snapshot->existing[$relative]) {
                continue;
            }

            if (is_link($source)) {
                $target = readlink($source);

                if ($target === false) {
                    throw new RuntimeException("Tidak dapat membaca symlink {$relative}.");
                }

                $snapshot->links[$relative] = $target;

                continue;
            }

            if (in_array($relative, self::MEMORY_ONLY_FILES, true)) {
                $contents = @file_get_contents($source);

                if ($contents === false) {
                    throw new RuntimeException("Tidak dapat membaca snapshot file {$relative}.");
                }

                $snapshot->memoryFiles[$relative] = $contents;
                $snapshot->fileModes[$relative] = fileperms($source) & 0777;

                continue;
            }

            File::ensureDirectoryExists(dirname($destination));

            if (is_dir($source)) {
                if (! File::copyDirectory($source, $destination)) {
                    throw new RuntimeException("Tidak dapat membuat snapshot directory {$relative}.");
                }

                continue;
            }

            if (! File::copy($source, $destination)) {
                throw new RuntimeException("Tidak dapat membuat snapshot file {$relative}.");
            }
        }

        File::put($directory.'/.manifest.json', json_encode([
            'existing' => $snapshot->existing,
            'links' => $snapshot->links,
        ],
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR,
        ));

        return $snapshot;
    }

    public function restore(): void
    {
        foreach (array_reverse(self::PATHS) as $relative) {
            $target = base_path($relative);

            if (is_dir($target) && ! is_link($target)) {
                File::deleteDirectory($target);
            } elseif (file_exists($target) || is_link($target)) {
                File::delete($target);
            }

            if (! ($this->existing[$relative] ?? false)) {
                continue;
            }

            if (array_key_exists($relative, $this->links)) {
                File::ensureDirectoryExists(dirname($target));

                if (! symlink($this->links[$relative], $target)) {
                    throw new RuntimeException("Tidak dapat memulihkan symlink {$relative}.");
                }

                continue;
            }

            if (array_key_exists($relative, $this->memoryFiles)) {
                File::ensureDirectoryExists(dirname($target));

                if (file_put_contents($target, $this->memoryFiles[$relative], LOCK_EX) === false) {
                    throw new RuntimeException("Tidak dapat memulihkan file {$relative} dari memory.");
                }

                chmod($target, $this->fileModes[$relative] ?? 0600);

                continue;
            }

            $source = $this->directory.'/'.$relative;
            File::ensureDirectoryExists(dirname($target));

            if (is_dir($source)) {
                if (! File::copyDirectory($source, $target)) {
                    throw new RuntimeException("Tidak dapat memulihkan directory {$relative}.");
                }

                continue;
            }

            if (! File::copy($source, $target)) {
                throw new RuntimeException("Tidak dapat memulihkan file {$relative}.");
            }
        }
    }

    public function discard(): void
    {
        File::deleteDirectory($this->directory);
    }
}
