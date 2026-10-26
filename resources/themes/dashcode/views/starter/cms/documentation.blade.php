@php
    $examples = [
        ['title' => 'Postingan dan kategori', 'description' => 'Ambil daftar terbit dengan pagination, atau batasi berdasarkan kode kategori.', 'code' => '$posts = $cms->posts(perPage: 12);
$news = $cms->posts(category: \'berita\', perPage: 12);
$post = $cms->post(\'judul-postingan\');
$categories = $cms->postCategories();'],
        ['title' => 'Halaman', 'description' => 'Ambil halaman berdasarkan slug atau kelompok kategorinya.', 'code' => '$pages = $cms->pages(category: \'profil\');
$page = $cms->page(\'tentang-kami\');
$categories = $cms->pageCategories();'],
        ['title' => 'Card', 'description' => 'Satu kategori dapat menjadi carousel, daftar layanan, sambutan, atau logo klien. Bentuk tampilannya ditentukan aplikasi Anda.', 'code' => '$slides = $cms->cards(\'beranda-carousel\');
$clients = $cms->cards(\'beranda-klien\');
$categories = $cms->cardCategories();'],
        ['title' => 'Media dan galeri', 'description' => 'Gunakan URL media yang aman. Foto galeri tersedia sesuai urutan yang disimpan.', 'code' => '$imageUrl = $cms->mediaUrl($card->image);
$logoUrl = $cms->mediaUrl($card->logo);
$gallery = $cms->gallery(\'kegiatan\');'],
        ['title' => 'Menu bertingkat', 'description' => 'Kategori menu bawaan: main, other, footer. Kode menu dan struktur induk-anak dapat diambil untuk navigasi Anda.', 'code' => '$menus = $cms->menus(\'main\');
$menu = $cms->menu(\'navigasi-utama\');
$tree = $menu ? $cms->menuTree($menu) : [];'],
        ['title' => 'Konten panjang dan komentar', 'description' => 'Render konten editor melalui renderer HTML yang aman, termasuk konten lama. Pembaca hanya mengembalikan komentar yang disetujui.', 'code' => '$safeHtml = $cms->renderBody($post);
$comments = $cms->comments(\'judul-postingan\');'],
    ];
@endphp
<main class="dashcode-landing-page">
    <header class="dashcode-landing-nav" data-starter-region="page-header">

            @include('starter.templates.components.company-brand')
            <a href="{{ url('/') }}" class="btn btn-secondary">Kembali ke Beranda</a>

    </header>
    <section class="container mx-auto p-6 space-y-5" data-starter-region="primary-content">
        <div class="mb-6"><h1 class="text-3xl font-semibold text-slate-900">Panduan Penggunaan CMS</h1><p class="text-secondary">CMS menyediakan data konten. Programmer menentukan route, layout, dan tampilan website.</p></div>
        <div class="card mb-5"><div class="card-body p-6 space-y-4"><h2 class="card-title">Mulai menggunakan CmsReader</h2><p>Inject <code>Aldhi88\StarterKit\Services\Cms\CmsReader</code> ke controller atau service aplikasi Anda sebagai <code>$cms</code>. Pembaca mengembalikan konten yang terbit, waktunya telah tiba, dan tidak diarsipkan.</p><p class="mb-0">Kode kategori merupakan kunci mapping yang stabil. Field Card seperti judul, subjudul, isi, gambar, thumbnail, logo, dan tautan bersifat opsional; pilih field sesuai komponen frontend Anda.</p></div></div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            @foreach ($examples as $example)
                <div class="min-w-0"><article class="card h-full"><div class="card-body p-6 space-y-4"><h2 class="card-title">{{ $example['title'] }}</h2><p class="text-secondary">{{ $example['description'] }}</p><pre class="overflow-x-auto rounded bg-slate-100 p-4 text-sm"><code>{{ $example['code'] }}</code></pre></div></article></div>
            @endforeach
        </div>
    </section>
</main>
