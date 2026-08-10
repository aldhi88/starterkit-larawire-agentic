# Starterkit Larawire Private

[English](README.md) · **Bahasa Indonesia**

Starterkit Larawire dibangun menggunakan dan mengapresiasi karya
[Laravel](https://laravel.com/), [Livewire](https://livewire.laravel.com/),
[Livewire PowerGrid](https://livewire-powergrid.com/),
[Tabler](https://tabler.io/), [Laravel Lang](https://laravel-lang.com/),
[Scramble](https://scramble.dedoc.co/), dan [Pest](https://pestphp.com/).

> Versi minimum Laravel: **13.8**. Versi Laravel setelahnya dapat digunakan
> selama struktur aplikasi fresh masih kompatibel dengan release Starterkit
> Larawire yang terpasang.

## A. Tentang

### Apa itu Starterkit Larawire?

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
php artisan starter:make-app sales --name="Sales"
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

Package publik saat ini menyediakan theme **Tabler** yang aman didistribusikan
dengan dua layout:

```dotenv
STARTER_THEME=tabler
STARTER_LAYOUT=vertical
```

`STARTER_LAYOUT` dapat diisi `vertical` atau `horizontal`. Registrasi theme
bersifat modular. Theme baru membawa view, asset, JavaScript handler, adapter
PowerGrid, referensi komponen, dan kedua layout miliknya sendiri. Tampilan satu
theme tidak dipaksakan mengikuti theme lain.

### Mode AGENTS AI

Installer menambahkan blok terkelola ke `AGENTS.md` project Laravel. Blok ini
mengarahkan agent AI ke aturan arsitektur, keamanan, testing, UI, theme, dan
development fitur di dalam Composer package. Fitur aplikasi tetap dibuat pada
project Laravel; `vendor/aldhi88/starterkit-larawire` bersifat read-only.

## B. Instalasi

### Instalasi local pertama kali

Persiapkan:

1. project Laravel fresh;
2. koneksi database yang benar di `.env`; dan
3. domain lokal utama pada `APP_URL`.

Dari root project Laravel:

```bash
composer require aldhi88/starterkit-larawire
```

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
php artisan starterkit:install --company="Nama Perusahaan"
```

Installer menjelaskan perubahan destruktif, mengonfirmasi `APP_URL`, menguji dan
mengonfirmasi database, lalu memeriksa source Laravel. Jika source tidak lagi
fresh, instalasi berhenti sebelum mengubah file atau data. Jika source fresh
sudah menjalankan migration, installer meminta satu konfirmasi tambahan.

Instalasi menjalankan `migrate:fresh`; seluruh tabel dan data pada database yang
dipilih akan dihapus.

### Menggunakan database kosong baru setelah starterkit terpasang

Jangan jalankan installer fresh lagi. Ubah `.env`, kemudian bangun ulang data
starter menggunakan:

```bash
php artisan starter:setup --company="Nama Perusahaan"
```

### Sinkronisasi local

Jalankan setelah mengubah konfigurasi App, route, module, menu, migration, atau
pilihan theme/layout:

```bash
php artisan starter:sync
```

Sync juga mempublikasikan asset package, PowerGrid, dan Livewire. Tidak ada
command publish asset terpisah.

### Update starterkit di local

```bash
composer update aldhi88/starterkit-larawire
php artisan starter:sync
```

Periksa dan commit `composer.json` serta `composer.lock` pada repository
aplikasi Laravel.

### Deployment production pertama kali

```bash
git clone <repository-laravel> <folder-project>
cd <folder-project>
cp .env.example .env
# atur APP_URL, database, dan secret production
composer install --no-dev --optimize-autoloader
php artisan starter:setup --company="Nama Perusahaan"
```

Arahkan domain utama dan subdomain App ke `<folder-project>/public`.

### Update production berkala

```bash
git pull --ff-only
composer install --no-dev --optimize-autoloader
php artisan starter:sync
```

Production memakai versi package yang terkunci di `composer.lock` project
Laravel. Installer destruktif tidak dijalankan di production.

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

README ini adalah panduan ringkas GitHub. Aturan dan arsitektur yang lebih rinci
tersedia di `docs/`. Website dokumentasi resmi direncanakan dan URL publiknya
akan ditambahkan setelah siap.
