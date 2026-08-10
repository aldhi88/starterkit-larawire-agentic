<?php

namespace Altekno\StarterKit\Services\Starter;

use Altekno\StarterKit\Contracts\Starter\ClientLoginInterface;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

class AuthenticatedLoginService
{
    public function __construct(
        private readonly AuthFactory $auth,
        private readonly ClientLoginInterface $clientLogins,
    ) {}

    public function current(): ClientLogin
    {
        return $this->authenticated($this->auth->guard()->user());
    }

    public function authenticated(mixed $login): ClientLogin
    {
        abort_unless($login instanceof ClientLogin, 403);

        return $this->clientLogins->loadRole($login);
    }

    public function settingsManager(mixed $login = null): ClientLogin
    {
        $login = $this->authenticated($login ?? $this->auth->guard()->user());
        abort_unless($login->role?->canManageSettings(), 403);

        return $login;
    }

    public function logViewer(mixed $login = null): ClientLogin
    {
        $login = $this->authenticated($login ?? $this->auth->guard()->user());
        abort_unless($login->role?->canViewLogs(), 403);

        return $login;
    }
}
