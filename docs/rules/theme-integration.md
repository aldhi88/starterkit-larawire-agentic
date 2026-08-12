# Integrasi Theme Baru

Rule ini berlaku ketika menambah theme baru, mengubah adapter theme, atau
mengaudit runtime theme yang sudah terpasang.

## Distribusi dan registrasi modular

- Core `aldhi88/starterkit-larawire-agentic` membawa hanya Tabler dan hanya file runtime yang benar-benar dimuat, plus atlas komponen terkurasi yang lisensinya boleh didistribusikan. Jangan menyimpan duplikasi bundle vendor penuh.
- Theme baru dibuat sebagai package Composer opsional. Menambah theme tidak boleh memperbesar download core bagi developer yang tidak memilihnya; developer memasang package theme yang diperlukan sebelum menjalankan installer.
- Service provider package theme memanggil `StarterThemeRegistry::register()` dengan `label`, absolute package `root`, relative `views`, `assets`, `docs`, class adapter `powergrid`, serta view layout `vertical` dan `horizontal`. Semua directory, class, dan layout harus ada; registry menolak path traversal dan definisi tidak lengkap.
- Installer menampilkan pilihan hanya bila lebih dari satu theme valid sudah terpasang. Satu theme dipilih otomatis. `starter:sync` mempublikasikan hanya asset theme aktif ke host; developer tidak menjalankan command publish terpisah.
- Package theme memiliki lisensi dan third-party notice sendiri. Source premium/non-redistributable tidak boleh disamarkan, dikomit, atau dipaketkan; theme seperti itu hanya boleh diintegrasikan di repository privat yang memiliki hak distribusi sesuai lisensinya.

Minimal registrasi pada service provider package theme:

```php
StarterThemeRegistry::register('theme-key', [
    'label' => 'Theme Name',
    'root' => dirname(__DIR__, 2),
    'views' => 'resources/views',
    'assets' => 'public/assets/theme-key',
    'docs' => 'docs/template/theme-key',
    'powergrid' => ThemePowerGrid::class,
    'layouts' => [
        'vertical' => 'starter.templates.layouts.navigation.vertical',
        'horizontal' => 'starter.templates.layouts.navigation.horizontal',
    ],
]);
```

Provider tersebut didaftarkan melalui Composer package discovery. Jangan
menambah key theme baru ke core `config/starter.php` dan jangan meminta user
menyalin source theme ke `vendor` atau project host.

## Kontrak yang sama, tampilan yang mandiri

- Kesamaan antar-theme hanya mencakup data, aksi, authorization, validasi,
  loading/empty/error state, makna aksesibilitas, dan kemampuan responsive.
- Setiap theme memilih sendiri komponen, struktur HTML, class, spacing,
  typography, warna, icon, dan pola interaksi dari bundle vendor theme itu.
- Theme lain tidak boleh menjadi sumber visual, fallback markup, atau target
  kemiripan. Setiap theme wajib menggunakan atlas dan komponen vendornya sendiri.
- Nama kebutuhan boleh sama—misalnya checkbox, switch, select, card, modal,
  tabel, dan pagination—tetapi representasinya wajib memakai komponen native
  vendor aktif. Improve diperbolehkan hanya dengan token dan bahasa desain
  vendor aktif.

## Evidence gate sebelum implementasi

Untuk setiap shell, halaman, atau komponen yang terlihat:

1. Tentukan kontrak produk yang harus tersedia tanpa membawa keputusan visual
   dari theme lama.
2. Cari komponen di atlas `docs/template/<theme>/template.md`, lalu buka satu sampai
   tiga HTML vendor yang paling dekat.
3. Catat pemetaan runtime di `docs/template/<theme>/runtime-map.md`: kebutuhan,
   file vendor, pola/class vendor, view runtime, dan state yang diuji.
4. Implementasikan view theme secara mandiri. Jangan mulai dengan menyalin view
   theme lain lalu mengganti CSS.
5. Setiap class visual baru yang bukan class vendor harus semantik, diawali nama
   theme, dan berada di asset adapter theme tersebut. `starter-*` hanya boleh
   menjadi hook kontrak lintas-theme yang sudah didokumentasikan; deklarasi
   visualnya tetap wajib dimiliki masing-masing theme dan boleh berbeda total.
   Class custom boleh menjembatani Livewire/Alpine, tetapi tidak boleh meniru
   komponen theme lain.

`data-starter-*`, event Livewire, data PHP, dan kontrak Alpine boleh dibagi.
Markup yang terlihat dan CSS vendor tidak boleh dibagi. `data-bs-*` hanya boleh
digunakan bila bundle vendor aktif memang menggunakannya; nama yang kebetulan
sama bukan bukti bahwa sebuah pola berasal dari theme lain.

Satu pengecualian visual lintas-theme adalah loader perpindahan halaman yang
dimiliki shared runtime sesuai `ui-ux.md`. Theme wajib meng-include komponen itu
tanpa menyalin markup atau memberi override visual. Loader action Livewire bukan
bagian dari pengecualian ini.

## Larangan compatibility skin

- Jangan membuat lapisan CSS yang mempertahankan markup theme lama dengan cara
  mendefinisikan ulang selector visual milik theme lama di theme baru.
- Jangan menyelesaikan perbedaan theme dengan kumpulan selector generik seperti
  `status`, `list-group`, `empty`, atau selector khusus vendor lain, kecuali
  selector tersebut terbukti ada pada contoh vendor aktif yang dipilih.
- Jangan memakai class utility yang tidak tersedia di CSS runtime. Class custom
  harus punya deklarasi yang dapat ditemukan dan alasan yang spesifik.
- Thin forwarder tanpa hierarki visual boleh identik antar-theme. Komponen yang
  merender UI wajib mempunyai implementasi theme sendiri.

## Matriks audit wajib

Audit theme belum selesai sampai semua kelompok berikut dipetakan dan diuji:

- shell vertical/horizontal, sidebar, header, account menu, sticky/overflow;
- auth, lock screen, error page, loader;
- page header, card/statistic, avatar, badge/status, empty state;
- button/link action dan state hover/focus/disabled/loading;
- input, textarea, select, file upload, validation, helper text;
- checkbox, radio, switch dalam state checked/unchecked/disabled;
- tabs, accordion, dropdown, alert, toast, modal dan destructive confirmation;
- PowerGrid: toolbar, search/filter, sort, selection, bulk action, horizontal
  scroll, per-page, record count, pagination atas dan bawah;
- responsive desktop 1280x768 dan viewport kecil yang relevan.

Verifikasi dilakukan pada halaman nyata dengan normal, kosong, error, disabled,
checked, dropdown/modal terbuka, dan data tabel lebar bila state tersebut
relevan. Theme baru tidak boleh dinyatakan selesai dengan daftar "nanti
diperbaiki" untuk komponen inti di atas.

## Audit residu lintas-theme

Sebelum selesai:

- cari signature theme lain pada view dan asset runtime theme aktif;
- bandingkan file yang terlihat dengan theme lain; kemiripan harus dijelaskan
  oleh kontrak produk, bukan salinan presentasi;
- pastikan adapter hanya memiliki selector vendor aktif atau selector semantik
  bernama theme;
- jalankan build/lint/test yang relevan dan browser check sesuai `testing.md`.

Jika audit menemukan compatibility skin, ubah markup ke pola vendor aktif lebih
dulu, lalu hapus selector kompatibilitas yang sudah tidak digunakan. Jangan
menutupi akar masalah dengan override CSS tambahan.
