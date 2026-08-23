# Starterkit Larawire Agentic

**Bahasa Indonesia** · [English](README.en.md)

[Website resmi](https://starterkit-larawire.altekno.id/) ·
[Dokumentasi](https://starterkit-larawire.altekno.id/docs) ·
[Packagist](https://packagist.org/packages/aldhi88/starterkit-larawire-agentic)

Starterkit Larawire dibangun menggunakan dan mengapresiasi karya
[Laravel](https://laravel.com/), [Livewire](https://livewire.laravel.com/),
[Livewire PowerGrid](https://livewire-powergrid.com/),
[Tabler](https://tabler.io/),
[Laravel Lang](https://laravel-lang.com/),
[Scramble](https://scramble.dedoc.co/), dan [Pest](https://pestphp.com/).

> Versi minimum Laravel: **13.8**. Constraint Composer pada setiap release
> menentukan versi yang sudah diuji; dukungan major Laravel baru ditambahkan
> setelah lolos compatibility test.

## A. Tentang

### Apa itu Starterkit Larawire Agentic?

Bayangkan Starterkit Larawire seperti Laravel authentication starter kit dengan
fondasi yang lebih lengkap. Package ini menyiapkan project aplikasi internal
perusahaan baru dengan fitur yang biasanya dibuat berulang kali:

- login menggunakan username atau email;
- Superuser, user, role, dan authorization berdasarkan module;
- profil perusahaan dan pengaturan keamanan;
- log aktivitas;
- pemisahan App dan subdomain;
- sinkronisasi route, module, dan menu dari source code;
- tabel menggunakan Livewire PowerGrid;
- API gateway dan dokumentasi OpenAPI opsional;
- layout vertical dan horizontal; serta
- kontrak development AGENTS AI.

Satu instalasi digunakan untuk satu perusahaan/client, bukan SaaS multi-tenant.

### Cara kerja App dan subdomain

Misalnya kamu membuat ERP internal dengan bagian Sales, HR, Gudang, dan
Karyawan. Setiap bagian dapat dipisahkan menjadi App:

| App | Contoh alamat | Tanggung jawab |
|---|---|---|
| Sales | `sales.company.test` | Prospek, penawaran, dan penjualan |
| HR | `hr.company.test` | Rekrutmen dan proses HR |
| Gudang | `gudang.company.test` | Stok dan pergerakan barang |
| Karyawan | `karyawan.company.test` | Layanan mandiri karyawan |

Semua App tetap menggunakan satu project Laravel, database, profil perusahaan,
dan sesi login. Subdomain hanya memisahkan area kerja, bukan membuat instalasi
Laravel yang berbeda.

Di production, arahkan domain utama dan semua subdomain App ke directory
Laravel `public/` yang sama. Wildcard DNS seperti `*.company.com` dapat dipakai
jika didukung hosting.

### File yang biasa diedit developer

Setiap App memiliki konfigurasi dan source fiturnya sendiri:

```text
config/apps/<app>.php                    Definisi App, module, dan menu
routes/apps/<app>.php                   Route web pada subdomain App
routes/apps/<app>.api.php               Route API App opsional
app/Livewire/Apps/<App>/                Komponen Livewire
resources/views/apps/<app>/             View App
database/migrations/apps/<app>/         Migration App
tests/Feature/Apps/<App>/               Test App
```

Buat struktur awal menggunakan:

```bash
php artisan starter:app
```

File route menentukan halaman yang tersedia. `config/apps/<app>.php`
menghubungkan route dengan module dan menu. Menu hanya navigasi; module adalah
batas authorization yang sebenarnya.

### Mengatur akses role secara dinamis

Administrator memberikan module kepada role melalui halaman Pengaturan. Satu
role dapat membuka satu atau beberapa App, dan setiap App mempunyai halaman
awal yang dipilih. Superuser selalu memiliki akses penuh dan dilindungi sebagai
akun sistem tersembunyi.

Setelah route, module, atau menu berubah, sinkronkan metadata source code:

```bash
php artisan starter:sync
```

### Theme dan layout

Saat ini Starterkit menyediakan Tabler dengan layout `vertical` dan `horizontal`:

| Theme | Lisensi source | Catatan |
|---|---|---|
| Tabler | MIT | Bebas digunakan sesuai lisensi Tabler |

Pilihan disimpan di environment:

```dotenv
STARTER_THEME=tabler
STARTER_LAYOUT=vertical
```

Wizard `starter:install` tetap meminta pilihan theme dan layout. Untuk saat ini
pilihan theme hanya Tabler sehingga mekanismenya siap menerima theme open-source
lain tanpa mengubah alur instalasi. Aset runtime minimum diunduh otomatis dari
arsip GitHub yang dipin, diperiksa ukuran dan checksum-nya, lalu dipublikasikan
ke `public/assets/tabler/`. Tidak ada download atau copy template manual.
Archive dikelola terpisah di
[`starterkit-larawire-agentic-template`](https://github.com/aldhi88/starterkit-larawire-agentic-template).

Hasil runtime `public/assets/<theme>/` wajib di-commit bersama project Laravel.
Production menggunakan hasil tersebut dan tidak mengunduh arsip theme.

Untuk menambah theme, taruh distribusi HTML/aset vendor di `theme-intake/<theme-key>/`, lalu instruksikan agent AI:

```text
Integrasikan theme <theme-key> dari theme-intake/<theme-key> sampai siap dipilih installer.
```

Agent wajib menangani audit lisensi, indexing seluruh HTML, filtering aset, seluruh halaman/komponen starter, kedua layout, PowerGrid, dan pengujian. Source demo penuh serta aset vendor tetap hanya di intake lokal/arsip pemilik; indeks, recipe, implementasi, dan peta referensinya masuk ke core package.

### Mode AGENTS AI

Installer menambahkan blok terkelola ke `AGENTS.md` project Laravel. Blok ini
mengarahkan agent AI ke aturan arsitektur, keamanan, testing, UI, theme, dan
development fitur di dalam Composer package. Fitur aplikasi tetap dibuat pada
project Laravel; `vendor/aldhi88/starterkit-larawire-agentic` bersifat read-only.

## B. Instalasi

### Instalasi local pertama kali

Persiapkan:

1. project Laravel fresh;
2. koneksi database yang benar di `.env`; dan
3. domain lokal utama pada `APP_URL`.

Dari root project Laravel:

```bash
composer require aldhi88/starterkit-larawire-agentic
```

Tidak perlu mengunduh atau menyalin template secara manual. Installer akan
mengunduh aset Tabler dari GitHub setelah theme dipilih dan memvalidasinya
sebelum source atau database project diubah.

Contoh `.env`:

```dotenv
APP_URL=http://company.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=company_erp
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan installer:

```bash
php artisan starter:install
```

Wizard meminta theme (saat ini hanya Tabler), layout vertical/horizontal, lalu lima
identitas berikut:

| Pertanyaan | Boleh memakai spasi? | Contoh input |
|---|---:|---|
| Nama perusahaan/client | Ya | `PT Maju Bersama` |
| Nama App pertama | Ya | `Human Resources` |
| Domain/subdomain App | Tidak | `hr` |
| Email Superuser | Tidak | `admin@company.test` |
| Password Superuser | Tidak | dimasukkan melalui prompt tersembunyi |

Untuk domain App, masukkan hanya bagian subdomain tanpa titik dan tanpa domain
utama. Contoh `hr` akan menjadi `hr.company.test` jika `APP_URL` menggunakan
`company.test`.

Installer menjelaskan perubahan destruktif, mengonfirmasi `APP_URL`, menjalankan
wizard identitas, mengonfirmasi koneksi database, lalu memeriksa source Laravel.
Jika database yang dikonfirmasi belum ada, installer membuatnya otomatis selama
akun database memiliki izin `CREATE DATABASE`. SQLite juga dibuat otomatis jika
file database belum ada. Jika source tidak lagi fresh, instalasi berhenti sebelum
mengubah file atau data. Jika source fresh sudah menjalankan migration, installer
meminta satu konfirmasi tambahan.

Username Superuser selalu `superuser`. Password hanya dibaca melalui prompt
tersembunyi, langsung disimpan sebagai hash, dan tidak pernah ditulis ke `.env`,
argumen command, log, atau file sementara.

Password local boleh sederhana agar development praktis, tetapi tetap wajib
diisi dan dikonfirmasi. Saat `starter:deploy` perlu membuat Superuser pada
database production kosong, password wajib minimal 10 karakter serta mengandung
huruf besar, huruf kecil, dan angka.

Installer otomatis menjalankan validasi keamanan internal dan seluruh test
aplikasi sebelum me-reset database target. Tidak ada command verifikasi tambahan
yang perlu dijalankan manual. Jika verifikasi gagal, instalasi dihentikan, perubahan file
dikembalikan, dan database target belum disentuh. Jika kegagalan terjadi setelah
database di-reset, installer mengembalikan kondisi database kosong atau struktur
migration fresh bawaan Laravel.

Instalasi menjalankan `migrate:fresh`; seluruh tabel dan data pada database yang
dipilih akan dihapus.

### Reset dan instal ulang di local

Gunakan hanya jika seluruh aplikasi lama memang ingin dibuang dan dibuat ulang:

```bash
php artisan starter:reset
```

Command ini menampilkan peringatan keras, lalu menjalankan wizard instalasi yang
sama. Jika disetujui, seluruh database, source App pada boundary `Apps`, asset
App, upload logo/foto starterkit, role, user, pengaturan, menu, dan log aktivitas
lama akan dihapus. Jangan gunakan command ini untuk update rutin atau production.

### Menggunakan database kosong baru setelah starterkit terpasang

Jangan jalankan installer fresh lagi. Ubah `.env`, kemudian bangun ulang data
starter menggunakan:

```bash
php artisan starter:sync
```

Jika database baru masih kosong, sync menjalankan migration lalu meminta email
dan password Superuser baru melalui prompt tersembunyi.

### Sinkronisasi local

Jalankan setelah mengubah konfigurasi App, route, module, menu, migration, atau
pilihan theme/layout:

```bash
php artisan starter:sync
```

Sync juga memverifikasi recipe theme dan mempublikasikan asset package,
PowerGrid, dan Livewire. Tidak ada command publish asset terpisah. Commit hasil
`public/assets/<theme>/`. Jika cache aset local belum tersedia, sync dapat
mengunduh ulang arsip GitHub yang terverifikasi.

### Update starterkit di local

```bash
composer update aldhi88/starterkit-larawire-agentic
php artisan starter:sync
```

Periksa dan commit `composer.json` serta `composer.lock` pada repository
aplikasi Laravel.

### Deployment production pertama kali

```bash
git clone <repository-laravel> <folder-project>
cd <folder-project>
cp .env.example .env
# atur APP_URL, database, secret production, serta pertahankan STARTER_THEME dan STARTER_LAYOUT dari local
composer install --no-dev --optimize-autoloader
php artisan starter:deploy
```

Arahkan domain utama dan subdomain App ke `<folder-project>/public`.
`STARTER_THEME` dan `STARTER_LAYOUT` wajib sama dengan pilihan di local. Kedua
nilai ini tetap berada di `.env` local dan production agar tidak membutuhkan
file konfigurasi tambahan. Production tidak mengunduh arsip GitHub atau
menyiapkan `theme-intake/`: server memakai aset runtime theme terpilih yang
sudah dihasilkan oleh `starter:sync` dan di-commit dari local ke
`public/assets/<theme>/`.

### Update production berkala

```bash
git pull --ff-only
composer install --no-dev --optimize-autoloader
php artisan starter:deploy
```

Production memakai versi package yang terkunci di `composer.lock` project
Laravel. `starter:deploy` khusus production dan melakukan preflight lengkap:
environment production, debug mati, HTTPS, cookie aman, domain, theme/layout
eksplisit, aset runtime theme dari repository, extension, directory runtime,
koneksi database, migration, registry App, dan cache.
Pada database production pertama yang masih kosong, command meminta kredensial
Superuser melalui prompt aman. Jika preflight gagal, deployment berhenti sebelum
mutation. `starter:sync` dan `starter:reset` ditolak di production.

### Command Starterkit

Sebelum instalasi tersedia `starter:install`. Setelah instalasi berhasil:

| Command | Kegunaan |
|---|---|
| `starter:reset` | Menghapus instalasi lama dan mengulang wizard; hanya local |
| `starter:sync` | Menyelaraskan source developer ke database local |
| `starter:app` | Membuat App/subdomain baru melalui wizard |
| `starter:deploy` | Deploy production dengan preflight lengkap |

## C. Gateway API Opsional

Gateway API nonaktif secara default. Aktifkan melalui `.env`:

```dotenv
STARTER_API_ENABLED=true
```

Kemudian jalankan:

```bash
php artisan starter:sync
```

Route API App berada di `routes/apps/<app>.api.php` dan tersedia pada
`api.<APP_DOMAIN>/<app>` tanpa tambahan prefix `/api`. Setiap endpoint tetap
wajib memiliki authentication, authorization, validation, dan rate limit yang
jelas. Dokumentasi API production hanya dapat diakses Superuser.

## Dokumentasi

README ini adalah panduan ringkas GitHub. Panduan instalasi, arsitektur App,
authorization, workflow local, Agentic AI, deployment production, Gateway API,
dan troubleshooting tersedia di
[website dokumentasi resmi](https://starterkit-larawire.altekno.id/docs).
Aturan teknis untuk agent dan contributor tetap tersedia di `docs/` package.

## Lisensi

Source tersedia untuk instalasi, modifikasi, dan deployment aplikasi internal
sesuai [LICENSE](LICENSE). Menjual, melisensikan ulang, atau mempublikasikan
ulang package/starter yang bersaing tidak diizinkan. Komponen pihak ketiga tetap
mengikuti lisensinya masing-masing; lihat
[THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).
