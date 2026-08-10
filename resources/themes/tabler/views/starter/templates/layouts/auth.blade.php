<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="starter-auth-login-url" content="{{ \Altekno\StarterKit\Support\Starter\StarterNavigation::authLoginUrl() }}">
    <title>{{ $title ?? 'Login' }} | {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/tabler/static/logo-small.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/tabler/dist/css/tabler.min.css') }}?v={{ filemtime(public_path('assets/tabler/dist/css/tabler.min.css')) }}">
    <style>
        .starter-auth-mark {
            align-items: center;
            background: var(--tblr-primary);
            border-radius: 1rem;
            color: var(--tblr-primary-fg);
            display: inline-flex;
            font-size: 1.5rem;
            font-weight: 700;
            height: 3.25rem;
            justify-content: center;
            width: 3.25rem;
        }

        .starter-livewire-loader {
            align-items: center;
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            background: color-mix(in srgb, var(--tblr-bg-surface) 68%, transparent);
            bottom: 0;
            display: flex;
            justify-content: center;
            left: 0;
            opacity: 0;
            pointer-events: none;
            position: fixed;
            right: 0;
            top: 0;
            transition: opacity .14s ease;
            z-index: 1045;
        }

        .starter-livewire-loader-card {
            min-width: 10rem;
        }

        .input-group:has(> .input-group-text, > .btn) > .form-control.is-invalid {
            background-image: none;
            padding-right: .75rem;
        }

        .input-group:has(> .form-control.is-invalid, > .form-select.is-invalid) > .form-control.is-invalid,
        .input-group:has(> .form-control.is-invalid, > .form-select.is-invalid) > .form-select.is-invalid,
        .input-group:has(> .form-control.is-invalid, > .form-select.is-invalid) > .input-group-text,
        .input-group:has(> .form-control.is-invalid, > .form-select.is-invalid) > .btn {
            border-color: var(--tblr-form-invalid-border-color) !important;
        }

        .input-group-flat:has(> .form-control.is-invalid, > .form-select.is-invalid):focus-within {
            box-shadow: 0 0 0 .25rem rgba(var(--tblr-danger-rgb), .25);
        }

        [data-starter-livewire-loading] {
            filter: blur(2px);
            opacity: .55;
            pointer-events: none;
            transition: filter .14s ease, opacity .14s ease;
            user-select: none;
        }

        body.starter-livewire-is-loading .starter-livewire-loader {
            opacity: 1;
            pointer-events: auto;
        }

        @media (prefers-reduced-motion: reduce) {
            .starter-livewire-loader,
            [data-starter-livewire-loading] {
                transition: none;
            }
        }
    </style>
    @includeIf('extensions.starter.layout.head')
    @stack('page-styles')
    @livewireStyles
</head>

<body class="d-flex flex-column bg-body-tertiary">
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

    <div class="page page-center">
        <div class="container py-4">
            <div class="row align-items-center gx-0 gx-lg-4 gy-4">
                <div class="col-lg d-none d-lg-block">
                    <svg class="img-fluid d-block mx-auto" width="520" height="390" viewBox="0 0 520 390" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-labelledby="auth-illustration-title">
                        <title id="auth-illustration-title">Ilustrasi login aman</title>
                        <path d="M78 313c-23.2-25.6-35.1-59.8-30.4-94.2 5.2-38.7 32.2-71.6 67.7-88.1 28.5-13.2 60.6-15.2 90.5-24.8 32.1-10.3 60.8-30.2 94.2-35.1 50.4-7.4 103.4 18.8 128.8 63 25.3 44.2 21.3 104.9-10.2 145-34.5 44-91.7 56.7-146.3 61.4-67.3 5.8-146.2 25.9-194.3-27.2Z" fill="var(--tblr-primary)" opacity=".08"/>
                        <path d="M148 112h174c18.8 0 34 15.2 34 34v126c0 18.8-15.2 34-34 34H148c-18.8 0-34-15.2-34-34V146c0-18.8 15.2-34 34-34Z" fill="var(--tblr-bg-surface)" stroke="var(--tblr-border-color)" stroke-width="2"/>
                        <path d="M115 158h240" stroke="var(--tblr-border-color)" stroke-width="2"/>
                        <circle cx="145" cy="135" r="7" fill="var(--tblr-danger)"/>
                        <circle cx="168" cy="135" r="7" fill="var(--tblr-warning)"/>
                        <circle cx="191" cy="135" r="7" fill="var(--tblr-success)"/>
                        <rect x="154" y="190" width="100" height="10" rx="5" fill="var(--tblr-secondary)" opacity=".35"/>
                        <rect x="154" y="217" width="150" height="10" rx="5" fill="var(--tblr-secondary)" opacity=".2"/>
                        <rect x="154" y="249" width="78" height="28" rx="6" fill="var(--tblr-primary)"/>
                        <path d="M306 187c0-28.2 22.8-51 51-51s51 22.8 51 51v18h-25v-18c0-14.4-11.6-26-26-26s-26 11.6-26 26v18h-25v-18Z" fill="var(--tblr-primary)" opacity=".16"/>
                        <rect x="282" y="198" width="150" height="118" rx="18" fill="var(--tblr-primary)"/>
                        <path d="M357 235c-12.2 0-22 9.8-22 22 0 8.3 4.6 15.5 11.4 19.2l-3.4 20.8h28l-3.4-20.8A21.9 21.9 0 0 0 379 257c0-12.2-9.8-22-22-22Z" fill="var(--tblr-primary-fg)"/>
                        <path d="M88 310h350" stroke="var(--tblr-border-color)" stroke-width="2" stroke-linecap="round"/>
                        <path d="M83 98h38M398 98h25M415 122h52M53 122h40" stroke="var(--tblr-primary)" stroke-width="4" stroke-linecap="round" opacity=".25"/>
                    </svg>
                </div>

                <div class="col-lg">
                    <div class="container-tight">
                        <div class="text-center mb-4">
                            <a href="{{ url('/') }}" class="text-decoration-none" wire:navigate>
                                <span class="starter-auth-mark">{{ str(config('app.name'))->substr(0, 1)->upper() }}</span>
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
                                        : 'Masukkan username atau email dan password untuk melanjutkan.' }}
                                </p>
                                @if (session('starter-auth-message'))
                                    <div class="alert alert-warning" role="alert">
                                        {{ session('starter-auth-message') }}
                                    </div>
                                @endif
                                {{ $slot }}
                            </div>
                        </div>

                        <div class="text-center text-secondary mt-3">
                            {{ now()->year }} © {{ config('app.name') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/tabler/dist/js/tabler.min.js') }}?v={{ filemtime(public_path('assets/tabler/dist/js/tabler.min.js')) }}" defer></script>
    <script src="{{ asset('assets/starter/js/starter-runtime.js') }}?v={{ filemtime(public_path('assets/starter/js/starter-runtime.js')) }}" data-navigate-once defer></script>
    @livewireScripts
    @stack('page-scripts')
    @includeIf('extensions.starter.layout.body-end')
</body>

</html>
