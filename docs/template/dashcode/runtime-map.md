# Dashcode Runtime Component Map

Peta ini mencatat sumber visual runtime Dashcode. Kontrak data dan aksi dapat
sama dengan theme lain; struktur serta presentasinya tidak.

| Kebutuhan runtime | Sumber vendor | Pola Dashcode yang dipakai | Runtime utama | State verifikasi |
|---|---|---|---|---|
| Shell vertical/horizontal | `blank-page.html`, `assets/js/app.js` | `app-wrapper`, sidebar light tetap terbuka di desktop, horizontal menu native | `templates/layouts/*` | desktop, drawer mobile, sticky |
| Auth | `signin-one.html`, `signin-two.html` | split auth, card/form Dashcode | `auth/*`, `templates/layouts/auth.blade.php` | normal, error, loading |
| Loader perpindahan halaman | shared runtime | komponen universal, tidak diberi style Dashcode | `starter-shared::components.navigate-loader` | tampil saat navigasi, hilang setelah selesai |
| Navigasi akun | `blank-page.html`, `profile.html` | identity trigger dan dropdown Dashcode | `templates/layouts/profile-dropdown.blade.php` | open, keyboard, Livewire navigate |
| Card dan statistik | `card.html`, `basic-widgets.html` | card putih dan statistic widget Dashcode | settings dan log aktivitas | 1280x768, teks panjang |
| Button | `buttons.html` | solid/outline/light Dashcode, icon kiri | seluruh starter | hover, focus, disabled, loading |
| Form | `input-layout.html`, `select.html`, `file-input.html` | `form-label`, `form-control`, select dan helper Dashcode | user/role/profile/settings | valid, invalid, disabled |
| Profil akun | `profile.html`, `settings.html`, `input-layout.html` | overview identitas, summary ringkas, menu pengaturan, form dua kolom dan action footer | `profile/edit-my-profile.blade.php` | detail, keamanan, foto default, responsive |
| Checkbox/radio/switch | `checkbox.html`, `radio.html`, `switch.html` | control native Dashcode dengan label sejajar | role dan security | checked, unchecked, disabled |
| Tabs/section navigation | `tab-accordion.html`, `settings.html` | tab/pill Dashcode | settings dan profil | active, responsive |
| Accordion akses | `tab-accordion.html` | header, chevron dan panel Dashcode | `user-management/role-form.blade.php` | open/close satu dan semua app |
| Alert | `alert.html` | light semantic notice dengan icon/action | flash dan kredensial sementara | success, info, warning, danger, dismiss |
| Modal | `modal.html` | overlay, content, icon close, body/footer Dashcode | komponen modal dan detail | open, close, validation, destructive |
| Badge/status | `badges.html` | soft/pill badge Dashcode | role, user, log | status semantic dan teks panjang |
| Empty state | `placeholder.html`, `404.html` | icon panel, judul, penjelasan, recovery action | daftar/detail tanpa data | empty dan recovery action |
| Daftar ringkas | `profile.html`, `project.html` | stacked rows dengan divider Dashcode | detail role dan akses user | item sedikit/banyak, overflow |
| Tabel PowerGrid | `advance-table.html` | `dashcode-data-table`, `table-th`, `table-td`, toolbar dan pagination | seluruh `powergrid/*` | filter, sort, select, bulk, scroll-x, pagination atas/bawah |

Komponen runtime tambahan wajib ditambahkan ke peta ini sebelum theme dianggap
selesai. Gunakan `template.md` sebagai indeks pencarian, lalu periksa HTML vendor
yang dicantumkan di tabel ini.
