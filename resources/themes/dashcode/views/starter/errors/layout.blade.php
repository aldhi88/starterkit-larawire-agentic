<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('code') · @yield('title') | {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/dashcode/images/logo/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashcode/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashcode/css/starter-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashcode/css/custom.css') }}">
</head>
<body class="font-inter bg-slate-100" data-starter-theme="dashcode">
    @php($errorCode = trim($__env->yieldContent('code')))
    <main class="starter-error-page" aria-labelledby="error-title">
        <div class="starter-error-content">
            <div data-starter-region="status-visual">
                @if ($errorCode === '404')
                    <img src="{{ asset('assets/dashcode/images/all-img/404.svg') }}" class="starter-error-image" alt="" aria-hidden="true">
                @else
                    <div class="starter-error-code">{{ $errorCode }}</div>
                @endif
            </div>
            <div data-starter-region="primary-content">
                <h1 id="error-title">@yield('title')</h1>
                <p>@yield('message')</p>
            </div>
            <a href="{{ rtrim((string) config('app.url'), '/') ?: '/' }}" class="btn btn-dark" data-starter-region="primary-action">
                @include('starter.templates.layouts.icon', ['name' => 'home'])
                Kembali ke Beranda
            </a>
            <div class="text-secondary small mt-4" data-starter-region="status-metadata">
                Kode error: <span class="font-monospace">{{ $errorCode }}</span>
            </div>
        </div>
    </main>
</body>
</html>
