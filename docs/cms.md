# Menggunakan Data CMS

CMS mengelola konten pada `cms.<APP_DOMAIN>`. Programmer menentukan route,
komponen, dan tampilan website publik di aplikasi Laravel masing-masing. Landing
bawaan tetap tersedia; CMS tidak memasang tema website publik.

## Instalasi

Pilih **Ya** pada pertanyaan CMS di wizard `php artisan starter:install`.
Installer membuat App dashboard pertama sesuai isian pengguna, kemudian menambah
App CMS sebagai App kedua. Jika menekan Enter atau memilih **Tidak**, tidak ada source, migration,
atau tabel CMS yang dipublikasikan.

Setelah memperbarui package LaraWire melalui Composer, proyek lama tetap memakai konfigurasi sebelumnya. Untuk menambahkan CMS, jalankan:

```bash
php artisan starter:cms --dry-run
php artisan starter:cms
```

Opsi `--dry-run` menampilkan file yang akan dibuat atau dipertahankan. Aktivasi menjalankan migration CMS bawaan dan menyinkronkan App CMS. Migration bisnis yang masih tertunda tetap mengikuti proses deployment proyek. Perintah ini hanya untuk local. Untuk production, sertakan source host yang telah
ditinjau dalam deployment dan jalankan `starter:deploy`. Jangan menjalankan
installer atau reset di production.

## Pengelolaan di dashboard

Kategori post, halaman, Card, dan menu memakai form nama di samping daftar.
Mengubah nama tidak mengubah kode mapping yang sudah dipakai programmer.

Editor konten menyediakan pemformatan visual dan integrasi pustaka media.
Gambar dapat dipilih dari pustaka atau diunggah saat menulis; dokumen PDF dapat
disisipkan sebagai tautan. Pemformatan tetap disaring di server.

Postingan memakai judul wajib dan tanggal/jam publikasi, tanpa nama internal atau
urutan manual. Postingan baru dimulai sebagai draf dengan tanggal/jam saat ini
dalam WIB (`Asia/Jakarta`). Formulir, tombol **Sekarang**, dan tanggal pada daftar
Konten memakai WIB. Nilai formulir dikonversi ke zona waktu aplikasi Laravel
sebelum disimpan agar penjadwalan dan pembaca publik tetap konsisten. Daftar publik
diurutkan dari waktu publikasi terbaru. Ukuran gambar dari
editor disimpan sebagai atribut lebar/tinggi yang aman; frontend tetap mengatur
perilaku responsifnya sendiri.

Pustaka Media menyediakan area upload beberapa file di atas daftar visual.
Galeri menampilkan seluruh pustaka yang sama dengan pencarian dan filter jenis
file. Detail file dan metadata dapat diedit dari pustaka. Pengelolaan album yang
sudah ada tersedia sebagai fitur sekunder; mapping album di bawah tetap berlaku.

Item menu dapat digeser untuk mengubah urutan dan hubungan induk-anak, hingga
tiga tingkat. Tombol pengurutan tetap tersedia sebagai alternatif yang mudah
diakses lewat keyboard.

## Mengambil konten

Gunakan dependency injection `Aldhi88\StarterKit\Services\Cms\CmsReader` pada
controller atau service aplikasi. Hindari query model CMS langsung untuk output
publik, karena reader menerapkan batas publikasi dan relasi yang aktif.

```php
use Aldhi88\StarterKit\Services\Cms\CmsReader;

public function index(CmsReader $cms)
{
    return view('website.home', [
        'posts' => $cms->posts(perPage: 12),
        'news' => $cms->posts(category: 'berita', perPage: 6, pageName: 'newsPage'),
        'carousel' => $cms->cards('beranda-carousel'),
        'clients' => $cms->cards('beranda-klien'),
        'greeting' => $cms->cards('beranda-sambutan', limit: 1)->first(),
        'gallery' => $cms->gallery('kegiatan-perusahaan'),
        'mainMenus' => $cms->menus('main'),
    ]);
}
```

`posts()` dan `pages()` mengembalikan paginator, dengan ukuran halaman 1–100.
Parameter pagination bawaannya adalah `postsPage`, `pagesPage`, dan
`commentsPage`. Berikan `pageName` yang berbeda saat menampilkan beberapa daftar
independen pada halaman yang sama, seperti `newsPage` pada contoh di atas.

| Kebutuhan | Pemanggilan |
|---|---|
| Semua postingan terbit, per halaman | `$cms->posts(perPage: 12)` |
| Postingan berdasarkan kode kategori | `$cms->posts(category: 'berita')` |
| Detail postingan berdasarkan slug | `$cms->post('judul-berita')` |
| Daftar halaman terbit | `$cms->pages()` |
| Detail halaman berdasarkan slug | `$cms->page('tentang-kami')` |
| Kategori aktif menurut jenis konten | `$cms->postCategories()`, `$cms->pageCategories()`, `$cms->cardCategories()` |
| Card menurut kode kategori, urut posisi | `$cms->cards('beranda-layanan')` |
| Galeri beserta foto aktif | `$cms->gallery('kegiatan-perusahaan')` |
| Menu menurut kode kategori menu | `$cms->menus('main')` |
| Satu menu berdasarkan kode | `$cms->menu('navigasi-header')` |
| Struktur dropdown satu menu | `$cms->menuTree($menu)` |
| URL media yang aman | `$cms->mediaUrl($content->image)` |
| Isi panjang dengan pemformatan aman | `$cms->renderBody($content)` |
| Komentar yang disetujui untuk postingan | `$cms->comments('judul-berita', perPage: 20)` |

Reader mengecualikan konten draf, konten arsip, jadwal terbit mendatang, kategori
nonaktif/arsip, dan media nonaktif/arsip. Lookup yang tidak tersedia mengembalikan
`null`; controller website menentukan respons 404 atau penggantinya.

Pada tabel Postingan **Tabler**, menu Aksi per baris menyediakan terbitkan sekarang,
jadikan draf, dan ubah kategori. Aksi massal dapat diterapkan pada baris terpilih
atau semua hasil filter aktif (termasuk halaman lain), maksimal 1.000 postingan.
Dialog menampilkan jumlah dan cakupan sebelum menyimpan. Kategori dipilih melalui pohon ringkas. Klik panah, nama induk, atau baris induk untuk membuka cabang; hanya kotak centang yang memilih kategori. Tampilan dua kolom menjaga daftar tetap ringkas, tanpa
pilihan mode. Pencarian membuka jalur induk dari hasil yang cocok. Melipat cabang
tidak menghapus centangnya. Aksi massal
dimulai dengan pilihan kosong dan mengganti kategori seluruh postingan terpilih;
edit satu baris menampilkan pilihan yang sudah tersimpan. Menyimpan tanpa centang
mengosongkan kategori. Memilih induk memilih seluruh turunannya, memilih subkategori
ikut memilih semua induknya, dan melepas induk melepas seluruh turunannya.
Melepas subkategori terakhir juga melepas centang induknya. Pilihan lama yang hanya
berisi induk ditampilkan sebagai pilihan seluruh cabang saat diedit; perubahan
baru disimpan setelah menekan Simpan. Perilaku
yang sama berlaku pada form postingan Tabler.

Terbitkan sekarang menerbitkan draf atau jadwal mendatang saat itu juga; tanggal
postingan yang sudah tayang dipertahankan. Draf tidak tampil di website, termasuk
saat tanggal terbitnya telah lewat. Status terjadwal ditampilkan terpisah dari terbit.
Server memeriksa ulang hak akses, seluruh pilihan, dan versi data saat penyimpanan;
perubahan serentak yang membuat pratinjau tidak berlaku ditolak tanpa perubahan parsial.
Seluruh aksi dicatat di audit log dan tabel diperbarui tanpa muat ulang halaman.

Satu postingan dapat dipilih ke beberapa kategori post. Kategori post dapat memiliki
induk dan subkategori. Pemanggilan `posts(category: 'berita')` mencakup postingan
di subkategori Berita yang aktif; pemilihan kategori di Tabler otomatis menyertakan induk kategori anak.
Postingan yang diambil melalui `CmsReader` memuat kategori aktif dalam relasi
`$post->categories`; `$post->category` tetap tersedia untuk integrasi lama.

Ringkasan post (`$post->excerpt`) yang kosong otomatis mengambil maksimal 200 karakter
dari awal isi tanpa markup. Nilai otomatis tidak disimpan sebagai isian manual, sehingga
mengikuti perubahan isi. Subjudul tetap opsional dan tidak memiliki teks pengganti.

## Mapping Card

Kategori Card mempunyai **kode** untuk mapping dan **nama** untuk pengelola CMS.
Programmer mengambil data berdasarkan kode. Mengubah nama kategori tidak mengubah
mapping; mengubah kode memerlukan penyesuaian kode frontend.

Satu Card dapat dipakai sebagai satu slide, satu layanan, satu logo klien, atau
satu sambutan. `name` adalah nama internal pengelolaan. Field tampil bersifat
opsional: `title`, `subtitle`, `slug`, `excerpt`, `body`, `image`, `thumbnail`,
`logo`, `link_label`, dan `link_url`. Gunakan hanya field yang dibutuhkan komponen.
Card diurutkan berdasarkan `position`, kemudian ID. Tidak ada layout atau bentuk
visual Card yang dipaksakan CMS.

```blade
@foreach ($clients as $client)
    @if ($logoUrl = $cms->mediaUrl($client->logo))
        <img src="{{ $logoUrl }}" alt="{{ $client->logo?->alt_text ?? $client->name }}">
    @endif
@endforeach
```

Pada contoh Blade, teruskan instance `$cms` dari controller atau gunakan
komponen/helper milik aplikasi yang memanggil reader. Teks biasa selalu di-escape
dengan `{{ }}`. Konten editor baru disimpan sebagai HTML yang telah disaring;
konten Markdown lama tetap didukung. Hanya hasil `renderBody()` yang boleh
dirender sebagai HTML karena renderer menyaring markup dan tautan berbahaya.
Jangan merender isi database secara langsung dengan `{!! $content->body !!}`.

Field `body_format` membedakan `html` dan `markdown`. Programmer tetap memakai
`renderBody()` untuk kedua format. URL gambar dari pustaka disiapkan untuk domain
deployment saat ini; jangan membentuk URL dari path penyimpanan privat.

## Media, galeri, dan menu

`image`, `thumbnail`, dan `logo` telah dimuat bersama hasil reader konten. Gunakan
objek relasi tersebut saat meminta URL media agar tidak menambah query per item.
File disimpan pada local disk privat; URL publik menggunakan ID publik dan
memeriksa status media. Jangan membuat URL dari kolom `path` atau mengekspos path
filesystem.

Galeri memuat relasi `media` yang telah diurutkan. Caption dan alt text berada
pada media. Pustaka media dapat digunakan ulang pada beberapa Card atau konten.

Kategori menu bawaan berkode `main`, `other`, dan `footer`, dengan label Menu
Utama, Menu Lainnya, dan Menu Bawah. Pengelola dapat membuat kategori tambahan.
Setiap menu mempunyai kode sendiri, kategori, serta item berurutan dengan
`key`, `label`, `url`, dan `parent_key`. `menuTree()` mengembalikan susunan anak
melalui key `children`, dengan kedalaman maksimal tiga tingkat. Programmer
memilih sendiri HTML dan perilaku dropdown-nya.

## Komentar

Dashboard menampilkan komentar pembaca pada konten. Buka **Aksi → Tinjau dan balas** untuk membaca komentar asli, mengatur status, atau mengirim balasan sebagai editor. Form moderasi mempertahankan teks dan identitas pengirim. Status Terbit menampilkan komentar, Disembunyikan menariknya dari website, dan Diblokir menolak komentar tersebut. Integrasi formulir
komentar publik adalah tanggung jawab aplikasi: tentukan route, CSRF, rate limit,
validasi, dan pencegahan spam. CMS tidak memasang endpoint tulis anonim secara
otomatis. Jangan menampilkan email pengirim sebagai data publik.

Service `Aldhi88\StarterKit\Services\Cms\CmsCommentSubmission` menyediakan
`submit(string $postSlug, string $authorName, ?string $authorEmail, string $body)`
untuk controller frontend milik aplikasi. Service memeriksa postingan yang sudah
terbit, memvalidasi input, menyimpan teks biasa, dan selalu membuat komentar
berstatus `pending`. Endpoint aplikasi tetap wajib memakai CSRF dan pembatasan
frekuensi; service tidak menggantikan perlindungan pada route tersebut. Reader
`comments($postSlug)` memaginasi komentar utama yang terbit. Setiap komentar memuat balasan terbit melalui `$comment->replies`, berurutan dari terlama. Saat komentar utama disembunyikan, diblokir, atau diarsipkan, balasannya turut tersembunyi. Tampilkan teks komentar dan balasan dengan ekspresi Blade yang di-escape.

## Hak akses dan pembaruan

Akses diberikan melalui role/module starter kit: Dashboard CMS, Konten, Halaman,
Card, Media, Menu, dan Komentar. Kategori mengikuti hak akses modulnya. Akun yang
dapat mengelola konten belum tentu dapat membuka pengaturan pengguna global.

Perbarui package melalui Composer, periksa `starter:sync cms --dry-run`, lalu
jalankan `starter:sync cms` di local. Periksa override view aktif melalui perintah
view bawaan bila aplikasi mempunyai penyesuaian struktur dashboard.

Pada tabel postingan Tabler, klik isi kolom Kategori (termasuk “Tanpa kategori”) untuk mengubah kategori postingan tersebut tanpa membuka form penuh. Pilihan baru disimpan hanya setelah menekan Simpan kategori.


## Static pages and public permalinks

Pages are stable editorial entries such as About, Profile, and Vision and Mission.
They use a required title, generated editable slug, rich body, required active page category,
optional thumbnail, subtitle and summary, plus draft/published status and scheduling defaulted to the current WIB date/time. Categories can be created and selected inline without leaving the editor; no fallback category is created. The media picker is available directly from the editor toolbar.
The internal name follows the title. Existing positions and media references are preserved.
Navigation ordering belongs to the menu, not the page form. Pages do not expose comments.

`CmsPermalink::for('post', $slug)` and `CmsPermalink::for('page', $slug)` build
root-website addresses using `config('app.url')` and the host's
`starter.cms.permalinks.post` / `starter.cms.permalinks.page` patterns.
Defaults are `/konten/{slug}` and `/halaman/{slug}`. Configure these paths to match
your public routes. These are URL mappings, not routes installed by the CMS.
Implement public routes in the host website and resolve entries with
`app(CmsReader::class)->post($slug)` or `->page($slug)`; return 404 for null results.
Use the existing safe rich-content renderer for the body. Copying a draft's address
does not publish it or expose it through the public reader. The editor copies the
saved slug; save changes first when editing a slug. Menu target selection will be
implemented separately; use these same URL mappings when integrating navigation.

## Reusable card presets

Cards continue to use `cms_contents` with `kind = card`; presets do not create
separate tables or public layouts. The additive migration adds `card_preset`
and nullable JSON `card_data`. Existing cards open with `free`; new cards start
with `service`. The registry `CmsCardPresets::all()` defines 19 editorial presets.
Changing a preset adapts field labels, grouping, and visibility. Hidden values remain
in the form and are saved. “Tampilkan semua field tambahan” reveals all fields.
The management `name` is required; public title, body, media, category and links
remain optional for backwards-compatible media-only cards.

Use `$cms->cards('kode-kategori')` to read published, available cards in position
order. The same reader returns the preset and cast `card_data` array:

```php
$cards = $cms->cards('kampus-dalam-angka');
foreach ($cards as $card) {
    $value = $card->card_data['value'] ?? '';
    $unit = $card->card_data['unit'] ?? '';
    // Escape plain text in the public Blade view: {{ $value }} {{ $unit }}.
}
```

Structured keys are `person`, `role`, `value`, `unit`, `period`, `organization`,
`starts_at`, `ends_at`, `location`, `contact`, and `hours`. Event timestamps are
local WIB strings (`Y-m-d\TH:i`); parse them explicitly with `Asia/Jakarta`.
They describe the event, independently from publication scheduling. An end time
must not precede a supplied start time. Statistic values are strings to support
formatted numbers and prefixes/suffixes; they are not analytic counters.

Use `image`, `thumbnail`, and `logo` relations for selected image media. Download
and video presets use the validated `link_url`; they accept URLs, not embed code.
Rendering, file destinations, video players, responsive image cropping, and menu
integration remain host-developer responsibilities. Never render plain metadata
as raw HTML; use the existing safe rich-content renderer for `body`.

## Editorial workspace

Posts and pages share the same authoring composition across Tabler, Vuexy, and
Dashcode: title and editor in the main column, publication, thumbnail, and category
in the sidebar. The generated slug is summarized in a disclosure; optional
subtitle/summary fields expand when already filled or invalid. Thumbnail tiles
open the existing media picker and support replacement/removal.

Card types are chosen from a searchable, flat inline list. Escape closes
the catalog and restores focus. Profile/partner cards pair identity with media;
statistics and event details use paired fields; long copy keeps the editor width.
New cards derive the dashboard name from the title until the name is edited.
Advanced fields remain available under “Opsi tambahan”; switching types never
clears values. Publication uses explicit draft/published choices and the existing
WIB scheduling rules. Typing and card-type selection do not issue Livewire requests. Saving persists
the form; media uploads and category creation keep their own existing actions.

Composition references: the [Statamic demo](https://demo.statamic.com/cp),
[Statamic sets](https://statamic.dev/fieldtypes/replicator), and
[WordPress post/page settings](https://wordpress.org/documentation/article/page-post-settings-sidebar/).
Native controls come from the indexed Tabler form-elements/selectgroup, Vuexy
forms-custom-options, and Dashcode radio/card references. These references inform
editorial interactions; public card rendering remains developer-owned.

## Single Cards and repeatable sections

The dashboard uses the term **Card**. A category groups multiple Cards; a preset
controls the fields shown inside a Card. Neither is required to look up one Card
by ID or slug. Existing records remain single items. New Cards start in collection
mode for these 16 list-oriented presets:

| Preset | Repeatable entry |
| --- | --- |
| banner | Slide: image, title, caption, button/link |
| profile | Team member: photo, name, role, biography, profile link |
| partner | Brand/partner: logo, name, website |
| service, product | Service, feature, product or program |
| testimonial, statistic | Quote/author or number/unit/label |
| faq, step, timeline | Question/answer, process step or historical event |
| event, award, facility | Agenda item, achievement or facility |
| contact, download, video | Contact channel, document or video |

Sambutan (`greeting`), CTA (`cta`), and free form (`free`) remain single. The
“Satu item / Beberapa item” choice is available for list presets. It preserves
existing fields and items; it does not automatically convert a single entry into
an item. Each collection allows up to 50 entries with add, reorder, remove, and
undo-last-removal actions. A published collection requires at least one item.
A draft may start empty. Each added item needs some text or media before saving.

Storage remains one `cms_contents` row. `card_data.mode` is `single` or
`collection`; `card_data.items` holds an ordered list. Each item has a stable
`key`, common text/media/link fields, and any applicable metadata such as `role`,
`value`, `unit`, `starts_at`, and `ends_at`. Nested `body` is **plain text**;
escape it in Blade. The existing parent `body` keeps its rich-text contract.
Images are checked server-side and tracked in `cms_content_media`, including
when items are temporarily hidden by a preset/mode change.

```php
$card = $cms->card(42);                 // Integer ID shown in the edit form.
$card = $cms->card('tim-pimpinan');     // Optional stable slug.
$items = $card && ($card->card_data['mode'] ?? 'single') === 'collection'
    ? ($card->card_data['items'] ?? []) : [];
foreach ($items as $item) {
    $photoUrl = $cms->mediaUrl(isset($item['image_id']) ? (int) $item['image_id'] : null);
    // Blade: {{ $item['title'] ?? '' }}, {{ $item['subtitle'] ?? '' }}.
}
```

Both lookups enforce publication time, draft/archive state, and category
visibility when a category is assigned. `$cms->cards('category-code')` still
returns multiple published Cards in their configured order. No public page or
URL is generated automatically. Publication and event inputs now share the same
Indonesian 24-hour calendar in all three dashboard themes, including on mobile.

## Tujuan item menu

Pada Susunan Menu, buka Detail lalu pilih URL khusus, Konten, Halaman, Kategori Konten, atau Kategori Halaman.
Pencarian mengembalikan hingga 25 tujuan per kata pencarian. Konten dan halaman dapat dipilih setelah terbit, dan kategori setelah aktif.
Pemilihan mengisi label yang masih kosong. Editor dapat mengganti label navigasi tanpa mengubah judul sumber.

Menu menyimpan ID dan jenis tujuan CMS dalam JSON yang sudah tersedia. Saat website membaca menu dengan `$cms->menu($code)` atau `$cms->menus($category)`, setiap item yang tersedia memiliki `url` sesuai alamat terbaru. `$cms->menuTree($menu)` menambahkan `children`. Jika tujuan diarsipkan atau kembali menjadi draf, item dan cabangnya disembunyikan dari navigasi publik. Item URL khusus yang sudah ada tetap memakai alamat yang disimpan.

Untuk kategori, pola bawaan adalah `/konten/kategori/{code}` dan `/halaman/kategori/{code}`. Sesuaikan `starter.cms.permalinks.post_category` dan `starter.cms.permalinks.page_category` dengan route website. Pengembang menyediakan halaman publik untuk pola tersebut.
