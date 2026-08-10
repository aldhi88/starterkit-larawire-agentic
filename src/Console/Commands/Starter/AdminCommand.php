<?php

namespace Altekno\StarterKit\Console\Commands\Starter;

use Illuminate\Console\Command;

class AdminCommand extends Command
{
    protected $signature = 'starter:admin';

    protected $description = 'Deprecated alias for starter:setup';

    public function handle(): int
    {
        $this->warn('starter:admin is deprecated; use starter:setup.');

        return $this->call('starter:setup');
    }
}
