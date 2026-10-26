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
    <div class="page-header mb-5" data-starter-region="page-header">
        <div class="dashcode-page-heading w-full">
            <div class="min-w-0"><h2 class="page-title">Beranda</h2><div class="text-secondary mt-1">Kelola konten website dari satu tempat.</div></div>
            @if (\Illuminate\Support\Facades\Route::has('starter.cms.documentation'))
                <div class="shrink-0"><a href="{{ route('starter.cms.documentation') }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">Panduan Penggunaan CMS</a></div>
            @endif
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5" data-starter-region="dashboard-summary">
        @foreach ($shortcuts as $key => $shortcut)
            @if (array_key_exists($key, $summary))
                @php($itemResource = \Aldhi88\StarterKit\Support\Cms\CmsResource::from($key))
                <div class="min-w-0">
                    <div class="card h-full">
                        <div class="card-body p-6">
                            <div class="flex items-center justify-between gap-3 mb-4"><span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 text-primary-500">@include('starter.templates.layouts.icon', ['name' => $shortcut['icon'], 'class' => 'icon'])</span><span class="text-3xl font-semibold text-slate-900">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($summary[$key]) }}</span></div>
                            <h3 class="card-title">{{ $shortcut['label'] }}</h3><p class="text-secondary mb-0">{{ $shortcut['description'] }}</p>
                        </div>
                        <div class="card-footer p-4 border-t border-slate-200"><a href="{{ route($itemResource->routePrefix().'.index') }}" class="text-primary-500 font-medium" data-starter-navigate>Kelola {{ $shortcut['label'] }} →</a></div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
    @if (empty($summary))<div class="alert alert-info" role="note">Belum ada modul konten yang dapat Anda kelola. Hubungi pengelola role untuk memberikan akses.</div>@endif
</div>
