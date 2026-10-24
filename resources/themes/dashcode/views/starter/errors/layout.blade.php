<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" class="light" @if (\Aldhi88\StarterKit\Support\Starter\StarterBrandPalette::configured()) data-starter-brand @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('code') · @yield('title') | {{ config('app.name') }}</title>
    @include('starter-shared::components.favicon')
    <link rel="stylesheet" href="{{ asset('assets/dashcode/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashcode/css/dashcode.css') }}">
    @include('starter-shared::components.brand-stylesheet', ['standalone' => true])
</head>
<body class="font-inter bg-white" data-starter-theme="dashcode">
    @php($errorCode = trim($__env->yieldContent('code')))
    <main class="starter-error-page" aria-labelledby="error-title">
        <div class="starter-error-content">
            <div data-starter-region="status-visual">
                @include('starter-shared::components.error-illustration', [
                    'illustrationUrl' => asset($errorCode === '404' ? 'assets/dashcode/images/all-img/404.svg' : 'assets/dashcode/images/svg/img-2.svg'),
                    'illustrationWidth' => $errorCode === '404' ? 515 : 500,
                    'illustrationHeight' => $errorCode === '404' ? 406 : 378,
                    'illustrationClass' => 'starter-error-image',
                ])
            </div>
            <div data-starter-region="primary-content">
                <h1 id="error-title">@yield('title')</h1>
                <p>@yield('message')</p>
            </div>
            <a href="{{ rtrim((string) config('app.url'), '/') ?: '/' }}" class="btn {{ \Aldhi88\StarterKit\Support\Starter\StarterBrandPalette::configured() ? 'btn-primary' : 'btn-dark' }}" data-starter-region="primary-action">
                @include('starter.templates.layouts.icon', ['name' => 'home'])
                Kembali ke Beranda
            </a>
            <div class="text-secondary small mt-4" data-starter-region="status-metadata">
                Kode error: <span class="font-monospace">{{ $errorCode }}</span>
            </div>
        </div>
    </main>
    <script src="{{ asset('assets/dashcode/js/dashcode.js') }}?v={{ (file_exists(public_path('assets/dashcode/js/dashcode.js')) ? filemtime(public_path('assets/dashcode/js/dashcode.js')) : 0) }}" defer></script>
</body>
</html>
