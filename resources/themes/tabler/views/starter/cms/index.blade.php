<div @if ($resource === 'posts') data-cms-content-page @endif>
    @if ($resource === 'posts') @include('starter-shared::cms.assets') @endif
    <div class="page-header d-print-none mt-0 mb-3" data-starter-region="page-header">
        <div class="row g-3 align-items-center">
            <div class="col min-w-0">

                <h2 class="page-title">{{ $cmsResource->label() }}</h2>
                <div class="text-secondary mt-1">Kelola {{ str($cmsResource->label())->lower() }}, publikasi, dan arsipnya.</div>
            </div>
            <div class="col-12 col-md-auto">
                <a href="{{ route($cmsResource->routePrefix().'.create') }}" class="btn btn-primary" data-starter-navigate>
                    @include('starter.templates.layouts.icon', ['name' => 'file-plus', 'class' => 'icon-sm'])
                    Tambah {{ $cmsResource->singular() }}
                </a>
            </div>
        </div>
    </div>
    <div class="card" data-starter-region="data-table">
        @if ($resource === 'posts')<p class="cms-table-scroll-hint text-secondary small mb-0 px-3 pt-3">Geser tabel ke samping untuk melihat status, kategori, dan waktu terbit.</p>@endif
        <livewire:cms.table :resource="$resource" :key="'cms-table-'.$resource" />
    </div>
</div>
