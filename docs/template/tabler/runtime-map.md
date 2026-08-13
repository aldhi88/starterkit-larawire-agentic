# Tabler Runtime Map

Map ini menghubungkan capability produk, reference Tabler terkurasi, dan view
runtime Starterkit. Detail per komponen/state tersedia pada
`component-manifest.json`.

| Capability | Reference Tabler | Runtime Starterkit |
|---|---|---|
| Shell vertical/horizontal dan navigation | `preview/pages/layout-vertical.html`, `preview/pages/layout-horizontal.html` | `resources/themes/tabler/views/starter/templates/layouts/` |
| Page header, card, statistic, avatar, status | `preview/pages/cards.html`, `preview/pages/profile.html` | profile, settings, user-management |
| Authentication dan lock screen | `preview/pages/sign-in.html` | `resources/themes/tabler/views/starter/auth/` |
| HTTP error dan empty state | `preview/pages/empty.html`, `preview/pages/error-404.html` | `resources/themes/tabler/views/starter/errors/` |
| Form, validation, filter, dan pilihan | `preview/pages/form-elements.html` | settings dan user-management forms |
| Tabs, accordion, dropdown | `preview/pages/tabs.html`, `preview/pages/dropdowns.html` | settings, role form, layout account menu |
| Alert, toast, modal, confirmation | `preview/pages/alerts.html`, `preview/pages/modals.html` | `resources/themes/tabler/views/starter/templates/components/` |
| Activity log | table, form/filter, dan feedback references | `resources/themes/tabler/views/starter/logs/` |
| PowerGrid lengkap | `preview/pages/datatables.html`, `preview/pages/pagination.html`, `preview/pages/form-elements.html` | `resources/themes/tabler/views/starter/powergrid/` dan toolbar per fitur |

Kedua layout wajib tersedia. Kontrak shared boleh memakai `data-starter-*`,
event Livewire, data PHP, dan state Alpine. Struktur visual, styling, adapter
JavaScript, dan PowerGrid tetap dimiliki Tabler.
