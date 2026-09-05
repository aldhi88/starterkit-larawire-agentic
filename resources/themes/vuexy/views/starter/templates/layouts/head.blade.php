<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="starter-auth-login-url" content="{{ \Aldhi88\StarterKit\Support\Starter\StarterNavigation::authLoginUrl() }}">
<title>{{ $title ?? config('app.name') }} | {{ config('app.name') }}</title>
<link rel="icon" href="{{ asset('assets/vuexy/img/favicon/favicon.ico') }}">
<link rel="stylesheet" href="{{ asset('assets/vuexy/vendor/css/core.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vuexy/vendor/fonts/iconify-icons.css') }}">
@if (($vuexyPage ?? 'app') === 'auth')
    <link rel="stylesheet" href="{{ asset('assets/vuexy/vendor/css/pages/page-auth.css') }}">
@endif
<link rel="stylesheet" href="{{ asset('assets/starter/css/starter.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vuexy/css/vuexy.css') }}?v={{ file_exists(public_path('assets/vuexy/css/vuexy.css')) ? filemtime(public_path('assets/vuexy/css/vuexy.css')) : time() }}">
<script src="{{ asset('assets/vuexy/vendor/js/helpers.js') }}" data-navigate-once></script>
@includeIf('extensions.starter.layout.head')
@stack('page-styles')
@livewireStyles
