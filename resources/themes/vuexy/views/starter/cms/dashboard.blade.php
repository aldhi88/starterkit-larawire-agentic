@php
    $shortcuts = [
        'posts' => ['label' => 'Postingan', 'description' => 'Berita, artikel, dan publikasi terjadwal.', 'icon' => 'file-text'],
        'pages' => ['label' => 'Halaman', 'description' => 'Konten tetap untuk kebutuhan website.', 'icon' => 'file-text'],
        'cards' => ['label' => 'Card', 'description' => 'Slide, layanan, sambutan, dan logo klien.', 'icon' => 'layout-cards'],
        'media' => ['label' => 'Pustaka Media', 'description' => 'Gambar dan dokumen yang dapat dipakai ulang.', 'icon' => 'photo'],
        'galleries' => ['label' => 'Galeri', 'description' => 'Kumpulan foto dan urutan penampilannya.', 'icon' => 'photo'],
        'menus' => ['label' => 'Menu', 'description' => 'Kelompok tautan dan susunan dropdown.', 'icon' => 'menu-2'],
        'comments' => ['label' => 'Komentar', 'description' => 'Tinjau dan moderasi komentar postingan.', 'icon' => 'messages'],
    ];
@endphp
<div>
    <div class="page-header d-print-none mt-0 mb-3" data-starter-region="page-header">
        <div class="row g-3 align-items-center">
            <div class="col"><h2 class="page-title">Beranda</h2><div class="text-secondary mt-1">Kelola konten website dari satu tempat.</div></div>
            @if (\Illuminate\Support\Facades\Route::has('starter.cms.documentation'))
                <div class="col-auto"><a href="{{ route('starter.cms.documentation') }}" target="_blank" rel="noopener noreferrer" class="btn ">Panduan Penggunaan CMS</a></div>
            @endif
        </div>
    </div>
    <div class="row g-3" data-starter-region="dashboard-summary">
        @foreach ($shortcuts as $key => $shortcut)
            @if (array_key_exists($key, $summary))
                @php($itemResource = \Aldhi88\StarterKit\Support\Cms\CmsResource::from($key))
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-3"><span class="avatar bg-label-primary">@include('starter.templates.layouts.icon', ['name' => $shortcut['icon'], 'class' => 'icon'])</span><span class="h1 mb-0">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($summary[$key]) }}</span></div>
                            <h3 class="card-title">{{ $shortcut['label'] }}</h3><p class="text-secondary mb-0">{{ $shortcut['description'] }}</p>
                        </div>
                        <div class="card-footer"><a href="{{ route($itemResource->routePrefix().'.index') }}" class="link-primary fw-medium" data-starter-navigate>Kelola {{ $shortcut['label'] }} →</a></div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
    @if (empty($summary))<div class="alert alert-info" role="note">Belum ada modul konten yang dapat Anda kelola. Hubungi pengelola role untuk memberikan akses.</div>@endif
</div>
