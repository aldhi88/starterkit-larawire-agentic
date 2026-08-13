<?php

use Aldhi88\StarterKit\Support\Starter\StarterDomain;
use Aldhi88\StarterKit\Themes\Starter\DashcodePowerGridTheme;
use Aldhi88\StarterKit\Themes\Starter\TablerPowerGridTheme;

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
            'label' => 'Tabler',
            'root' => dirname(__DIR__),
            'views' => 'resources/themes/tabler/views',
            'assets' => 'assets/tabler',
            'docs' => 'docs/template/tabler',
            'powergrid' => TablerPowerGridTheme::class,
            'layouts' => [
                'vertical' => 'starter.templates.layouts.navigation.vertical',
                'horizontal' => 'starter.templates.layouts.navigation.horizontal',
            ],
        ],
        'dashcode' => [
            'label' => 'DashCode',
            'root' => dirname(__DIR__),
            'views' => 'resources/themes/dashcode/views',
            'assets' => 'assets/dashcode',
            'docs' => 'docs/template/dashcode',
            'powergrid' => DashcodePowerGridTheme::class,
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

];
