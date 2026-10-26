<div @if ($resource === 'posts') data-cms-content-page @endif>
    @if ($resource === 'posts') @include('starter-shared::cms.assets') @endif
    <div class="page-header mb-5" data-starter-region="page-header">
        <div class="dashcode-page-heading w-full">
            <div class="min-w-0">

                <h2 class="page-title">{{ $cmsResource->label() }}</h2>
                <div class="text-secondary mt-1">Kelola {{ str($cmsResource->label())->lower() }}, publikasi, dan arsipnya.</div>
            </div>
            <div class="shrink-0">
                <a href="{{ route($cmsResource->routePrefix().'.create') }}" class="btn btn-primary" data-starter-navigate>
                    @include('starter.templates.layouts.icon', ['name' => 'file-plus', 'class' => 'icon-sm'])
                    Tambah {{ $cmsResource->singular() }}
                </a>
            </div>
        </div>
    </div>
    <div class="card dashcode-table-card" data-starter-region="data-table">
        @if ($resource === 'posts')<p class="cms-table-scroll-hint text-sm text-slate-500 mb-0 px-4 pt-4">Geser tabel ke samping untuk melihat status, kategori, dan waktu terbit.</p>@endif
        <livewire:cms.table :resource="$resource" :key="'cms-table-'.$resource" />
    </div>
</div>
