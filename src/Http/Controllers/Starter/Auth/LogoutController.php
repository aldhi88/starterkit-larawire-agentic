<?php

namespace Altekno\StarterKit\Http\Controllers\Starter\Auth;

use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Services\Starter\AuditLogService;
use Altekno\StarterKit\Support\Starter\StarterNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request, AuditLogService $auditLogs): RedirectResponse
    {
        $login = $request->user();

        if ($login instanceof ClientLogin) {
            $auditLogs->recordSecurityEvent(
                'auth.logout',
                'Logout',
                target: $login,
                actor: $login,
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(StarterNavigation::authLoginUrl());
    }
}
