# Dashcode Template Discovery Atlas

> Atlas ini memetakan 74 HTML DashCode pada arsip owner dan intake lokal
> `theme-intake/dashcode/`. HTML/aset komersial tidak ikut package. Runtime
> Laravel berada di `resources/themes/dashcode/`; recipe minimum dipublikasikan
> ke host `public/assets/dashcode/` setelah lisensi dan hash diverifikasi.

Pemetaan komponen yang benar-benar dipakai runtime dan state verifikasinya ada
di [`runtime-map.md`](runtime-map.md). Atlas ini untuk menemukan kandidat;
runtime map adalah evidence implementasinya.

## Cara memakai atlas

1. Tentukan tujuan halaman, data, aksi utama, dan state yang perlu terlihat.
2. Cari kandidat dengan `rg -n "kata-kunci" docs/template/dashcode/template.md`.
3. Buka satu sampai tiga file terdekat di `theme-intake/dashcode/`.
4. Ambil hierarki, density, class, dan pola komponen Dashcode; jangan menyalin
   script customizer, CDN, atau dependency halaman yang tidak dibutuhkan.
5. Implementasikan di view theme Dashcode dan verifikasi pada browser.

## Mekanisme layout Dashcode

Dashcode tidak menyediakan file HTML terpisah untuk vertical dan horizontal.
Keduanya menggunakan shell yang sama. Theme Customizer asli mengubah class
`.horizontalMenu` pada `.app-wrapper` melalui JavaScript dan localStorage.

| Pilihan starter | Shell Dashcode | Navigasi desktop | Navigasi layar kecil |
|---|---|---|---|
| `STARTER_LAYOUT=vertical` | `.app-wrapper` | `.sidebar-wrapper` dan `.vertical-box` | sidebar drawer |
| `STARTER_LAYOUT=horizontal` | `.app-wrapper.horizontalMenu` | `.horizental-box` dan `.main-menu` | sidebar drawer |

Referensi utama: `blank-page.html`, `assets/js/app.js`, dan
`assets/css/app.css`. Runtime starter menetapkan class dari konfigurasi server,
bukan dari Theme Customizer atau localStorage.

## Jalur inspirasi utama

| Kebutuhan | Kandidat | Pola yang dibandingkan |
|---|---|---|
| Shell, sidebar, top menu | `blank-page.html`, `index.html` | header, sidebar, horizontal menu, content, footer |
| Dashboard dan KPI | `index.html`, `crm-dashboard.html`, `ecommerce-dashboard.html`, `banking-dashboard.html`, `project-dashboard.html` | statistic card, chart card, summary, activity |
| Tabel dan daftar | `basic-table.html`, `advance-table.html`, `invoice.html`, `project.html` | header tabel, density, status, aksi, pagination |
| Form | `input.html`, `input-layout.html`, `form-validation.html`, `select.html`, `date-picker.html` | label, input, validation, grouping, helper text |
| Modal dan feedback | `modal.html`, `alert.html`, `badges.html`, `progressbar.html` | konfirmasi, status, danger/success feedback |
| Profil dan pengaturan | `profile.html`, `settings.html` | identitas, tab/side navigation, section form |
| Auth dan lock screen | `signin-one.html`, `signin-two.html`, `signin-three.html`, `lock-screen-one.html` | split auth, compact auth, focus form |
| Error dan maintenance | `404.html`, `under-maintanance.html`, `comming-soon.html` | illustration, message, next action |

## Komponen dan sumber terdekat

| Komponen | File rujukan | Class/pola penting |
|---|---|---|
| App shell | `blank-page.html` | `app-wrapper`, `app-header`, `content-wrapper`, `site-footer` |
| Sidebar | `blank-page.html` | `sidebar-wrapper`, `logo-segment`, `sidebar-menu`, `navItem`, `sidebar-submenu` |
| Horizontal menu | `blank-page.html` | `horizontalMenu`, `horizental-box`, `main-menu`, `sub-menu` |
| Card | `card.html`, `basic-widgets.html` | `card`, `card-header`, `card-body`, `card-footer` |
| Button | `buttons.html` | `btn`, `btn-primary`, `btn-dark`, outline dan light variants |
| Alert | `alert.html` | `alert`, semantic color, icon, dismiss action |
| Badge/status | `badges.html` | `badge`, solid/light/outline variants |
| Modal | `modal.html` | `modal`, `modal-dialog`, `modal-content`, header/body/footer |
| Dropdown | `dropdown.html` | trigger, menu, item, placement |
| Tabs/accordion | `tab-accordion.html` | secondary navigation, active state, collapsed state |
| Pagination | `pagination.html` | previous/next, active page, disabled state |
| Input | `input.html`, `input-layout.html` | `form-label`, `form-control`, `fromGroup` |
| Validation | `form-validation.html` | invalid border, feedback, focus state |
| Checkbox/radio/switch | `checkbox.html`, `radio.html`, `switch.html` | selection state dan label |
| Select/date/file | `select.html`, `date-picker.html`, `file-input.html` | control height, dropdown, upload |
| Basic table | `basic-table.html` | `table-th`, `table-td`, `table-checkbox` |
| Advanced table | `advance-table.html` | search, action, bulk select, pagination |
| Avatar/profile | `profile.html`, `settings.html` | avatar, identity block, account settings |
| Empty/error | `404.html`, `placeholder.html` | illustration, explanation, recovery action |

## Inventaris halaman

### Dashboard dan operasional

- `index.html` — analytics dashboard.
- `banking-dashboard.html` — rekening, transaksi, kartu, dan statistik.
- `crm-dashboard.html` — customer, funnel, activity, dan ringkasan.
- `ecommerce-dashboard.html` — produk, order, sales, dan revenue.
- `project-dashboard.html` — project summary, progress, dan task.
- `basic-widgets.html`, `statistics-widgets.html` — kumpulan card statistik.
- `chat.html`, `email.html`, `kanban.html`, `calander.html`, `todo.html` — pola aplikasi operasional.
- `project.html`, `project-details.html` — daftar dan detail project.

### Data, chart, dan dokumen

- `basic-table.html`, `advance-table.html` — tabel sederhana dan tabel kaya interaksi.
- `apex-chart.html`, `chartjs.html` — card dan komposisi chart.
- `invoice.html`, `invoive-add.html`, `invoive-preview.html` — invoice list/form/preview.
- `map.html` — komposisi map.

### Form

- `input.html`, `input-group.html`, `input-layout.html`, `textarea.html`.
- `form-validation.html`, `form-repeater.html`, `wizard.html`.
- `input-mask.html`, `file-input.html`, `select.html`, `date-picker.html`.
- `checkbox.html`, `radio.html`, `switch.html`.

### Komponen UI

- `alert.html`, `badges.html`, `buttons.html`, `card.html`.
- `carousel.html`, `dropdown.html`, `image.html`, `modal.html`.
- `pagination.html`, `placeholder.html`, `progressbar.html`.
- `tab-accordion.html`, `tooltip-popover.html`, `typography.html`.
- `colors.html`, `icons.html`, `video.html`.

### Auth dan utility

- `signin-one.html`, `signin-two.html`, `signin-three.html`.
- `signup-one.html`, `signup-two.html`, `signup-three.html`.
- `forget-password-one.html`, `forget-password-two.html`, `forget-password-three.html`.
- `lock-screen-one.html`, `lock-screen-two.html`, `lock-screen-three.html`.
- `profile.html`, `settings.html`, `pricing.html`.
- `blog.html`, `blog-details.html`.
- `404.html`, `comming-soon.html`, `under-maintanance.html`, `blank-page.html`.

## Batas implementasi

- `advance-table.html` adalah sumber visual wajib untuk semua tabel PowerGrid Dashcode. Pertahankan pola `dashcode-data-table`, `table-th`, `table-td`, checkbox, filter/search, action dropdown, dan pagination Dashcode sambil membiarkan PowerGrid menangani data serta state server-side.
- Kesamaan dengan theme lain hanya mencakup data, aksi, authorization, state, accessibility, dan responsive capability. Jangan menyalin markup, class, atau tampilan Tabler/Bootstrap ke runtime Dashcode.
- Jangan memuat Google Fonts, Iconify API, unpkg, atau CDN lain di runtime.
- Jangan menjalankan `settings.js` atau Theme Customizer pada aplikasi.
- Jangan memuat `rt-plugins.js` secara global; gunakan Alpine/shared runtime
  untuk interaksi kecil dan dependency lokal per halaman untuk kebutuhan khusus.
- Pertahankan atribusi dan bundle vendor asli hanya di intake lokal/arsip owner;
  jangan commit atau memasukkannya ke Composer.
- PowerGrid memakai adapter Tailwind khusus Dashcode, bukan adapter Bootstrap
  milik Tabler.
