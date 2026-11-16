@php
    $examples = [
        ['title' => 'Konten dan kategori', 'description' => 'Ambil daftar terbit dengan pagination, atau batasi berdasarkan kode kategori.', 'code' => '$posts = $cms->posts(perPage: 12);
$news = $cms->posts(category: \'berita\', perPage: 12);
$post = $cms->post(\'judul-konten\');
$categories = $cms->postCategories();'],
        ['title' => 'Permalink', 'description' => 'Pola bawaan: /konten/{slug} dan /halaman/{slug} pada domain utama. Pengembang menyesuaikan starter.cms.permalinks dan membuat route publik yang memakai reader CMS; tautan tidak otomatis membuat halaman publik.', 'code' => '\\Aldhi88\\StarterKit\\Support\\Cms\\CmsPermalink::for(\'page\', \'tentang-kami\');'],
        ['title' => 'Halaman', 'description' => 'Ambil halaman berdasarkan slug atau kelompok kategorinya.', 'code' => '$pages = $cms->pages(category: \'profil\');
$page = $cms->page(\'tentang-kami\');
$categories = $cms->pageCategories();'],
        ['title' => 'Card', 'description' => 'Satu kategori dapat menjadi carousel, daftar layanan, sambutan, atau logo klien. Bentuk tampilannya ditentukan aplikasi Anda.', 'code' => '$slides = $cms->cards(\'beranda-carousel\');
$clients = $cms->cards(\'beranda-klien\');
$categories = $cms->cardCategories();'],
        ['title' => 'Media dan galeri', 'description' => 'Gunakan URL media yang aman. Foto galeri tersedia sesuai urutan yang disimpan.', 'code' => '$imageUrl = $cms->mediaUrl($card->image);
$logoUrl = $cms->mediaUrl($card->logo);
$gallery = $cms->gallery(\'kegiatan\');'],
        ['title' => 'Menu bertingkat', 'description' => 'Item menu dapat menuju URL khusus, konten, halaman, atau kategori. Tujuan CMS mengikuti alamat terbarunya. Ambil menu lewat kode, lalu gunakan url dan children untuk navigasi.', 'code' => '$menus = $cms->menus(\'main\');
$menu = $cms->menu(\'navigasi-utama\');
$tree = $menu ? $cms->menuTree($menu) : [];'],
        ['title' => 'Konten panjang dan komentar', 'description' => 'Render konten editor melalui renderer HTML yang aman, termasuk konten lama. Daftar memuat komentar utama yang terbit beserta balasan editornya.', 'code' => '$safeHtml = $cms->renderBody($post);
$comments = $cms->comments(\'judul-konten\');
foreach ($comments as $comment) {
    $replies = $comment->replies;
}'],
    ];
@endphp
<main class="page min-vh-100 bg-light">
    <header class="navbar navbar-expand-md d-print-none" data-starter-region="page-header">
        <div class="container-xl">
            @include('starter.templates.components.company-brand')
            @include('starter-shared::cms.back-link', ['backUrl' => url('/'), 'backLabel' => 'Kembali ke Beranda', 'backClass' => 'btn-outline-secondary ms-auto', 'backNavigate' => false])
        </div>
    </header>
    <section class="container-xl py-4" data-starter-region="primary-content">
        <div class="mb-4"><h1 class="display-6 fw-semibold">Panduan Penggunaan CMS</h1><p class="text-secondary">CMS menyediakan data konten. Programmer menentukan route, layout, dan tampilan website.</p></div>
        <div class="card mb-3"><div class="card-body"><h2 class="card-title">Mulai menggunakan CmsReader</h2><p>Inject <code>Aldhi88\StarterKit\Services\Cms\CmsReader</code> ke controller atau service aplikasi Anda sebagai <code>$cms</code>. Pembaca mengembalikan konten yang terbit, waktunya telah tiba, dan tidak diarsipkan.</p><p class="mb-0">Kode kategori merupakan kunci mapping yang stabil. Field Card seperti judul, subjudul, isi, gambar, thumbnail, logo, dan tautan bersifat opsional; pilih field sesuai komponen frontend Anda.</p></div></div>
        <div class="row g-3">
            @foreach ($examples as $example)
                <div class="col-12 col-lg-6"><article class="card h-100"><div class="card-body"><h2 class="card-title">{{ $example['title'] }}</h2><p class="text-secondary">{{ $example['description'] }}</p><pre class="overflow-auto bg-light rounded p-3 mb-0"><code>{{ $example['code'] }}</code></pre></div></article></div>
            @endforeach
        @include('starter-shared::cms.card-documentation')
</div>
    </section>
</main>
