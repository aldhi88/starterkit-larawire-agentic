@php($hasStarterApps = \Aldhi88\StarterKit\Support\Starter\StarterAppRegistry::keys() !== [])

<main class="dashcode-landing-page">
    <header class="dashcode-landing-nav" data-starter-region="page-header">
        <a href="{{ url('/') }}" class="dashcode-landing-brand">
            <img src="{{ asset('assets/dashcode/images/logo/logo-c.svg') }}" alt="">
            <strong>{{ config('app.name') }}</strong>
        </a>
        <a href="{{ \Aldhi88\StarterKit\Support\Starter\StarterNavigation::authLoginUrl() }}" class="dashcode-landing-login">Login</a>
    </header>

    <section class="dashcode-landing-hero dashcode-landing-hero-single" data-starter-region="primary-content">
        <div class="dashcode-landing-copy">
            <span class="dashcode-landing-kicker">{{ $hasStarterApps ? 'Workspace siap' : 'Panduan awal project' }}</span>
            <h1>{{ $hasStarterApps ? 'Aplikasi tim siap digunakan' : 'Starterkit siap dikembangkan' }}</h1>
            <p>{{ $hasStarterApps ? 'Masuk untuk melanjutkan ke App yang tersedia.' : 'Buat App pertama untuk memulai struktur module dan menu.' }}</p>
            <div class="dashcode-landing-actions">
                <a href="{{ \Aldhi88\StarterKit\Support\Starter\StarterNavigation::authLoginUrl() }}" class="btn btn-primary">Masuk ke aplikasi</a>
            </div>
        </div>
    </section>
</main>
