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
</head>
<body class="font-inter bg-slate-100" data-starter-theme="dashcode">
    <main class="starter-error-page" aria-labelledby="error-title">
        <div class="starter-error-content">
            <img src="{{ asset('assets/dashcode/images/all-img/404.svg') }}" class="starter-error-image" alt="" aria-hidden="true">
            <div class="starter-error-code">@yield('code')</div>
            <h1 id="error-title">@yield('title')</h1>
            <p>@yield('message')</p>
            <a href="{{ rtrim((string) config('app.url'), '/') ?: '/' }}" class="btn btn-dark">
                @include('starter.templates.layouts.icon', ['name' => 'home'])
                Kembali ke Beranda
            </a>
        </div>
    </main>
</body>
</html>
