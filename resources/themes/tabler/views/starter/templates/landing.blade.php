@php($hasStarterApps = \Aldhi88\StarterKit\Support\Starter\StarterAppRegistry::keys() !== [])

<main class="page min-vh-100 bg-light">
    <header class="navbar navbar-expand-md d-print-none" data-starter-region="page-header">
        <div class="container-xl">
            <div class="navbar-brand navbar-brand-autodark">{{ config('app.name') }}</div>
            <div class="navbar-nav flex-row order-md-last">
                <a href="{{ \Aldhi88\StarterKit\Support\Starter\StarterNavigation::authLoginUrl() }}" class="btn btn-primary">Login</a>
            </div>
        </div>
    </header>
    <section class="page-wrapper" data-starter-region="primary-content">
        <div class="container-tight py-6">
            <div class="text-center">
                <div class="page-pretitle">{{ $hasStarterApps ? 'Workspace siap' : 'Panduan awal project' }}</div>
                <h1 class="display-5 fw-bold mb-3">{{ $hasStarterApps ? 'Aplikasi tim siap digunakan' : 'Starterkit siap dikembangkan' }}</h1>
                <p class="text-secondary mb-4">{{ $hasStarterApps ? 'Masuk untuk melanjutkan ke App yang tersedia.' : 'Buat App pertama untuk memulai struktur module dan menu.' }}</p>
                <a href="{{ \Aldhi88\StarterKit\Support\Starter\StarterNavigation::authLoginUrl() }}" class="btn btn-primary">Masuk ke aplikasi</a>
            </div>
        </div>
    </section>
</main>
