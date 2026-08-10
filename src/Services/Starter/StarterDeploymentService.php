<?php

namespace Altekno\StarterKit\Services\Starter;

use Illuminate\Console\Command;
use Throwable;

class StarterDeploymentService
{
    public function __construct(
        private readonly StarterAssetPublisher $assets,
    ) {}

    public function prepare(Command $command, bool $ensureApplicationKey = false): int
    {
        $hadBootCache = app()->configurationIsCached() || app()->routesAreCached();

        $command->info('Membersihkan cache config dan route lama...');
        if ($command->call('config:clear') !== Command::SUCCESS
            || $command->call('route:clear') !== Command::SUCCESS) {
            return Command::FAILURE;
        }

        if ($hadBootCache) {
            $command->info('Cache bootstrap lama telah dibersihkan. Deployment dilanjutkan dalam proses yang sama.');
        }

        if ($ensureApplicationKey && ! $this->ensureApplicationKey($command)) {
            return Command::FAILURE;
        }

        $command->info('Memvalidasi konfigurasi keamanan...');
        if ($command->call('starter:security-check') !== Command::SUCCESS) {
            return Command::FAILURE;
        }

        $command->info('Menyinkronkan asset dan migration...');
        if (! $this->assets->publish($command)
            || $command->call('migrate', ['--force' => true]) !== Command::SUCCESS) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    public function finish(Command $command): int
    {
        $command->info('Mempublikasikan asset runtime dan membangun cache production...');
        if (! $this->livewireAssetsAreCurrent()
            && $command->call('livewire:publish', [
                '--assets' => true,
                '--no-interaction' => true,
            ]) !== Command::SUCCESS) {
            return Command::FAILURE;
        }

        if ($this->livewireAssetsAreCurrent()) {
            $command->line('Asset Livewire sudah terbaru.');
        }

        $this->ensureStorageLink($command);

        if ($this->shouldBuildProductionCache()) {
            if ($command->call('optimize') !== Command::SUCCESS) {
                return Command::FAILURE;
            }

            if (! is_file(app()->getCachedConfigPath()) || ! is_file(app()->getCachedRoutesPath())) {
                $command->error('Cache config atau route production gagal dibangun.');

                return Command::FAILURE;
            }

            $command->info('Cache config, event, route, dan view production telah dibangun ulang.');
        }

        return Command::SUCCESS;
    }

    private function ensureApplicationKey(Command $command): bool
    {
        if (filled(config('app.key'))) {
            return true;
        }

        if ($command->call('key:generate', ['--force' => true]) !== Command::SUCCESS) {
            return false;
        }

        $generatedKey = $this->environmentValue('APP_KEY');

        if ($generatedKey === '') {
            $command->error('APP_KEY tidak dapat dibaca setelah key:generate.');

            return false;
        }

        config(['app.key' => $generatedKey]);

        return true;
    }

    private function livewireAssetsAreCurrent(): bool
    {
        $sourceManifest = base_path('vendor/livewire/livewire/dist/manifest.json');
        $publishedManifest = public_path('vendor/livewire/manifest.json');

        return is_file($sourceManifest)
            && is_file($publishedManifest)
            && hash_file('sha256', $sourceManifest) === hash_file('sha256', $publishedManifest);
    }

    private function shouldBuildProductionCache(): bool
    {
        $environment = strtolower($this->environmentValue('APP_ENV'));

        return app()->isProduction() || $environment === 'production';
    }

    private function ensureStorageLink(Command $command): void
    {
        $link = public_path('storage');

        if (is_link($link) || file_exists($link)) {
            $command->info('Public storage path already exists.');

            return;
        }

        if (! function_exists('symlink') && ! function_exists('exec')) {
            $this->warnManualStorageLink($command);

            return;
        }

        try {
            if ($command->call('storage:link') === Command::SUCCESS) {
                return;
            }
        } catch (Throwable) {
            // Shared hosting may disable both PHP symlink and process functions.
        }

        $this->warnManualStorageLink($command);
    }

    private function warnManualStorageLink(Command $command): void
    {
        $command->warn(
            'PHP hosting tidak mengizinkan pembuatan symlink. Jalankan satu kali dari root Laravel: '
            .'ln -s ../storage/app/public public/storage',
        );
    }

    private function environmentValue(string $key): string
    {
        $contents = @file_get_contents(base_path('.env'));

        if ($contents === false
            || preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $contents, $matches) !== 1) {
            return '';
        }

        return trim(trim($matches[1]), "\"'");
    }
}
