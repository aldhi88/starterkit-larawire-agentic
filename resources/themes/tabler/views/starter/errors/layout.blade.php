<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('code') · @yield('title') | {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/tabler/static/logo-small.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/tabler/dist/css/tabler.min.css') }}?v={{ filemtime(public_path('assets/tabler/dist/css/tabler.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/tabler/css/starter-theme.css') }}?v={{ filemtime(public_path('assets/tabler/css/starter-theme.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/tabler/css/custom.css') }}?v={{ filemtime(public_path('assets/tabler/css/custom.css')) }}">
</head>

<body class="border-top-wide border-primary bg-body-tertiary">
    <script src="{{ asset('assets/tabler/dist/js/tabler-theme.min.js') }}?v={{ filemtime(public_path('assets/tabler/dist/js/tabler-theme.min.js')) }}"></script>

    <main class="page page-center" aria-labelledby="error-title">
        <div class="container-tight py-4">
            <div class="empty">
                <div class="empty-img" data-starter-region="status-visual">
                    <span class="avatar avatar-xl bg-primary-lt text-primary rounded-circle">
                        @include('starter.templates.layouts.icon', ['name' => 'alert-triangle', 'size' => 42])
                    </span>
                </div>
                <div data-starter-region="primary-content">
                    <h1 class="empty-title" id="error-title">@yield('title')</h1>
                    <p class="empty-subtitle text-secondary">
                        @yield('message')
                    </p>
                </div>
                <div class="empty-action" data-starter-region="primary-action">
                    <a href="{{ rtrim((string) config('app.url'), '/') ?: '/' }}" class="btn btn-primary">
                        @include('starter.templates.layouts.icon', ['name' => 'home', 'class' => 'icon'])
                        Kembali ke Beranda
                    </a>
                </div>
                <div class="text-secondary small mt-4" data-starter-region="status-metadata">
                    Kode error: <span class="font-monospace">@yield('code')</span>
                </div>
            </div>
        </div>
    </main>

    <script src="{{ asset('assets/tabler/dist/js/tabler.min.js') }}?v={{ filemtime(public_path('assets/tabler/dist/js/tabler.min.js')) }}" defer></script>
</body>

</html>
