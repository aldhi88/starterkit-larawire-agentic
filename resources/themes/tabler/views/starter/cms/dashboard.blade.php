@php
    $shortcuts = [
        'posts' => ['label' => 'Konten', 'description' => 'Berita, artikel, dan publikasi terjadwal.'],
        'pages' => ['label' => 'Halaman', 'description' => 'Konten tetap untuk kebutuhan website.'],
        'cards' => ['label' => 'Card', 'description' => 'Slide, layanan, sambutan, dan logo klien.'],
        'media' => ['label' => 'Pustaka Media', 'description' => 'Gambar dan dokumen yang dapat dipakai ulang.'],
        'galleries' => ['label' => 'Album Foto', 'description' => 'Kumpulan foto dan urutan penampilannya.'],
        'menus' => ['label' => 'Menu', 'description' => 'Kelompok tautan dan susunan dropdown.'],
        'comments' => ['label' => 'Komentar', 'description' => 'Tinjau dan moderasi komentar konten.'],
    ];
@endphp
<div data-cms-workspace>
    @include('starter-shared::cms.assets')
    <div class="page-header d-print-none mt-0 mb-3" data-starter-region="page-header">
        <div class="row g-3 align-items-center">
            <div class="col"><h2 class="page-title">Beranda</h2><div class="text-secondary mt-1">Pilih modul untuk mengelola isi website.</div></div>
            @if (\Illuminate\Support\Facades\Route::has('starter.cms.documentation'))
                <div class="col-auto"><a href="{{ route('starter.cms.documentation') }}" target="_blank" rel="noopener noreferrer" class="btn cms-btn-neutral">@include('starter.cms.action-icon', ['action' => 'guide']) Panduan Penggunaan CMS</a></div>
            @endif
        </div>
    </div>
    <div class="row g-3" data-starter-region="dashboard-summary">
        @foreach ($shortcuts as $key => $shortcut)
            @if (array_key_exists($key, $summary))
                @php($itemResource = \Aldhi88\StarterKit\Support\Cms\CmsResource::from($key))
                <div class="col-12 col-md-6 col-xl-4">
                    <a class="card cms-dashboard-link" href="{{ route($itemResource->managementIndexRoute()) }}" data-starter-navigate>
                        <div class="card-body cms-dashboard-tile">
                            @include('starter-shared::cms.module-icon', ['module' => $key])
                            <div class="cms-dashboard-details">
                                <h3 class="card-title">{{ $shortcut['label'] }}</h3>
                                <p class="text-secondary">{{ $shortcut['description'] }}</p>
                            </div>
                            <strong class="cms-dashboard-count">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($summary[$key]) }}</strong>
                            <span class="cms-dashboard-arrow" aria-hidden="true">@include('starter.templates.layouts.icon', ['name' => 'chevron-right', 'class' => 'icon'])</span>
                        </div>
                    </a>
                </div>
            @endif
        @endforeach
    </div>
    @if (empty($summary))<div class="alert alert-info" role="note">Belum ada modul konten yang dapat Anda kelola. Hubungi pengelola role untuk memberikan akses.</div>@endif
</div>
