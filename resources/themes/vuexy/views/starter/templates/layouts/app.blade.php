@php
    $starterLayout = \Aldhi88\StarterKit\Support\Starter\StarterTheme::layout();
@endphp
<!doctype html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="ltr"
    data-skin="default"
    data-bs-theme="light"
    data-semidark-menu="true"
    data-template="{{ $starterLayout === 'vertical' ? 'vertical-menu-template-no-customizer' : 'horizontal-menu-template-no-customizer' }}"
    class="layout-navbar-fixed layout-menu-fixed layout-compact"
>
<head>
    @include('starter.templates.layouts.head')
    <meta name="starter-lock-screen-enabled" content="{{ $lockScreenEnabled ? '1' : '0' }}">
    <meta name="starter-lock-screen-timeout" content="{{ $lockScreenTimeoutSeconds }}">
    <meta name="starter-lock-screen-url" content="{{ $lockScreenUrl }}">
    <meta name="starter-session-activity-url" content="{{ $sessionActivityUrl }}">
    <link rel="stylesheet" href="{{ asset('assets/starter/vendor/flatpickr/flatpickr.min.css') }}">
</head>
<body data-starter-app-shell data-starter-theme="vuexy" data-starter-layout="{{ $starterLayout }}">
    @php
        $defaultBrandLogoUrl = asset('assets/vuexy/img/branding/vuexy-mark.svg');
        $brandLogoUrl = $clientLogoUrl ?: $defaultBrandLogoUrl;
        $brandLogoAlt = $clientLogoUrl ? ($clientName ?: config('app.name')) : config('app.name');
    @endphp
    @include('starter.templates.components.toast')
    <div class="layout-wrapper {{ $starterLayout === 'vertical' ? 'layout-content-navbar' : 'layout-navbar-full layout-horizontal layout-without-menu' }}">
        <div class="layout-container">
            @if ($starterLayout === 'vertical')
                @include('starter.templates.layouts.navigation.vertical')
            @else
                @include('starter.templates.layouts.navbar')
            @endif

            <div class="layout-page">
                @if ($starterLayout === 'vertical')
                    @include('starter.templates.layouts.navbar')
                @endif

                <div class="content-wrapper">
                    @if ($starterLayout === 'horizontal')
                        @include('starter.templates.layouts.navigation.horizontal')
                    @endif

                    <main class="container-xxl flex-grow-1 container-p-y" wire:transition="starter-page">
                        <div class="starter-slot-area position-relative">
                            {{ $slot }}
                            @include('starter-shared::components.navigate-loader')
                            <div class="starter-livewire-loader align-items-center justify-content-center" data-starter-livewire-loader aria-hidden="true" role="status">
                                <div class="card shadow-sm"><div class="card-body d-flex align-items-center gap-3 py-3 px-4"><span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span><span class="fw-medium">Memproses...</span></div></div>
                            </div>
                        </div>
                    </main>
                    <footer class="content-footer footer bg-footer-theme"><div class="container-xxl"><div class="footer-container d-flex justify-content-between flex-wrap gap-2 py-4"><span>{{ now()->year }} © {{ config('app.name') }}</span><span>{{ $currentAppName }}</span></div></div></footer>
                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle" data-vuexy-menu-toggle></div>
        <div class="drag-target"></div>
    </div>
    <script src="{{ asset('assets/starter/vendor/flatpickr/flatpickr.min.js') }}" data-navigate-once defer></script>
    <script src="{{ asset('vendor/livewire-powergrid/powergrid.js') }}" data-navigate-once defer></script>
    @include('starter.templates.layouts.scripts')
</body>
</html>
