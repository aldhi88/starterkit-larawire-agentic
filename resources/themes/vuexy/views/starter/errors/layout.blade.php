<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if (\Aldhi88\StarterKit\Support\Starter\StarterBrandPalette::configured()) data-starter-brand @endif>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('code') · @yield('title') | {{ config('app.name') }}</title>
    @include('starter-shared::components.favicon')
    <link rel="stylesheet" href="{{ asset('assets/vuexy/vendor/css/core.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vuexy/vendor/fonts/iconify-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vuexy/css/vuexy.css') }}?v={{ file_exists(public_path('assets/vuexy/css/vuexy.css')) ? filemtime(public_path('assets/vuexy/css/vuexy.css')) : time() }}">
    @include('starter-shared::components.brand-stylesheet', ['standalone' => true])
</head>

<body class="starter-error-page">
    @php
        $errorCode = trim($__env->yieldContent('code'));
        $maintenance = $errorCode === '503';
        $unauthorized = in_array($errorCode, ['401', '403'], true);
        $illustrationUrl = $maintenance ? asset('assets/vuexy/img/illustrations/page-misc-under-maintenance.png')
            : ($unauthorized ? asset('assets/vuexy/img/illustrations/page-misc-you-are-not-authorized.png')
                : asset('assets/vuexy/img/illustrations/page-misc-error.png'));
    @endphp
    <main class="container-xxl container-p-y" aria-labelledby="error-title">
        <div class="misc-wrapper">
            <div data-starter-region="status-visual">
                <div class="starter-error-number mb-2">{{ $errorCode }}</div>
                @include('starter-shared::components.error-illustration', [
                    'illustrationUrl' => $illustrationUrl,
                    'illustrationWidth' => $maintenance ? 933 : ($unauthorized ? 257 : 339),
                    'illustrationHeight' => 751,
                    'illustrationClass' => 'starter-error-image',
                ])
            </div>
            <div data-starter-region="primary-content">
                <h1 class="h4 mb-2" id="error-title">@yield('title')</h1>
                <p class="mb-6 text-body-secondary">@yield('message')</p>
            </div>
            <div data-starter-region="primary-action">
                <a href="{{ rtrim((string) config('app.url'), '/') ?: '/' }}" class="btn btn-primary">Kembali ke Beranda</a>
            </div>
            <div class="text-body-secondary small mt-4" data-starter-region="status-metadata">Kode error: {{ $errorCode }}</div>
        </div>
    </main>
</body>
</html>
