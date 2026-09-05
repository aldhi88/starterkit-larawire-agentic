<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" class="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="starter-auth-login-url" content="{{ \Aldhi88\StarterKit\Support\Starter\StarterNavigation::authLoginUrl() }}">
    <title>{{ $title ?? 'Login' }} | {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/dashcode/images/logo/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashcode/css/app.css') }}?v={{ filemtime(public_path('assets/dashcode/css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/starter/css/starter.css') }}?v={{ filemtime(public_path('assets/starter/css/starter.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/dashcode/css/dashcode.css') }}?v={{ filemtime(public_path('assets/dashcode/css/dashcode.css')) }}">
    @includeIf('extensions.starter.layout.head')
    @stack('page-styles')
    @livewireStyles
</head>

<body class="font-inter skin-default dashcode-auth" data-starter-theme="dashcode">
    @include('starter.templates.components.toast')

    <div class="starter-livewire-loader" data-starter-livewire-loader aria-label="Memproses permintaan" aria-hidden="true" role="status">
        <div class="card starter-loader-card">
            <span class="starter-spinner" aria-hidden="true"></span>
            <span class="font-medium">Memproses...</span>
        </div>
    </div>

    <main class="loginwrapper">
        <div class="lg-inner-column">
            <section class="left-column relative z-[1]" aria-label="Identitas aplikasi" data-starter-region="identity-panel">
                <div class="starter-auth-intro max-w-[520px] pt-20 ltr:pl-20 rtl:pr-20">
                    <a href="{{ url('/') }}" data-starter-navigate>
                        <img src="{{ asset('assets/dashcode/images/logo/logo.svg') }}" alt="{{ config('app.name') }}" class="starter-auth-logo">
                    </a>
                    <h1 class="starter-auth-heading">
                        Akses aplikasi perusahaan<br>
                        <span class="font-bold">dengan aman.</span>
                    </h1>
                </div>
                <div class="starter-auth-illustration absolute bottom-[-130px] left-0 z-[-1] h-full w-full 2xl:bottom-[-160px]">
                    <img src="{{ asset('assets/dashcode/images/auth/ils1.svg') }}" class="h-full w-full object-contain" alt="" aria-hidden="true">
                </div>
            </section>

            <section class="right-column relative" data-starter-region="primary-content">
                <div class="inner-content flex h-full flex-col bg-white">
                    <div class="auth-box starter-auth-box flex h-full flex-col justify-center">
                        <div class="mobile-logo mb-6 text-center lg:hidden">
                            <a href="{{ url('/') }}" data-starter-navigate>
                                <img src="{{ asset('assets/dashcode/images/logo/logo.svg') }}" alt="{{ config('app.name') }}" class="starter-auth-logo mx-auto">
                            </a>
                        </div>
                        <div class="mb-6 text-center">
                            <h1 class="text-2xl font-semibold text-slate-800">{{ $title ?? 'Login' }}</h1>
                            <p class="mt-2 text-base text-slate-500">
                                {{ ($title ?? null) === 'Layar Dikunci'
                                    ? 'Aplikasi dikunci untuk melindungi sesi Anda.'
                                    : 'Masukkan username atau email dan password untuk melanjutkan.' }}
                            </p>
                        </div>

                        @if (session('starter-auth-message'))
                            <div class="dashcode-alert dashcode-alert-warning mb-4" role="alert">{{ session('starter-auth-message') }}</div>
                        @endif

                        {{ $slot }}

                        @if (($title ?? null) !== 'Layar Dikunci')
                            <div class="mt-6 text-center">
                                <a href="{{ route('landing') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-primary-500" data-starter-navigate>
                                    @include('starter.templates.layouts.icon', ['name' => 'arrow-left'])
                                    Kembali ke landing page
                                </a>
                            </div>
                        @endif
                    </div>
                    <footer class="auth-footer text-center text-sm text-slate-500" data-starter-region="page-footer">{{ now()->year }} © {{ config('app.name') }}</footer>
                </div>
            </section>
        </div>
    </main>

    <script src="{{ asset('assets/dashcode/js/dashcode.js') }}?v={{ filemtime(public_path('assets/dashcode/js/dashcode.js')) }}" data-navigate-once defer></script>
    <script src="{{ asset('assets/starter/js/starter-runtime.js') }}?v={{ filemtime(public_path('assets/starter/js/starter-runtime.js')) }}" data-navigate-once defer></script>
    @livewireScripts
    @stack('page-scripts')
    @includeIf('extensions.starter.layout.body-end')
</body>

</html>
