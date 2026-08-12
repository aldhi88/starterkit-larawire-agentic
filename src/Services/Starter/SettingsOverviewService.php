<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Aldhi88\StarterKit\Contracts\Starter\AppInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientRoleInterface;
use Aldhi88\StarterKit\Models\Starter\Client;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;

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
        abort_unless($viewer->role->canManageSettings(), 403);

        return [
            'client' => $this->clients->current(),
            'roleCount' => $this->roles->countForViewer($viewer),
            'userCount' => $this->users->countForViewer($viewer),
            'appCount' => $this->apps->countRegistered(),
        ];
    }
}
