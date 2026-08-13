# Atlas Komponen Tabler

Atlas ini hanya menjadi sumber visual untuk theme Tabler. Theme lain memakai
capability produk yang sama, tetapi wajib memilih markup, komponen, class, dan
presentasi dari vendor theme-nya sendiri.

Distribusi demo Tabler tidak disimpan di package. Seluruh 395 file HTML yang
diaudit tetap tercatat beserta path, hash, dan sinyal pencariannya di
`source-index.json`. File mentah tersedia hanya pada arsip owner dan intake
lokal `theme-intake/tabler/`. Pemetaan capability berada di
`component-manifest.json`; recipe aset runtime minimum berada di
`asset-manifest.json`.

## Cara memakai atlas

1. Rumuskan kebutuhan data, aksi, state, dan responsive halaman.
2. Cari capability di `component-manifest.json` atau tabel di bawah.
3. Buka hanya satu sampai tiga path HTML terdekat dari `theme-intake/tabler/`.
4. Komposisikan pola Tabler tersebut ke Blade pemiliknya; jangan menyalin satu
   halaman penuh dan jangan memakai presentasi theme lain sebagai fallback.
5. Perbarui `runtime-map.md` dan manifest bila capability/runtime berubah.

## Jalur reference utama

| Reference | Capability utama |
|---|---|
| `preview/pages/layout-vertical.html`, `preview/pages/layout-horizontal.html` | shell, navigation, App switcher, account menu |
| `preview/pages/cards.html`, `preview/pages/profile.html` | page header, card, statistic, avatar, badge/status |
| `preview/pages/form-elements.html` | input, textarea, select, switch, search, dan filter |
| `preview/pages/buttons.html`, `preview/pages/dropdowns.html` | button, dropdown, selection, dan action |
| `preview/pages/datatables.html`, `preview/pages/pagination.html` | table, empty state, per-page, count, pagination |
| `preview/pages/alerts.html`, `preview/pages/modals.html` | alert, toast, detail modal, destructive confirmation |
| `preview/pages/tabs.html`, `preview/pages/empty.html` | tabs, navigation, empty/error state |
| `preview/pages/sign-in.html` | login, confirm-password, dan lock-screen language |

## Batasan

- Reference HTML eksternal adalah bukti komponen, bukan source runtime dan bukan router
  desain otomatis.
- Jangan menambah CDN, dependency, atau bundle demo saat satu komponen sudah
  tersedia pada aset runtime.
- Jangan memasukkan upstream demo ke package. Untuk audit ulang, gunakan
  `theme-intake/tabler/`, bangun ulang `source-index.json`, lalu pertahankan
  intake sebagai folder lokal yang di-ignore.
- Lisensi reference Tabler dipertahankan pada `LICENSE`.
