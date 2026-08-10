<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="starter-auth-login-url" content="{{ \Altekno\StarterKit\Support\Starter\StarterNavigation::authLoginUrl() }}">
    <meta name="starter-lock-screen-enabled" content="{{ $lockScreenEnabled ? '1' : '0' }}">
    <meta name="starter-lock-screen-timeout" content="{{ $lockScreenTimeoutSeconds }}">
    <meta name="starter-lock-screen-url" content="{{ $lockScreenUrl }}">
    <meta name="starter-session-activity-url" content="{{ $sessionActivityUrl }}">
    <title>{{ $title ?? ($currentAppName ?? config('app.name')) }} | {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/tabler/static/logo-small.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/tabler/dist/css/tabler.min.css') }}?v={{ filemtime(public_path('assets/tabler/dist/css/tabler.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/tabler/dist/css/tabler-vendors.min.css') }}?v={{ filemtime(public_path('assets/tabler/dist/css/tabler-vendors.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/starter/vendor/flatpickr/flatpickr.min.css') }}?v={{ filemtime(public_path('assets/starter/vendor/flatpickr/flatpickr.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('vendor/livewire-powergrid/bootstrap5.css') }}?v={{ filemtime(public_path('vendor/livewire-powergrid/bootstrap5.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/starter/css/starter.css') }}?v={{ file_exists(public_path('assets/starter/css/starter.css')) ? filemtime(public_path('assets/starter/css/starter.css')) : time() }}">
    <link rel="stylesheet" href="{{ asset('assets/tabler/css/starter-theme.css') }}?v={{ file_exists(public_path('assets/tabler/css/starter-theme.css')) ? filemtime(public_path('assets/tabler/css/starter-theme.css')) : time() }}">
    @includeIf('extensions.starter.layout.head')
    @stack('page-styles')
    @livewireStyles
</head>

@php
    $starterTheme = \Altekno\StarterKit\Support\Starter\StarterTheme::key();
    $starterLayout = \Altekno\StarterKit\Support\Starter\StarterTheme::layout();
    $starterLayoutView = \Altekno\StarterKit\Support\Starter\StarterTheme::layoutView();
@endphp

<body data-starter-app-shell data-starter-theme="{{ $starterTheme }}" data-starter-layout="{{ $starterLayout }}">
    <script src="{{ asset('assets/tabler/dist/js/tabler-theme.min.js') }}?v={{ filemtime(public_path('assets/tabler/dist/js/tabler-theme.min.js')) }}"></script>

    @php
        $accountPersistBase = 'starter-account-'.($login?->getKey() ?? 'guest');
        $defaultBrandLogoUrl = asset('assets/tabler/static/logo-white.svg');
        $brandLogoUrl = $clientLogoUrl ?: $defaultBrandLogoUrl;
        $brandLogoAlt = $clientLogoUrl ? ($clientName ?: config('app.name')) : config('app.name');
    @endphp

    @include('starter.templates.components.toast')

    <div class="page starter-layout-{{ $starterLayout }}">
        @include($starterLayoutView)

        <div class="page-wrapper">
            <div class="page-body starter-page-body" wire:transition="starter-page">
                <div class="container-fluid starter-content-container">
                    <div class="starter-slot-area position-relative">
                        {{ $slot }}

                        <div class="starter-livewire-loader d-flex align-items-center justify-content-center rounded" data-starter-livewire-loader aria-label="Memproses permintaan" aria-hidden="true" role="status">
                            <div class="card card-sm shadow starter-livewire-loader-card">
                                <div class="card-body d-flex align-items-center justify-content-center gap-3">
                                    <span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span>
                                    <span class="fw-semibold">Memproses...</span>
                                </div>
                            </div>
                        </div>

                        @include('starter-shared::components.navigate-loader')
                    </div>
                </div>
            </div>

            <footer class="footer footer-transparent d-print-none">
                <div class="container-fluid starter-content-container">
                    <div class="row text-center align-items-center flex-row-reverse">
                        <div class="col-lg-auto ms-lg-auto">
                            <span class="text-secondary">{{ $currentAppName ?? 'Starter' }}</span>
                        </div>
                        <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                            {{ now()->year }} © {{ config('app.name') }}.
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="{{ asset('assets/tabler/dist/js/tabler.min.js') }}?v={{ filemtime(public_path('assets/tabler/dist/js/tabler.min.js')) }}" defer></script>
    <script src="{{ asset('assets/tabler/js/starter-theme.js') }}?v={{ filemtime(public_path('assets/tabler/js/starter-theme.js')) }}" data-navigate-once defer></script>
    <script src="{{ asset('assets/starter/js/starter-runtime.js') }}?v={{ filemtime(public_path('assets/starter/js/starter-runtime.js')) }}" data-navigate-once defer></script>
    <script src="{{ asset('assets/starter/vendor/flatpickr/flatpickr.min.js') }}?v={{ filemtime(public_path('assets/starter/vendor/flatpickr/flatpickr.min.js')) }}" data-navigate-once defer></script>
    <script src="{{ asset('vendor/livewire-powergrid/powergrid.js') }}?v={{ filemtime(public_path('vendor/livewire-powergrid/powergrid.js')) }}" data-navigate-once defer></script>
    @livewireScripts
    @stack('page-scripts')
    @includeIf('extensions.starter.layout.body-end')
</body>

</html>
