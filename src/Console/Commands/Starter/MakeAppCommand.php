<?php

namespace Altekno\StarterKit\Console\Commands\Starter;

use Altekno\StarterKit\Services\Starter\StarterAppScaffolder;
use Altekno\StarterKit\Support\Starter\StarterRouteRegistrar;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class MakeAppCommand extends Command
{
    protected $signature = 'starter:make-app
        {subdomain : Lowercase subdomain for the new app}
        {--name= : Human-readable app name}
        {--description= : App description}
        {--icon=apps : Menu icon name}
        {--no-sync : Generate files without syncing metadata}';

    protected $description = 'Generate and optionally sync a complete starter subdomain application';

    public function handle(StarterAppScaffolder $scaffolder): int
    {
        $subdomain = (string) $this->argument('subdomain');

        if (preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $subdomain) !== 1) {
            $this->error('Subdomain must contain lowercase letters, numbers, or internal hyphens only.');

            return self::FAILURE;
        }

        if ($subdomain === 'api') {
            $this->error('Subdomain [api] is reserved for the shared API gateway.');

            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?: Str::headline($subdomain)));

        try {
            $created = $scaffolder->create(
                $subdomain,
                $name,
                $this->option('description') ?: null,
                (string) $this->option('icon'),
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($created as $path) {
            $this->line('Created '.str_replace(base_path().'/', '', $path));
        }

        if (! $this->option('no-sync')) {
            StarterRouteRegistrar::register($subdomain);

            if ($this->call('starter:sync', ['subdomain' => $subdomain, '--force' => true]) !== self::SUCCESS) {
                $this->warn('Files were generated, but metadata sync failed. Fix the reported issue and run starter:sync.');

                return self::FAILURE;
            }
        }

        $this->info("Subdomain application [{$subdomain}] is ready.");

        return self::SUCCESS;
    }
}
