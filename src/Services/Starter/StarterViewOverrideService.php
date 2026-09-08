<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class StarterViewOverrideService
{
    public const MANIFEST = '.starter-views.json';

    public function __construct(private readonly Filesystem $files) {}

    public function sourceRoot(?string $theme = null): string
    {
        return StarterTheme::viewPath('starter');
    }

    public function overrideThemeRoot(?string $theme = null): string
    {
        $theme ??= StarterTheme::key();

        return resource_path("views/vendor/starterkit-larawire/{$theme}");
    }

    public function overrideRoot(?string $theme = null): string
    {
        return $this->overrideThemeRoot($theme).DIRECTORY_SEPARATOR.'starter';
    }

    public function layoutsRoot(?string $theme = null): string
    {
        return $this->overrideRoot($theme).DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'layouts';
    }

    public function hasOverrides(?string $theme = null): bool
    {
        return $this->files->isDirectory($this->overrideRoot($theme));
    }

    /** @return array<string, array{path: string, status: string, project_sha256: ?string, package_sha256: ?string, baseline_sha256: ?string}> */
    public function statuses(?string $theme = null): array
    {
        $theme ??= StarterTheme::key();
        $source = $this->viewHashes($this->sourceRoot($theme));
        $project = $this->viewHashes($this->overrideRoot($theme));
        $baseline = $this->manifest($theme)['files'];
        $paths = array_values(array_unique([...array_keys($source), ...array_keys($project)]));
        sort($paths);
        $statuses = [];

        foreach ($paths as $path) {
            $packageHash = $source[$path] ?? null;
            $projectHash = $project[$path] ?? null;
            $baselineHash = is_string($baseline[$path] ?? null) ? $baseline[$path] : null;

            $statuses[$path] = [
                'path' => $path,
                'status' => $this->classify($projectHash, $packageHash, $baselineHash),
                'project_sha256' => $projectHash,
                'package_sha256' => $packageHash,
                'baseline_sha256' => $baselineHash,
            ];
        }

        return $statuses;
    }

    /**
     * @param  list<string>  $paths
     * @return array{created: int, replaced: int, preserved: int, backup: ?string}
     */
    public function publish(array $paths = [], bool $force = false): array
    {
        $theme = StarterTheme::key();
        $source = $this->viewHashes($this->sourceRoot($theme));
        $selected = $this->selectPaths($paths, array_keys($source));
        $manifest = $this->manifest($theme);
        $baseline = $manifest['files'];
        $created = $replaced = $preserved = 0;
        $needsBackup = false;

        foreach ($selected as $path) {
            $target = $this->targetPath($path, $theme);
            $this->assertSafeTarget($target, $theme);

            if ($this->files->exists($target) && hash_file('sha256', $target) !== $source[$path]) {
                $needsBackup = $needsBackup || $force;
            }
        }

        $backup = $needsBackup ? $this->backup($theme) : null;

        foreach ($selected as $path) {
            $target = $this->targetPath($path, $theme);
            $this->assertSafeTarget($target, $theme);
            $exists = $this->files->exists($target);

            if ($exists && ! $force && hash_file('sha256', $target) !== $source[$path]) {
                $baseline[$path] ??= $source[$path];
                $preserved++;

                continue;
            }

            $this->copySource($path, $target, $theme);
            $baseline[$path] = $source[$path];
            $exists ? $replaced++ : $created++;
        }

        $this->writeManifest($theme, $baseline);

        return compact('created', 'replaced', 'preserved', 'backup');
    }

    /**
     * @param  list<string>  $paths
     * @return array<string, string>
     */
    public function replacementPlan(array $paths = []): array
    {
        $theme = StarterTheme::key();
        $source = $this->viewHashes($this->sourceRoot($theme));
        $project = $this->viewHashes($this->overrideRoot($theme));
        $available = array_values(array_unique([...array_keys($source), ...array_keys($project)]));
        $selected = $this->selectPaths($paths, $available);
        $plan = [];

        foreach ($selected as $path) {
            if (! isset($source[$path])) {
                $plan[$path] = 'delete';
            } elseif (! isset($project[$path])) {
                $plan[$path] = 'create';
            } elseif ($project[$path] !== $source[$path]) {
                $plan[$path] = 'replace';
            }
        }

        return $plan;
    }

    /**
     * @param  list<string>  $paths
     * @return array{created: int, replaced: int, deleted: int, backup: ?string}
     */
    public function replace(array $paths = []): array
    {
        $theme = StarterTheme::key();

        if (! $this->hasOverrides($theme)) {
            throw new RuntimeException('Belum ada view override. Jalankan php artisan starter:views-publish terlebih dahulu.');
        }

        $plan = $this->replacementPlan($paths);

        if ($plan === []) {
            return ['created' => 0, 'replaced' => 0, 'deleted' => 0, 'backup' => null];
        }

        $backup = $this->backup($theme);
        $source = $this->viewHashes($this->sourceRoot($theme));
        $manifest = $this->manifest($theme);
        $baseline = $manifest['files'];
        $created = $replaced = $deleted = 0;

        foreach ($plan as $path => $action) {
            $target = $this->targetPath($path, $theme);
            $this->assertSafeTarget($target, $theme);

            if ($action === 'delete') {
                $this->files->delete($target);
                unset($baseline[$path]);
                $deleted++;

                continue;
            }

            $this->copySource($path, $target, $theme);
            $baseline[$path] = $source[$path];
            $action === 'create' ? $created++ : $replaced++;
        }

        $this->writeManifest($theme, $baseline);

        return compact('created', 'replaced', 'deleted', 'backup');
    }

    /** @return list<string> */
    public function availablePaths(): array
    {
        $paths = array_values(array_unique([
            ...array_keys($this->viewHashes($this->sourceRoot())),
            ...array_keys($this->viewHashes($this->overrideRoot())),
        ]));
        sort($paths);

        return $paths;
    }

    /** @return list<string> */
    public function sourcePaths(): array
    {
        return array_keys($this->viewHashes($this->sourceRoot()));
    }

    private function classify(?string $project, ?string $package, ?string $baseline): string
    {
        if ($project === null) {
            return 'package-only';
        }

        if ($package === null) {
            return 'removed-from-package';
        }

        if (hash_equals($project, $package)) {
            return 'current';
        }

        if ($baseline === null) {
            return 'custom-untracked';
        }

        if (hash_equals($project, $baseline)) {
            return 'update-available';
        }

        if (hash_equals($package, $baseline)) {
            return 'customized';
        }

        return 'conflict';
    }

    /** @return array<string, string> */
    private function viewHashes(string $root): array
    {
        if (! $this->files->isDirectory($root)) {
            return [];
        }

        $hashes = [];

        foreach ($this->files->allFiles($root) as $file) {
            if ($file->getExtension() !== 'php' || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $path = str_replace('\\', '/', $file->getRelativePathname());
            $hashes[$path] = hash_file('sha256', $file->getPathname());
        }

        ksort($hashes);

        return $hashes;
    }

    /** @return array{schema: int, theme: string, files: array<string, string>} */
    private function manifest(string $theme): array
    {
        $path = $this->overrideThemeRoot($theme).DIRECTORY_SEPARATOR.self::MANIFEST;

        if (! $this->files->exists($path)) {
            return ['schema' => 1, 'theme' => $theme, 'files' => []];
        }

        $manifest = json_decode($this->files->get($path), true);

        if (! is_array($manifest) || ($manifest['schema'] ?? null) !== 1 || ($manifest['theme'] ?? null) !== $theme) {
            throw new RuntimeException("Manifest view override tidak valid: {$path}");
        }

        return $manifest;
    }

    /** @param  array<string, string>  $baseline */
    private function writeManifest(string $theme, array $baseline): void
    {
        ksort($baseline);
        $root = $this->overrideThemeRoot($theme);
        $manifest = $root.DIRECTORY_SEPARATOR.self::MANIFEST;
        $this->assertSafeTarget($manifest, $theme);
        $this->files->ensureDirectoryExists($root);
        $contents = json_encode([
            'schema' => 1,
            'theme' => $theme,
            'files' => $baseline,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
        $this->files->put($manifest, $contents, true);
    }

    /**
     * @param  list<string>  $requested
     * @param  list<string>  $available
     * @return list<string>
     */
    private function selectPaths(array $requested, array $available): array
    {
        sort($available);

        if ($requested === []) {
            return $available;
        }

        $selected = [];

        foreach ($requested as $path) {
            $path = str_replace('\\', '/', trim($path));

            if (! in_array($path, $available, true)) {
                throw new RuntimeException("View override tidak dikenal: {$path}");
            }

            $selected[] = $path;
        }

        return array_values(array_unique($selected));
    }

    private function targetPath(string $path, string $theme): string
    {
        return $this->overrideRoot($theme).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    private function copySource(string $path, string $target, string $theme): void
    {
        $source = $this->sourceRoot($theme).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
        $this->assertSafeTarget($target, $theme);
        $this->files->ensureDirectoryExists(dirname($target));

        if (! $this->files->copy($source, $target)) {
            throw new RuntimeException("Gagal menyalin view override: {$path}");
        }
    }

    private function backup(string $theme): ?string
    {
        $root = $this->overrideThemeRoot($theme);

        if (! $this->files->isDirectory($root)) {
            return null;
        }

        if (is_link($root)) {
            throw new RuntimeException('Symlink tidak diizinkan di dalam area view override.');
        }

        $backup = storage_path('app/starter/backups/views/'.now()->format('Ymd_His_u')."/{$theme}");
        $this->files->ensureDirectoryExists(dirname($backup));

        if (! $this->files->copyDirectory($root, $backup)) {
            throw new RuntimeException("Gagal membuat backup view override: {$backup}");
        }

        return $backup;
    }

    private function assertSafeTarget(string $target, string $theme): void
    {
        $root = $this->overrideThemeRoot($theme);

        if (is_link($root) || is_link($target)) {
            throw new RuntimeException('Symlink tidak diizinkan di dalam area view override.');
        }

        $relative = ltrim(substr(dirname($target), strlen($root)), DIRECTORY_SEPARATOR);
        $current = $root;

        foreach ($relative === '' ? [] : explode(DIRECTORY_SEPARATOR, $relative) as $segment) {
            $current .= DIRECTORY_SEPARATOR.$segment;

            if (is_link($current)) {
                throw new RuntimeException('Symlink tidak diizinkan di dalam area view override.');
            }
        }
    }
}
