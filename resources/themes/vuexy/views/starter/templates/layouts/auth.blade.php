<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" data-skin="default" data-bs-theme="light" data-template="vertical-menu-template-no-customizer">
<head>@include('starter.templates.layouts.head', ['vuexyPage' => 'auth'])</head>
<body>
    <div class="authentication-wrapper authentication-cover">
        <div class="app-brand auth-cover-brand">@include('starter.templates.layouts.brand', ['url' => route('landing'), 'navigate' => false, 'clientLogoUrl' => null, 'brandLogoUrl' => asset('assets/vuexy/img/branding/vuexy-mark.svg'), 'brandText' => config('app.name')])</div>
        <div class="authentication-inner row m-0">
            <div class="d-none d-xl-flex col-xl-8 p-0" data-starter-region="identity-panel"><div class="auth-cover-bg d-flex justify-content-center align-items-center">
                <img src="{{ asset('assets/vuexy/img/illustrations/auth-login-illustration-light.png') }}" alt="" class="my-5 auth-illustration">
                <img src="{{ asset('assets/vuexy/img/illustrations/bg-shape-image-light.png') }}" alt="" class="platform-bg">
            </div></div>
            <div class="d-flex col-12 col-xl-4 align-items-center authentication-bg p-sm-12 p-6" data-starter-region="primary-content"><div class="w-px-400 mx-auto mt-12 pt-5">
                <h4 class="mb-1">{{ $title ?? 'Selamat datang!' }} 👋</h4>
                <p class="mb-6">{{ $subtitle ?? 'Silakan masuk untuk melanjutkan ke workspace Anda.' }}</p>
                @if (session('starter-auth-message'))<div class="alert alert-warning" role="alert">{{ session('starter-auth-message') }}</div>@endif
                {{ $slot }}
                <footer class="text-center mt-6" data-starter-region="page-footer">{{ now()->year }} © {{ config('app.name') }}</footer>
            </div></div>
        </div>
    </div>
    @include('starter.templates.components.toast')
    @include('starter.templates.layouts.scripts')
</body>
</html>
