<div>
    <div class="page-header d-print-none mt-0 mb-3" aria-label="Header halaman" data-starter-region="page-header">
        <div class="row g-3 align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ config($appConfigKey.'.name') }}</div>
                <h2 class="page-title">{{ $section }}</h2>
            </div>
        </div>
    </div>
    <div class="card" data-starter-region="primary-content"><div class="card-body">{{ config($appConfigKey.'.desc') }}</div></div>
</div>
