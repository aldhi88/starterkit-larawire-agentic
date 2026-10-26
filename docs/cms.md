# Menggunakan Data CMS

CMS mengelola konten pada `cms.<APP_DOMAIN>`. Programmer menentukan route,
komponen, dan tampilan website publik di aplikasi Laravel masing-masing. Landing
bawaan tetap tersedia; CMS tidak memasang tema website publik.

## Instalasi

Pilih **Ya** pada pertanyaan CMS di wizard `php artisan starter:install`.
Installer membuat App dashboard pertama sesuai isian pengguna, kemudian menambah
App CMS sebagai App kedua. Jika memilih **Tidak**, tidak ada source, migration,
atau tabel CMS yang dipublikasikan.

Instalasi yang sudah ada dapat menambahkan CMS tanpa reset:

```bash
php artisan starter:cms --dry-run
php artisan starter:cms
```

Perintah ini hanya untuk local. Untuk production, sertakan source host yang telah
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

Dashboard CMS menyediakan moderasi komentar postingan. Integrasi formulir
komentar publik adalah tanggung jawab aplikasi: tentukan route, CSRF, rate limit,
validasi, dan pencegahan spam. CMS tidak memasang endpoint tulis anonim secara
otomatis. Jangan menampilkan email pengirim sebagai data publik.

Service `Aldhi88\StarterKit\Services\Cms\CmsCommentSubmission` menyediakan
`submit(string $postSlug, string $authorName, ?string $authorEmail, string $body)`
untuk controller frontend milik aplikasi. Service memeriksa postingan yang sudah
terbit, memvalidasi input, menyimpan teks biasa, dan selalu membuat komentar
berstatus `pending`. Endpoint aplikasi tetap wajib memakai CSRF dan pembatasan
frekuensi; service tidak menggantikan perlindungan pada route tersebut. Reader
`comments($postSlug)` hanya mengembalikan komentar yang sudah disetujui.

## Hak akses dan pembaruan

Akses diberikan melalui role/module starter kit: Dashboard CMS, Konten, Halaman,
Card, Media, Menu, dan Komentar. Kategori mengikuti hak akses modulnya. Akun yang
dapat mengelola konten belum tentu dapat membuka pengaturan pengguna global.

Perbarui package melalui Composer, periksa `starter:sync cms --dry-run`, lalu
jalankan `starter:sync cms` di local. Periksa override view aktif melalui perintah
view bawaan bila aplikasi mempunyai penyesuaian struktur dashboard.

Pada tabel postingan Tabler, klik isi kolom Kategori (termasuk “Tanpa kategori”) untuk mengubah kategori postingan tersebut tanpa membuka form penuh. Pilihan baru disimpan hanya setelah menekan Simpan kategori.
