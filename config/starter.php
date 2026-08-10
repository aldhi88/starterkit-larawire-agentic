<?php

use Altekno\StarterKit\Support\Starter\StarterDomain;
use Altekno\StarterKit\Themes\Starter\TablerPowerGridTheme;

$configuredDomain = strtolower(rtrim((string) env('APP_DOMAIN'), '.'));
$domain = $configuredDomain !== ''
    ? $configuredDomain
    : StarterDomain::host((string) env('APP_URL', 'http://localhost'));

return [
    'domain' => $domain,

    'theme' => env('STARTER_THEME', 'tabler'),

    'layout' => env('STARTER_LAYOUT', 'vertical'),

    'themes' => [
        'tabler' => [
            'views' => 'resources/themes/tabler/views',
            'assets' => 'public/themes/tabler/assets/tabler',
            'docs' => 'docs/template/tabler',
            'powergrid' => TablerPowerGridTheme::class,
            'layouts' => [
                'vertical' => 'starter.templates.layouts.navigation.vertical',
                'horizontal' => 'starter.templates.layouts.navigation.horizontal',
            ],
        ],
    ],

    'api' => [
        'enabled' => env('STARTER_API_ENABLED', false),
        'domain' => 'api.'.trim((string) $domain, '.'),
    ],

    'connector' => [
        'configure_auth' => env('STARTER_CONFIGURE_AUTH', true),
        'configure_shared_session' => env('STARTER_CONFIGURE_SHARED_SESSION', true),
    ],

    'superuser' => [
        'username' => env('STARTER_SUPERUSER_USERNAME', 'superuser'),
        'email' => env('STARTER_SUPERUSER_EMAIL', 'developer@example.test'),
        'password' => env('STARTER_SUPERUSER_PASSWORD'),
    ],
];
