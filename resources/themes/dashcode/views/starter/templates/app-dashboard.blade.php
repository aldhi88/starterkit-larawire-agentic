<div class="space-y-5">
    <header class="flex flex-wrap items-center justify-between gap-3" aria-label="Header halaman" data-starter-region="page-header">
        <div>
            <p class="page-pretitle">{{ config($appConfigKey.'.name') }}</p>
            <h1 class="page-title">{{ $section }}</h1>
        </div>
    </header>

    <section class="card" data-starter-region="primary-content">
        <div class="card-body">{{ config($appConfigKey.'.desc') }}</div>
    </section>
</div>
