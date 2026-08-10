<?php

namespace Altekno\StarterKit\Services\Starter;

use Altekno\StarterKit\Contracts\Starter\AppInterface;
use Altekno\StarterKit\Contracts\Starter\ClientInterface;
use Altekno\StarterKit\Contracts\Starter\ClientLoginInterface;
use Altekno\StarterKit\Contracts\Starter\ClientRoleInterface;
use Altekno\StarterKit\Models\Starter\Client;
use Altekno\StarterKit\Models\Starter\ClientLogin;

class SettingsOverviewService
{
    public function __construct(
        private readonly ClientInterface $clients,
        private readonly ClientRoleInterface $roles,
        private readonly ClientLoginInterface $users,
        private readonly AppInterface $apps,
    ) {}

    /**
     * @return array{client: Client, roleCount: int, userCount: int, appCount: int}
     */
    public function forViewer(ClientLogin $viewer): array
    {
        abort_unless($viewer->role?->canManageSettings() ?? false, 403);

        return [
            'client' => $this->clients->current(),
            'roleCount' => $this->roles->countForViewer($viewer),
            'userCount' => $this->users->countForViewer($viewer),
            'appCount' => $this->apps->countRegistered(),
        ];
    }
}
