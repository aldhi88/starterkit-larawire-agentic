<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" class="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="starter-auth-login-url" content="{{ \Aldhi88\StarterKit\Support\Starter\StarterNavigation::authLoginUrl() }}">
    <meta name="starter-lock-screen-enabled" content="{{ $lockScreenEnabled ? '1' : '0' }}">
    <meta name="starter-lock-screen-timeout" content="{{ $lockScreenTimeoutSeconds }}">
    <meta name="starter-lock-screen-url" content="{{ $lockScreenUrl }}">
    <meta name="starter-session-activity-url" content="{{ $sessionActivityUrl }}">
    <title>{{ $title ?? ($currentAppName ?? config('app.name')) }} | {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/dashcode/images/logo/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashcode/css/app.css') }}?v={{ filemtime(public_path('assets/dashcode/css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/starter/vendor/flatpickr/flatpickr.min.css') }}?v={{ filemtime(public_path('assets/starter/vendor/flatpickr/flatpickr.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('vendor/livewire-powergrid/tailwind.css') }}?v={{ filemtime(public_path('vendor/livewire-powergrid/tailwind.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/starter/css/starter.css') }}?v={{ filemtime(public_path('assets/starter/css/starter.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/dashcode/css/starter-theme.css') }}?v={{ filemtime(public_path('assets/dashcode/css/starter-theme.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/dashcode/css/custom.css') }}?v={{ filemtime(public_path('assets/dashcode/css/custom.css')) }}">
    @includeIf('extensions.starter.layout.head')
    @stack('page-styles')
    @livewireStyles
</head>

@php
    $starterTheme = \Aldhi88\StarterKit\Support\Starter\StarterTheme::key();
    $starterLayout = \Aldhi88\StarterKit\Support\Starter\StarterTheme::layout();
    $starterLayoutView = \Aldhi88\StarterKit\Support\Starter\StarterTheme::layoutView();
    $accountPersistBase = 'starter-account-'.($login?->getKey() ?? 'guest');
    $defaultBrandLogoUrl = asset('assets/dashcode/images/logo/logo-white.svg');
    $defaultBrandLogoDarkUrl = asset('assets/dashcode/images/logo/logo.svg');
    $brandLogoUrl = $clientLogoUrl ?: $defaultBrandLogoUrl;
    $brandLogoDarkUrl = $clientLogoUrl ?: $defaultBrandLogoDarkUrl;
    $brandLogoAlt = $clientLogoUrl ? ($clientName ?: config('app.name')) : config('app.name');
@endphp

<body class="font-inter dashcode-app" id="body_class" data-starter-app-shell data-starter-theme="{{ $starterTheme }}" data-starter-layout="{{ $starterLayout }}">
    @include('starter.templates.components.toast')

    <main class="app-wrapper {{ $starterLayout === 'horizontal' ? 'horizontalMenu' : '' }}">
        @include($starterLayoutView)

        <div class="dashcode-main-column flex min-h-screen min-w-0 flex-col justify-between">
            <div class="min-w-0">
                <div class="content-wrapper transition-all duration-150 ltr:ml-[248px] rtl:mr-[248px]" id="content_wrapper">
                    <div class="starter-content-container page-content px-[15px] pb-8 pt-6 md:px-6">
                        <div class="starter-slot-area relative min-w-0" wire:transition="starter-page">
                            {{ $slot }}

                            <div class="starter-livewire-loader" data-starter-livewire-loader aria-label="Memproses permintaan" aria-hidden="true" role="status">
                                <div class="card starter-loader-card">
                                    <span class="starter-spinner" aria-hidden="true"></span>
                                    <span class="font-medium">Memproses...</span>
                                </div>
                            </div>

                            @include('starter-shared::components.navigate-loader')
                        </div>
                    </div>
                </div>
            </div>

            <footer class="site-footer starter-shell-footer bg-white py-4 text-sm text-slate-500 ltr:ml-[248px] rtl:mr-[248px]">
                <div class="starter-content-container grid grid-cols-1 px-[15px] md:grid-cols-2 md:gap-5 md:px-6">
                    <span class="text-center text-sm ltr:md:text-start rtl:md:text-right">{{ now()->year }} © {{ config('app.name') }}.</span>
                    <span class="text-center text-sm ltr:md:text-right rtl:md:text-end">{{ $currentAppName ?? 'Starter' }}</span>
                </div>
            </footer>
        </div>
    </main>

    <script src="{{ asset('assets/dashcode/js/starter-theme.js') }}?v={{ filemtime(public_path('assets/dashcode/js/starter-theme.js')) }}" data-navigate-once defer></script>
    <script src="{{ asset('assets/starter/js/starter-runtime.js') }}?v={{ filemtime(public_path('assets/starter/js/starter-runtime.js')) }}" data-navigate-once defer></script>
    <script src="{{ asset('assets/starter/vendor/flatpickr/flatpickr.min.js') }}?v={{ filemtime(public_path('assets/starter/vendor/flatpickr/flatpickr.min.js')) }}" data-navigate-once defer></script>
    <script src="{{ asset('vendor/livewire-powergrid/powergrid.js') }}?v={{ filemtime(public_path('vendor/livewire-powergrid/powergrid.js')) }}" data-navigate-once defer></script>
    @livewireScripts
    @stack('page-scripts')
    @includeIf('extensions.starter.layout.body-end')
</body>

</html>
