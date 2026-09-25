<?php

use Aldhi88\StarterKit\Support\Starter\StarterNavigation;
use Illuminate\Http\Request;

it('preserves a configured non-standard port in root authentication URLs', function (): void {
    config([
        'app.domain' => 'localhost',
        'app.url' => 'http://localhost:8123',
    ]);

    expect(StarterNavigation::rootAuthority())->toBe('localhost:8123')
        ->and(StarterNavigation::authUrl('login'))->toBe('http://localhost:8123/auth/login');
});

it('does not add a port when APP_URL does not define one', function (): void {
    config([
        'app.domain' => 'company.test',
        'app.url' => 'https://company.test',
    ]);

    expect(StarterNavigation::rootAuthority())->toBe('company.test')
        ->and(StarterNavigation::authUrl())->toBe('https://company.test/auth');
});

it('rejects framework and session endpoints as intended redirects', function (string $url): void {
    config([
        'app.domain' => 'company.test',
        'app.url' => 'https://company.test',
    ]);

    expect(StarterNavigation::isSafeRedirect($url))->toBeFalse();
})->with([
    'livewire versioned update' => 'https://hr.company.test/livewire-4.4.0/update',
    'livewire update' => 'https://company.test/livewire/update',
    'livewire upload' => 'https://company.test/livewire/upload-file',
    'livewire preview' => 'https://company.test/livewire/preview-file/hash',
    'livewire asset' => 'https://company.test/livewire/livewire.js',
    'session activity' => 'https://company.test/session/activity',
    'logout' => 'https://company.test/auth/logout',
    'login' => 'https://company.test/auth/login',
    'lock screen' => 'https://company.test/lock-screen',
    'health endpoint' => 'https://company.test/up',
    'api endpoint' => 'https://api.company.test/api/status',
    'broadcast authentication' => 'https://company.test/broadcasting/auth',
    'sanctum csrf endpoint' => 'https://company.test/sanctum/csrf-cookie',
]);

it('derives the safe originating page from livewire request metadata', function (): void {
    config([
        'app.domain' => 'company.test',
        'app.url' => 'https://company.test',
    ]);

    $request = Request::create('https://hr.company.test/livewire-4.4.0/update', 'POST');
    $request->headers->set('X-Starter-Page-Url', 'https://hr.company.test/reports?month=9');
    $request->headers->set('referer', 'https://hr.company.test/fallback');

    expect(StarterNavigation::safeRequestReturnUrl($request))
        ->toBe('https://hr.company.test/reports?month=9');
});

it('does not derive an internal livewire endpoint as the originating page', function (): void {
    config([
        'app.domain' => 'company.test',
        'app.url' => 'https://company.test',
    ]);

    $request = Request::create('https://hr.company.test/livewire-4.4.0/update', 'POST');
    $request->headers->set('X-Starter-Page-Url', 'https://hr.company.test/livewire-4.4.0/update');
    $request->headers->set('referer', 'https://hr.company.test/session/activity');

    expect(StarterNavigation::safeRequestReturnUrl($request))->toBeNull();
});
