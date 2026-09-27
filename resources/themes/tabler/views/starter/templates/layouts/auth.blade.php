@php
    $defaultBrandLogoUrl = asset('assets/tabler/static/logo-small.svg');
    $brandLogoUrl = $clientLogoUrl ?: $defaultBrandLogoUrl;
    $brandLogoAlt = $clientLogoUrl ? ($clientName ?: config('app.name')) : config('app.name');
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="starter-auth-login-url" content="{{ \Aldhi88\StarterKit\Support\Starter\StarterNavigation::authLoginUrl() }}">
    <title>{{ $title ?? 'Login' }} | {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ $brandLogoUrl }}">
    <link rel="stylesheet" href="{{ asset('assets/tabler/dist/css/tabler.min.css') }}?v={{ filemtime(public_path('assets/tabler/dist/css/tabler.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/starter/css/starter.css') }}?v={{ filemtime(public_path('assets/starter/css/starter.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/tabler/css/tabler.css') }}?v={{ filemtime(public_path('assets/tabler/css/tabler.css')) }}">
    @includeIf('extensions.starter.layout.head')
    @stack('page-styles')
    @livewireStyles
</head>

<body class="bg-body-tertiary">
    <script src="{{ asset('assets/tabler/dist/js/tabler-theme.min.js') }}?v={{ filemtime(public_path('assets/tabler/dist/js/tabler-theme.min.js')) }}"></script>
    @include('starter.templates.components.toast')

    <div class="starter-livewire-loader" data-starter-livewire-loader aria-label="Memproses permintaan" aria-hidden="true" role="status">
        <div class="card card-sm shadow starter-livewire-loader-card">
            <div class="card-body d-flex align-items-center justify-content-center gap-3">
                <span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span>
                <span class="fw-semibold">Memproses...</span>
            </div>
        </div>
    </div>

    <main class="starter-tabler-auth-shell">
        <section class="starter-tabler-auth-backdrop starter-auth-backdrop-panel d-none d-lg-block" data-starter-region="identity-panel" aria-label="Identitas aplikasi">
            @include('starter-shared::components.auth-backdrop')
        </section>

        <section class="starter-tabler-auth-form" data-starter-region="primary-content">
            <div class="container-tight w-100">
                <div class="text-center mb-4">
                    <a href="{{ url('/') }}" class="text-decoration-none" wire:navigate>
                        @if ($clientLogoUrl)
                            <img
                                src="{{ $brandLogoUrl }}"
                                class="starter-auth-company-logo"
                                alt="{{ $brandLogoAlt }}"
                                data-starter-brand-logo
                                data-fallback-src="{{ $defaultBrandLogoUrl }}"
                                data-company-logo="true"
                            >
                        @else
                            <span class="starter-auth-mark">{{ str(config('app.name'))->substr(0, 1)->upper() }}</span>
                        @endif
                    </a>
                    @if (($title ?? null) !== 'Layar Dikunci')
                        <div class="mt-3">
                            <a href="{{ route('landing') }}" class="link-secondary d-inline-flex align-items-center gap-1" wire:navigate>
                                @include('starter.templates.layouts.icon', ['name' => 'arrow-left', 'class' => 'icon-sm'])
                                Kembali ke landing page
                            </a>
                        </div>
                    @endif
                </div>

                <div class="card card-md">
                    <div class="card-body">
                        <h2 class="h2 text-center mb-2">{{ $title ?? 'Login' }}</h2>
                        <p class="text-secondary text-center mb-4">
                            {{ ($title ?? null) === 'Layar Dikunci'
                                ? 'Aplikasi dikunci untuk melindungi sesi Anda.'
                                : (($otpRequired ?? false)
                                    ? 'Verifikasi kode yang dikirim ke email akun Anda.'
                                    : (($authenticatorRequired ?? false)
                                        ? 'Verifikasi kode dari aplikasi authenticator Anda.'
                                        : 'Selesaikan verifikasi login untuk melanjutkan.')) }}
                        </p>
                        @if (session('starter-auth-message'))
                            <div class="alert alert-warning" role="alert">
                                {{ session('starter-auth-message') }}
                            </div>
                        @endif
                        {{ $slot }}
                    </div>
                </div>

                <div class="text-center text-secondary mt-3" data-starter-region="page-footer">
                    {{ now()->year }} © {{ config('app.name') }}
                </div>
            </div>
        </section>
    </main>

    <script src="{{ asset('assets/tabler/dist/js/tabler.min.js') }}?v={{ filemtime(public_path('assets/tabler/dist/js/tabler.min.js')) }}" defer></script>
    <script src="{{ asset('assets/tabler/js/tabler.js') }}?v={{ filemtime(public_path('assets/tabler/js/tabler.js')) }}" data-navigate-once defer></script>
    <script src="{{ asset('assets/starter/js/starter-runtime.js') }}?v={{ filemtime(public_path('assets/starter/js/starter-runtime.js')) }}" data-navigate-once defer></script>
    @livewireScripts
    @stack('page-scripts')
    @includeIf('extensions.starter.layout.body-end')
</body>

</html>
