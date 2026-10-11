@php
    $defaultBrandLogoUrl = asset('assets/vuexy/img/branding/vuexy-mark.svg');
    $brandLogoUrl = $clientLogoUrl ?: $defaultBrandLogoUrl;
    $brandLogoAlt = $clientLogoUrl ? ($clientName ?: config('app.name')) : config('app.name');
    $authSubtitle = $subtitle ?? match (true) {
        ($title ?? null) === 'Layar Dikunci' => 'Aplikasi dikunci untuk melindungi sesi Anda.',
        ($title ?? null) === 'Konfirmasi Password' => 'Verifikasi diperlukan sebelum membuka pengaturan sensitif.',
        ($otpRequired ?? false) => 'Verifikasi kode yang dikirim ke email akun Anda.',
        ($authenticatorRequired ?? false) => 'Verifikasi kode dari aplikasi authenticator Anda.',
        default => 'Selesaikan verifikasi login untuk melanjutkan.',
    };
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" data-skin="default" data-bs-theme="light" data-template="vertical-menu-template-no-customizer" @if (\Aldhi88\StarterKit\Support\Starter\StarterBrandPalette::configured()) data-starter-brand @endif>
<head>@include('starter.templates.layouts.head', ['vuexyPage' => 'auth'])</head>
<body data-starter-auth-shell>
    <div class="starter-livewire-loader align-items-center justify-content-center" data-starter-livewire-loader aria-label="Memproses permintaan" aria-hidden="true" role="status">
        <div class="card shadow-sm"><div class="card-body d-flex align-items-center gap-3 py-3 px-4"><span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span><span class="fw-medium">Memproses...</span></div></div>
    </div>
    <div class="authentication-wrapper authentication-cover">
        <div class="authentication-inner row m-0">
            <div class="d-none d-xl-flex col-xl-8 p-0 starter-auth-backdrop-panel" data-starter-region="identity-panel" aria-label="Identitas aplikasi">
                @include('starter-shared::components.auth-backdrop')
            </div>
            <div class="d-flex col-12 col-xl-4 align-items-center authentication-bg p-sm-12 p-6" data-starter-region="primary-content"><div class="w-px-400 mx-auto mt-12 pt-5">
                <div class="app-brand starter-auth-form-brand justify-content-center mb-6">@include('starter.templates.layouts.brand', ['url' => route('landing'), 'navigate' => false, 'clientLogoUrl' => $clientLogoUrl, 'brandLogoUrl' => $brandLogoUrl, 'brandLogoAlt' => $brandLogoAlt, 'brandText' => config('app.name'), 'markClass' => $clientLogoUrl ? 'starter-auth-company-logo' : '', 'showText' => ! $clientLogoUrl])</div>
                <h1 class="h4 mb-1">{{ $title ?? 'Login' }}@if (($title ?? 'Login') === 'Login') 👋@endif</h1>
                <p class="mb-6">{{ $authSubtitle }}</p>
                @if (session('starter-auth-message'))<div class="alert alert-warning" role="alert">{{ session('starter-auth-message') }}</div>@endif
                {{ $slot }}
                @if (($title ?? null) !== 'Layar Dikunci')
                    <div class="text-center mt-4">
                        <a href="{{ route('landing') }}" class="link-primary d-inline-flex align-items-center gap-1" data-starter-navigate>
                            @include('starter.templates.layouts.icon', ['name' => 'arrow-left', 'class' => 'icon-sm'])
                            Kembali ke landing page
                        </a>
                    </div>
                @endif
                <footer class="text-center mt-6" data-starter-region="page-footer">{{ now()->year }} © {{ config('app.name') }}</footer>
            </div></div>
        </div>
    </div>
    @include('starter.templates.components.toast')
    @include('starter.templates.layouts.scripts')
</body>
</html>
