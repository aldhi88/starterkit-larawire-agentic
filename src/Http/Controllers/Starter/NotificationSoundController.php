<?php

namespace Aldhi88\StarterKit\Http\Controllers\Starter;

use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Support\Starter\StarterNotificationSound;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NotificationSoundController
{
    public function __invoke(AuthenticatedLoginService $logins, AuditLogService $auditLogs): BinaryFileResponse
    {
        $login = $logins->current();
        $path = $login->notification_sound_path;
        abort_unless(
            StarterNotificationSound::isOwnedPath($login->id, $path) && Storage::disk('local')->exists($path),
            404,
        );

        $extension = str_ends_with($path, '.wav') ? 'wav' : 'mp3';
        $auditLogs->recordSecurityEvent('profile.notification_sound.read', 'Membaca audio notifikasi pribadi', $login, $login);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $extension === 'wav' ? 'audio/wav' : 'audio/mpeg',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
