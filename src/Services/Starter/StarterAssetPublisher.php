<?php

namespace Altekno\StarterKit\Services\Starter;

use Altekno\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class StarterAssetPublisher
{
    public function publish(Command $command): bool
    {
        $destination = public_path('assets');
        File::ensureDirectoryExists($destination);
        $fingerprintDirectory = storage_path('framework/cache/starterkit');
        File::ensureDirectoryExists($fingerprintDirectory);

        $sources = [
            'starter' => StarterPaths::path('public/assets/starter'),
        ];

        foreach ((array) config('starter.themes', []) as $theme => $definition) {
            $path = is_array($definition) ? ($definition['assets'] ?? null) : null;

            if (is_string($path) && $path !== '' && ! str_contains($path, '..')) {
                $sources[(string) $theme] = StarterPaths::path($path);
            }
        }

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

        if (! $this->publishPowerGridAssets($command, $fingerprintDirectory)) {
            return false;
        }

        $command->info('Starter assets synchronized to public/assets.');

        return true;
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
