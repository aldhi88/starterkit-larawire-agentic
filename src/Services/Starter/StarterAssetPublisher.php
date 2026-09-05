<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Aldhi88\StarterKit\Support\Starter\StarterThemeRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;
use ZipArchive;

class StarterAssetPublisher
{
    private const MAX_ARCHIVE_ENTRIES = 5000;

    private const MAX_EXTRACTED_BYTES = 104857600;

    public function publish(Command $command): bool
    {
        $destination = public_path('assets');
        File::ensureDirectoryExists($destination);
        $fingerprintDirectory = storage_path('framework/cache/starterkit');
        File::ensureDirectoryExists($fingerprintDirectory);

        $sources = ['starter' => StarterPaths::path('public/assets/starter')];

        foreach ($sources as $ownedDirectory => $ownedSource) {
            $ownedDestination = "{$destination}/{$ownedDirectory}";
            $fingerprintPath = "{$fingerprintDirectory}/assets-{$ownedDirectory}.sha256";

            if (! File::isDirectory($ownedSource)) {
                $command->error("Required starter asset directory not found: {$ownedSource}");

                return false;
            }

            $fingerprint = $this->directoryFingerprint($ownedSource);

            if ($this->publishedFingerprintMatches($ownedDestination, $fingerprintPath, $fingerprint)) {
                $command->line("Starter asset directory is already current: {$ownedDirectory}");

                continue;
            }

            File::deleteDirectory($ownedDestination);

            if (! File::copyDirectory($ownedSource, $ownedDestination)) {
                $command->error("Unable to publish starter asset directory: {$ownedDirectory}");

                return false;
            }

            File::put($fingerprintPath, $fingerprint.PHP_EOL);
        }

        if (! $this->publishSelectedTheme($command)) {
            return false;
        }

        if (! $this->publishPowerGridAssets($command, $fingerprintDirectory)) {
            return false;
        }

        $command->info('Starter assets synchronized to public/assets.');

        return true;
    }

    public function themeAssetsReady(string $theme): bool
    {
        $definition = StarterThemeRegistry::get($theme);
        $destination = public_path((string) $definition['assets']);

        return $this->manifestMatchesDirectory($destination, $this->themeAssetFiles($theme));
    }

    public function themeSourceReady(string $theme): bool
    {
        return $this->themeSourceRoot($theme, $this->themeAssetFiles($theme)) !== null;
    }

    public function prepareTheme(Command $command, string $theme): bool
    {
        if ($this->themeAssetsReady($theme) || $this->themeSourceReady($theme)) {
            return true;
        }

        if (app()->isProduction()) {
            $this->explainMissingThemeSource($command, $theme);

            return false;
        }

        return $this->downloadThemeSource($command, $theme);
    }

    public function explainMissingThemeSource(Command $command, string $theme): void
    {
        $source = $this->themeSourceMetadata($theme);
        $privateLocalTheme = ($source['provider'] ?? null) === 'local';

        if (app()->isProduction()) {
            $command->line('<fg=red;options=bold>ASET RUNTIME THEME PRODUCTION TIDAK TERSEDIA ATAU TIDAK VALID.</>');
            $command->line("Theme aktif: {$theme}");
            $command->line('Jangan download atau menyalin source template ke server production.');
            $command->line($privateLocalTheme
                ? 'Di local: siapkan runtime berlisensi owner pada directory theme-intake/'.$theme.'/runtime, jalankan starter:sync, lalu commit public/assets/'.$theme.'/.'
                : 'Di local: jalankan starter:sync agar arsip GitHub diunduh dan aset runtime dibuat ulang, lalu commit public/assets/'.$theme.'/');
            $command->line('Di production: pull commit tersebut dan jalankan kembali starter:deploy.');

            return;
        }

        $command->line('<fg=red;options=bold>ASET THEME GAGAL DISIAPKAN.</>');
        $command->line("Theme: {$theme}");
        $command->line($privateLocalTheme
            ? 'Theme ini bersifat lokal/privat. Sediakan runtime berlisensi owner pada directory theme-intake/'.$theme.'/runtime.'
            : 'Periksa koneksi internet dan pastikan URL arsip GitHub theme dapat diakses.');
        $command->line('Installer menolak arsip yang URL, ukuran, checksum, atau isinya tidak sesuai manifest package.');
    }

    public function publishSelectedTheme(Command $command): bool
    {
        $theme = StarterTheme::key();
        $definition = StarterThemeRegistry::get($theme);
        $destination = public_path((string) $definition['assets']);
        $files = $this->themeAssetFiles($theme);

        if ($this->manifestMatchesDirectory($destination, $files)) {
            $command->line("Starter theme assets are already current: {$theme}");

            return true;
        }

        $sourceRoot = $this->themeSourceRoot($theme, $files);

        if ($sourceRoot === null && ! app()->isProduction() && $this->downloadThemeSource($command, $theme)) {
            $sourceRoot = $this->themeSourceRoot($theme, $files);
        }

        if ($sourceRoot === null) {
            $this->explainMissingThemeSource($command, $theme);

            return false;
        }

        $staging = storage_path('framework/cache/starterkit/theme-'.$theme.'-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($staging);

        try {
            foreach ($files as $entry) {
                $source = $sourceRoot.'/'.str_replace('/', DIRECTORY_SEPARATOR, $entry['source']);
                $target = $staging.'/'.str_replace('/', DIRECTORY_SEPARATOR, $entry['target']);
                File::ensureDirectoryExists(dirname($target));

                if (! File::copy($source, $target)) {
                    throw new RuntimeException("Unable to stage theme asset [{$entry['target']}].");
                }
            }

            if (! $this->manifestMatchesDirectory($staging, $files)) {
                throw new RuntimeException("Theme [{$theme}] staging failed its exact manifest check.");
            }

            File::deleteDirectory($destination);
            File::ensureDirectoryExists(dirname($destination));

            if (! File::moveDirectory($staging, $destination)) {
                throw new RuntimeException("Unable to publish theme assets for [{$theme}].");
            }
        } catch (Throwable $exception) {
            File::deleteDirectory($staging);
            $command->error($exception->getMessage());

            return false;
        }

        $command->info("Theme assets published: public/{$definition['assets']}");

        return true;
    }

    private function downloadThemeSource(Command $command, string $theme): bool
    {
        try {
            $source = $this->themeSourceMetadata($theme);
            if (($source['provider'] ?? null) === 'local') {
                $command->error("Theme [{$theme}] is licensed for private use. Supply the licensed runtime in theme-intake/{$theme}/runtime/ before installation; public downloading is disabled.");

                return false;
            }
            $url = (string) $source['url'];
            $expectedHash = (string) $source['archive_sha256'];
            $maximumBytes = (int) $source['archive_max_bytes'];
            $workingRoot = storage_path('framework/cache/starterkit/theme-downloads');
            $archivePath = $workingRoot.'/'.$theme.'-'.bin2hex(random_bytes(6)).'.zip';
            $staging = $workingRoot.'/'.$theme.'-'.bin2hex(random_bytes(6));
            $cacheRoot = $this->downloadedThemeSourcePath($theme);

            File::ensureDirectoryExists($workingRoot);
            $command->info("Mengunduh aset theme {$theme} dari GitHub...");
            $response = Http::accept('application/octet-stream')
                ->withHeaders(['User-Agent' => 'Starterkit-Larawire-Agentic'])
                ->connectTimeout(15)
                ->timeout(120)
                ->retry(2, 250)
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException("GitHub merespons HTTP {$response->status()}.");
            }

            $archive = $response->body();
            $archiveBytes = strlen($archive);

            if ($archiveBytes === 0 || $archiveBytes > $maximumBytes) {
                throw new RuntimeException("Ukuran arsip theme tidak valid ({$archiveBytes} byte).");
            }

            if (! hash_equals($expectedHash, hash('sha256', $archive))) {
                throw new RuntimeException('Checksum arsip theme tidak cocok dengan manifest package.');
            }

            File::put($archivePath, $archive);
            File::ensureDirectoryExists($staging);
            $this->extractVerifiedArchive($archivePath, $staging);

            if (! $this->sourceMatchesManifest($staging, $this->themeAssetFiles($theme), exact: true)) {
                throw new RuntimeException('Isi arsip theme tidak cocok dengan asset manifest package.');
            }

            File::deleteDirectory($cacheRoot);
            File::ensureDirectoryExists(dirname($cacheRoot));

            if (! File::moveDirectory($staging, $cacheRoot)) {
                throw new RuntimeException('Aset theme terverifikasi tidak dapat disimpan ke cache local.');
            }

            File::delete($archivePath);
            $command->info("Aset theme {$theme} berhasil diverifikasi dan disiapkan.");

            return true;
        } catch (Throwable $exception) {
            if (isset($archivePath)) {
                File::delete($archivePath);
            }

            if (isset($staging)) {
                File::deleteDirectory($staging);
            }

            $command->error('Download theme gagal: '.$exception->getMessage());
            $this->explainMissingThemeSource($command, $theme);

            return false;
        }
    }

    private function extractVerifiedArchive(string $archivePath, string $destination): void
    {
        $zip = new ZipArchive;

        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('Arsip theme bukan file ZIP yang valid.');
        }

        try {
            if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_ARCHIVE_ENTRIES) {
                throw new RuntimeException('Jumlah file di dalam arsip theme tidak valid.');
            }

            $totalBytes = 0;

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);

                if (! is_array($entry)) {
                    throw new RuntimeException('Arsip theme memiliki metadata file yang tidak valid.');
                }

                $name = $entry['name'];
                $size = $entry['size'];

                if (! $this->safeArchivePath($name)) {
                    throw new RuntimeException('Arsip theme memiliki path atau metadata file yang tidak aman.');
                }

                $operatingSystem = 0;
                $attributes = 0;

                if ($zip->getExternalAttributesIndex($index, $operatingSystem, $attributes)
                    && (($attributes >> 16) & 0xF000) === 0xA000) {
                    throw new RuntimeException('Arsip theme tidak boleh berisi symbolic link.');
                }

                $totalBytes += $size;

                if ($totalBytes > self::MAX_EXTRACTED_BYTES) {
                    throw new RuntimeException('Ukuran hasil ekstraksi theme melewati batas aman.');
                }
            }

            if (! $zip->extractTo($destination)) {
                throw new RuntimeException('Arsip theme tidak dapat diekstrak.');
            }
        } finally {
            $zip->close();
        }
    }

    private function safeArchivePath(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);
        $trimmed = rtrim($normalized, '/');

        return $trimmed !== ''
            && ! str_contains($normalized, "\0")
            && ! str_starts_with($normalized, '/')
            && preg_match('/^[A-Za-z]:\//', $normalized) !== 1
            && preg_match('~(^|/)\.\.?(?:/|$)~', $trimmed) !== 1;
    }

    /** @return array<string, mixed> */
    private function themeSourceMetadata(string $theme): array
    {
        $path = StarterThemeRegistry::path($theme, 'docs', 'source.json');
        $source = json_decode((string) File::get($path), true);

        if (! is_array($source)) {
            throw new RuntimeException("Theme [{$theme}] tidak memiliki metadata source yang valid.");
        }

        return $source;
    }

    /** @param list<array{source: string, target: string, sha256: string}> $files */
    private function themeSourceRoot(string $theme, array $files): ?string
    {
        foreach ([base_path('theme-intake/'.$theme), $this->downloadedThemeSourcePath($theme)] as $root) {
            if ($this->sourceMatchesManifest($root, $files)) {
                return $root;
            }
        }

        return null;
    }

    private function downloadedThemeSourcePath(string $theme): string
    {
        return storage_path('framework/cache/starterkit/theme-sources/'.$theme);
    }

    /** @return list<array{source: string, target: string, sha256: string}> */
    private function themeAssetFiles(string $theme): array
    {
        $path = StarterThemeRegistry::path($theme, 'docs', 'asset-manifest.json');
        $decoded = json_decode((string) File::get($path), true);

        if (! is_array($decoded) || ! is_array($decoded['files'] ?? null)) {
            throw new RuntimeException("Theme [{$theme}] asset recipe is invalid.");
        }

        return array_map(static fn (array $entry): array => [
            'source' => (string) $entry['source'],
            'target' => (string) $entry['target'],
            'sha256' => (string) $entry['sha256'],
        ], $decoded['files']);
    }

    /** @param list<array{source: string, target: string, sha256: string}> $files */
    private function sourceMatchesManifest(string $root, array $files, bool $exact = false): bool
    {
        if (! File::isDirectory($root)) {
            return false;
        }

        $expected = [];

        foreach ($files as $entry) {
            $path = $root.'/'.str_replace('/', DIRECTORY_SEPARATOR, $entry['source']);

            if (! File::isFile($path) || is_link($path)
                || ! hash_equals($entry['sha256'], (string) hash_file('sha256', $path))) {
                return false;
            }

            $expected[] = $entry['source'];
        }

        if ($exact) {
            $actual = collect(File::allFiles($root))
                ->map(fn ($file): string => str_replace('\\', '/', $file->getRelativePathname()))
                ->sort()->values()->all();
            sort($expected);

            if ($expected !== $actual) {
                return false;
            }
        }

        return true;
    }

    /** @param list<array{source: string, target: string, sha256: string}> $files */
    private function manifestMatchesDirectory(string $root, array $files): bool
    {
        if (! File::isDirectory($root)) {
            return false;
        }

        $expected = [];

        foreach ($files as $entry) {
            $path = $root.'/'.str_replace('/', DIRECTORY_SEPARATOR, $entry['target']);

            if (! File::isFile($path) || is_link($path)
                || ! hash_equals($entry['sha256'], (string) hash_file('sha256', $path))) {
                return false;
            }

            $expected[] = $entry['target'];
        }

        $actual = collect(File::allFiles($root))
            ->map(fn ($file): string => str_replace('\\', '/', $file->getRelativePathname()))
            ->sort()->values()->all();
        sort($expected);

        return $expected === $actual;
    }

    private function publishPowerGridAssets(Command $command, string $fingerprintDirectory): bool
    {
        $source = base_path('vendor/power-components/livewire-powergrid/dist');
        $destination = public_path('vendor/livewire-powergrid');
        $fingerprintPath = "{$fingerprintDirectory}/assets-livewire-powergrid.sha256";

        if (! File::isDirectory($source)) {
            $command->error('Livewire PowerGrid assets are unavailable. Run Composer install first.');

            return false;
        }

        $fingerprint = $this->directoryFingerprint($source);

        if ($this->publishedFingerprintMatches($destination, $fingerprintPath, $fingerprint)) {
            $command->line('Starter asset directory is already current: livewire-powergrid');

            return true;
        }

        File::deleteDirectory($destination);

        if (! File::copyDirectory($source, $destination)) {
            $command->error('Unable to publish Livewire PowerGrid assets.');

            return false;
        }

        File::put($fingerprintPath, $fingerprint.PHP_EOL);

        return true;
    }

    private function publishedFingerprintMatches(
        string $destination,
        string $fingerprintPath,
        string $sourceFingerprint,
    ): bool {
        return File::isDirectory($destination)
            && File::isFile($fingerprintPath)
            && hash_equals($sourceFingerprint, trim((string) File::get($fingerprintPath)));
    }

    private function directoryFingerprint(string $source): string
    {
        $files = collect(File::allFiles($source))
            ->sortBy(fn ($file): string => $file->getRelativePathname(), SORT_STRING);
        $hash = hash_init('sha256');

        foreach ($files as $file) {
            hash_update($hash, implode("\0", [
                $file->getRelativePathname(),
                (string) $file->getSize(),
                hash_file('sha256', $file->getPathname()),
            ])."\n");
        }

        return hash_final($hash);
    }
}
