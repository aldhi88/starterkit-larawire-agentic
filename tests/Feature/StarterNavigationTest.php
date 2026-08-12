<?php

use Aldhi88\StarterKit\Support\Starter\StarterNavigation;

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
