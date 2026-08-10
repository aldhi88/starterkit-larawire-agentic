<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title ?? config('app.name') }} | {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/tabler/static/logo-small.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/tabler/dist/css/tabler.min.css') }}?v={{ filemtime(public_path('assets/tabler/dist/css/tabler.min.css')) }}">
    @includeIf('extensions.starter.layout.head')
    @stack('page-styles')
    @livewireStyles
</head>

<body class="bg-white">
    <script src="{{ asset('assets/tabler/dist/js/tabler-theme.min.js') }}?v={{ filemtime(public_path('assets/tabler/dist/js/tabler-theme.min.js')) }}"></script>

    {{ $slot }}

    <script src="{{ asset('assets/tabler/dist/js/tabler.min.js') }}?v={{ filemtime(public_path('assets/tabler/dist/js/tabler.min.js')) }}" defer></script>
    @livewireScripts
    @stack('page-scripts')
    @includeIf('extensions.starter.layout.body-end')
</body>

</html>
