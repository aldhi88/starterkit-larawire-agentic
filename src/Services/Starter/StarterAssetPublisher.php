<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Aldhi88\StarterKit\Support\Starter\StarterThemeRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class StarterAssetPublisher
{
    public const TEMPLATE_SOURCE_URL = 'https://drive.google.com/drive/folders/1ZtJiaL7bgxwiKZCEUb8yttCwUjXXJ-X0?usp=sharing';

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
        return $this->sourceMatchesManifest(
            base_path('theme-intake/'.$theme),
            $this->themeAssetFiles($theme),
        );
    }

    public function explainMissingThemeSource(Command $command, string $theme): void
    {
        if (app()->isProduction()) {
            $command->line('<fg=red;options=bold>ASET RUNTIME THEME PRODUCTION TIDAK TERSEDIA ATAU TIDAK VALID.</>');
            $command->line("Theme aktif: {$theme}");
            $command->line('Jangan download atau menyalin source template ke server production.');
            $command->line('Di local: pastikan theme-intake tersedia, jalankan starter:sync, lalu commit dan push public/assets/'.$theme.'/');
            $command->line('Di production: pull commit tersebut dan jalankan kembali starter:deploy.');

            return;
        }

        $command->line('<fg=red;options=bold>FILE SOURCE TEMPLATE WAJIB BELUM TERSEDIA.</>');
        $command->line("Theme: {$theme}");
        $command->line('Download source template dari:');
        $command->line(self::TEMPLATE_SOURCE_URL);
        $command->line("Salin foldernya ke: theme-intake/{$theme}/");
        $command->line('Folder theme-intake diabaikan Git dan tidak ikut package Composer.');
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

        $sourceRoot = base_path('theme-intake/'.$theme);

        if (! $this->sourceMatchesManifest($sourceRoot, $files)) {
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
        } catch (\Throwable $exception) {
            File::deleteDirectory($staging);
            $command->error($exception->getMessage());

            return false;
        }

        $command->info("Theme assets published: public/{$definition['assets']}");

        return true;
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
    private function sourceMatchesManifest(string $root, array $files): bool
    {
        if (! File::isDirectory($root)) {
            return false;
        }

        foreach ($files as $entry) {
            $path = $root.'/'.str_replace('/', DIRECTORY_SEPARATOR, $entry['source']);

            if (! File::isFile($path) || is_link($path)
                || ! hash_equals($entry['sha256'], (string) hash_file('sha256', $path))) {
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
