<?php

use Aldhi88\StarterKit\Support\Starter\StarterDomain;
use Aldhi88\StarterKit\Themes\Starter\DashcodePowerGridTheme;
use Aldhi88\StarterKit\Themes\Starter\TablerPowerGridTheme;
use Aldhi88\StarterKit\Themes\Starter\VuexyPowerGridTheme;

$configuredDomain = strtolower(rtrim((string) env('APP_DOMAIN'), '.'));
$domain = $configuredDomain !== ''
    ? $configuredDomain
    : StarterDomain::host((string) env('APP_URL', 'http://localhost'));

return [
    'domain' => $domain,

    'theme' => env('STARTER_THEME', 'tabler'),

    'layout' => env('STARTER_LAYOUT', 'vertical'),

    'auth' => [
        'login_human_challenge_enabled' => env('STARTER_LOGIN_HUMAN_CHALLENGE_ENABLED', true),
        'login_otp_enabled' => env('STARTER_LOGIN_OTP_ENABLED', false),
        'login_two_factor_enabled' => env('STARTER_LOGIN_TWO_FACTOR_ENABLED', true),
        'login_otp_mail_view' => 'starter-mail::login-otp',
        'login_otp_mail_text_view' => 'starter-mail::login-otp-text',
    ],

    'themes' => [
        'tabler' => [
            'label' => 'Tabler',
            'root' => dirname(__DIR__),
            'views' => 'resources/themes/tabler/views',
            'assets' => 'assets/tabler',
            'mail_logo' => 'assets/tabler/static/logo-small.svg',
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
            'mail_logo' => 'assets/dashcode/images/logo/logo.svg',
            'docs' => 'docs/template/dashcode',
            'powergrid' => DashcodePowerGridTheme::class,
            'layouts' => [
                'vertical' => 'starter.templates.layouts.navigation.vertical',
                'horizontal' => 'starter.templates.layouts.navigation.horizontal',
            ],
        ],
        'vuexy' => [
            'label' => 'Vuexy (licensed local runtime)',
            'root' => dirname(__DIR__),
            'views' => 'resources/themes/vuexy/views',
            'assets' => 'assets/vuexy',
            'mail_logo' => 'assets/vuexy/img/branding/vuexy-mark.svg',
            'docs' => 'docs/template/vuexy',
            'powergrid' => VuexyPowerGridTheme::class,
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
