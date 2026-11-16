<div data-cms-workspace @if (in_array($resource, ['posts', 'pages'], true)) data-cms-content-page @endif>
    @include('starter-shared::cms.assets')
    <div class="page-header d-print-none mt-0 mb-3" data-starter-region="page-header">
        <div class="row g-3 align-items-center">
            <div class="col min-w-0">

                <h2 class="page-title">{{ match ($resource) { 'posts' => 'Data Konten', 'pages' => 'Data Halaman', 'cards' => 'Data Card', 'galleries' => 'Album Foto', default => $cmsResource->label() } }}</h2>
                <div class="text-secondary mt-1">{{ $cmsResource->indexDescription() }}</div>
            </div>
            <div class="col-12 col-md-auto cms-header-actions">
                @if ($resource === 'galleries')
                    @include('starter-shared::cms.back-link', ['backUrl' => route('cms.media.galleries.index'), 'backLabel' => 'Kembali ke Galeri', 'backClass' => 'cms-btn-neutral'])
                @endif
                @if ($resource !== 'comments')
                    <a href="{{ route($cmsResource->routePrefix().'.create') }}" class="btn btn-primary" data-starter-navigate>
                        @include('starter.cms.action-icon', ['action' => 'add'])
                        Tambah {{ $cmsResource->singular() }}
                    </a>
                @endif
            </div>
        </div>
    </div>
    <div class="card" data-starter-region="data-table">
        @if (in_array($resource, ['posts', 'pages'], true))<p class="cms-table-scroll-hint text-secondary small mb-0 px-3 pt-3">Geser tabel untuk melihat kolom lainnya.</p>@endif
        <livewire:cms.table :resource="$resource" :key="'cms-table-'.$resource" />
    </div>
</div>
