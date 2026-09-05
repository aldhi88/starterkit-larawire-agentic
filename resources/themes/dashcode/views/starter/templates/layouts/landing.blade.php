<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ?? config('app.name') }} | {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/dashcode/images/logo/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashcode/css/app.css') }}?v={{ filemtime(public_path('assets/dashcode/css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/dashcode/css/dashcode.css') }}?v={{ filemtime(public_path('assets/dashcode/css/dashcode.css')) }}">
    @includeIf('extensions.starter.layout.head')
    @stack('page-styles')
    @livewireStyles
</head>
<body class="font-inter bg-white dashcode-landing" data-starter-theme="dashcode">
    {{ $slot }}
    <script src="{{ asset('assets/dashcode/js/dashcode.js') }}?v={{ filemtime(public_path('assets/dashcode/js/dashcode.js')) }}" data-navigate-once defer></script>
    @livewireScripts
    @stack('page-scripts')
    @includeIf('extensions.starter.layout.body-end')
</body>
</html>
