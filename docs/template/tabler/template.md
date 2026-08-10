# Template Discovery Atlas

> Atlas ini hanya menjadi sumber visual untuk theme Tabler. Theme lain boleh
> mempertahankan capability yang sama, tetapi tidak boleh menyalin markup,
> class, atau tampilan Tabler sebagai presentation base.

> Inventaris ini memetakan 402 HTML template UI yang tersedia di `docs/template` pada 28 Juli 2026. Ini adalah atlas penemuan, bukan router kaku atau keputusan desain otomatis.

## Tujuan

Gunakan atlas ini untuk menemukan beberapa contoh yang mungkin relevan, lalu baca markup sumber yang benar-benar akan dipakai. Nama file dan sinyal konten membantu pencarian, tetapi tidak membatasi penggunaan: tabel dapat ditemukan pada invoice, daftar pengguna, tugas, atau halaman operasional lain; pola yang baik boleh dikomposisikan selama tetap konsisten dengan template UI aktif dan kebutuhan halaman.

Jangan membaca keseluruhan dokumen ini atau seluruh folder template. Cari dahulu, bandingkan kandidat, lalu buka hanya satu sampai tiga HTML sumber terdekat.

## Protokol eksplorasi hemat token

1. Rumuskan konteks halaman: tujuan user, jenis/volume data, status, aksi utama, dan interaksi.
2. Cari atlas berdasarkan kombinasi konteks atau komponen, bukan satu nama komponen saja. Contoh: `rg -n "invoice|table|status" docs/template/template.md` atau `rg -n "activity|timeline|card" docs/template/template.md`.
3. Pilih tiga sampai lima kandidat dari jalur inspirasi atau inventaris; utamakan halaman yang memiliki konteks serupa, lalu cari alternatif lintas konteks.
4. Buka satu sampai tiga HTML sumber dan cari markup yang dibutuhkan dengan `rg`/pembacaan potongan. Bandingkan hierarki informasi, kepadatan, aksi, state, dan responsivitasnya.
5. Komposisikan pola yang paling tepat memakai komponen starter yang relevan dan markup template UI aktif. Jangan menyalin satu halaman penuh atau memaksa satu contoh menjadi solusi semua kasus.
6. Verifikasi browser pada state kosong, sedikit, dan banyak data serta ukuran desktop/mobile yang relevan.

Sinyal pada inventaris adalah hasil pembacaan markup existing untuk mempercepat pencarian; sinyal bukan label fitur wajib dan dapat saling tumpang tindih.

## Jalur inspirasi lintas konteks

| Kebutuhan yang sedang dieksplorasi | Kandidat untuk dibandingkan | Fokus perbandingan |
|---|---|---|
| Daftar operasional padat | `tabler-components/preview/pages/users.html`, `tabler-components/preview/pages/tasks.html`, `tabler-components/preview/pages/job-listing.html`, `tabler-components/shared/includes/ui/advanced-table.html`, `tabler-components/shared/includes/cards/table-users.html` | Prioritas kolom, filter, status, aksi baris, dan kepadatan. |
| Tabel yang muncul di konteks non-tabel | `tabler-components/shared/includes/cards/invoices.html`, `tabler-components/shared/includes/cards/company-employees.html`, `tabler-components/shared/includes/cards/store-list.html`, `tabler-components/shared/includes/cards/tasks.html` | Cara menyisipkan tabel sebagai bagian dashboard/detail tanpa kehilangan keterbacaan. |
| Ringkasan dashboard dan keputusan cepat | `layout-and-cards.html`, `tabler-components/preview/pages/cards.html`, `tabler-components/preview/pages/widgets.html`, `tabler-components/shared/includes/cards/small-stats.html`, `tabler-components/shared/includes/cards/project-summary.html`, `tabler-components/shared/includes/cards/order-statistics.html` | Hierarki KPI, ringkasan, aktivitas, dan data pendukung. |
| Detail entitas, profil, atau riwayat | `tabler-components/preview/pages/profile.html`, `tabler-components/shared/includes/cards/profile.html`, `tabler-components/shared/includes/cards/profile-timeline.html`, `tabler-components/shared/includes/cards/invoice.html` | Identitas utama, key-value, status, tindakan, dan kronologi. |
| Form dan alur input | `forms-and-filters.html`, `tabler-components/preview/pages/form-layout.html`, `tabler-components/preview/pages/form-elements.html`, `tabler-components/shared/includes/cards/form/layout.html`, `tabler-components/shared/includes/forms/form-elements-1.html` | Pengelompokan field, kolom desktop, validasi, helper text, dan urutan input. |
| Feedback, konfirmasi, dan aksi berisiko | `feedback-and-modals.html`, `tabler-components/preview/pages/modals.html`, `tabler-components/preview/pages/toasts.html`, `tabler-components/shared/includes/parts/modals/danger.html`, `tabler-components/shared/includes/parts/modals/success.html` | Kejelasan dampak, CTA, pembatalan aman, dan feedback setelah aksi. |
| Empty state, error, dan state sekunder | `tabs-and-empty.html`, `tabler-components/preview/pages/empty.html`, `tabler-components/preview/pages/error-404.html`, `tabler-components/preview/pages/error-500.html`, `tabler-components/shared/includes/ui/empty.html` | Penjelasan kondisi, aksi lanjut, dan konsistensi layout. |
| Log, monitoring, dan aktivitas | `tabler-components/preview/pages/activity.html`, `tabler-components/preview/pages/logs.html`, `tabler-components/shared/includes/cards/activity.html`, `tabler-components/shared/includes/ui/timeline.html` | Urutan waktu, pelaku, status, metadata, dan kemampuan pemindaian. |
| Login, akun, dan akses | `auth.html`, `tabler-components/preview/pages/sign-in.html`, `tabler-components/preview/pages/sign-in-cover.html`, `tabler-components/shared/includes/cards/sign-in.html`, `tabler-components/shared/includes/cards/auth-lock.html` | Fokus tunggal, keamanan, bantuan, dan minim distraksi. |
| Setting, layout, dan navigasi | `tabler-components/preview/pages/settings.html`, `tabler-components/preview/pages/settings-plan.html`, `tabler-components/shared/includes/settings.html`, `tabler-components/shared/includes/layout/` | Navigasi sekunder, pembagian section, dan aksi simpan yang jelas. |

## Batasan keputusan desain

- Jangan menyimpulkan desain hanya dari nama file; buka sumber HTML kandidat sebelum menerapkan markup.
- Jangan membuat komponen visual baru bila template UI aktif atau komponen starter sudah menyediakan padanan yang dapat dikomposisikan.
- Jangan memilih card/table/form hanya karena jalur atlas menyebutkannya. Pilih berdasarkan kebutuhan membandingkan data, jumlah data, tindakan, dan prioritas informasi.
- Jangan memasukkan CDN, jQuery, atau library baru melalui eksperimen UI. Tetap ikuti rule asset, Alpine, Livewire, dan dependency pada `docs/rules/ui-ux.md`.
- Jangan membaca seluruh atlas atau semua source HTML untuk satu halaman. Pencarian terarah dan pembacaan selektif adalah mekanisme hemat token.

## Inventaris lengkap

Kolom `Sinyal markup` membantu pencarian awal. Setelah menemukan kandidat, buka file sumbernya; path dan sinyal bukan pengganti inspeksi markup.

| File | Sumber | Sinyal markup |
|---|---|---|
| `auth.html` | project reference | form, card, layout/navigation, auth |
| `feedback-and-modals.html` | project reference | card, modal, feedback, status, layout/navigation |
| `forms-and-filters.html` | project reference | form, card, layout/navigation |
| `index.html` | project reference | card, feedback, pagination, layout/navigation |
| `layout-and-cards.html` | project reference | card, status, avatar, layout/navigation |
| `tabler-components/preview/pages/2-step-verification-code.html` | preview page | form, card, feedback |
| `tabler-components/preview/pages/2-step-verification.html` | preview page | form, card |
| `tabler-components/preview/pages/accordion.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/activity.html` | preview page | card, timeline/activity, layout/navigation |
| `tabler-components/preview/pages/alerts.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/auth-lock.html` | preview page | auth |
| `tabler-components/preview/pages/avatars.html` | preview page | card, avatar, layout/navigation |
| `tabler-components/preview/pages/badges.html` | preview page | card, status, dropdown, layout/navigation |
| `tabler-components/preview/pages/blank.html` | preview page | feedback |
| `tabler-components/preview/pages/buttons.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/card-actions.html` | preview page | card, dropdown, avatar, layout/navigation |
| `tabler-components/preview/pages/cards-masonry.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/cards.html` | preview page | card, feedback, status, avatar, progress, layout/navigation |
| `tabler-components/preview/pages/carousel.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/changelog.html` | preview page | layout/content |
| `tabler-components/preview/pages/charts.html` | preview page | card, dropdown, chart, timeline/activity, layout/navigation |
| `tabler-components/preview/pages/chat.html` | preview page | form, card, tabs, avatar, layout/navigation |
| `tabler-components/preview/pages/colorpicker.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/colors.html` | preview page | form, card, avatar, layout/navigation |
| `tabler-components/preview/pages/cookie-banner.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/datagrid.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/datatables.html` | preview page | table, card, progress, layout/navigation |
| `tabler-components/preview/pages/docs/index.html` | preview page | layout/content |
| `tabler-components/preview/pages/dropdowns.html` | preview page | status, dropdown, layout/navigation |
| `tabler-components/preview/pages/dropzone.html` | preview page | card, upload/editor, layout/navigation |
| `tabler-components/preview/pages/emails.html` | preview page | card, modal, layout/navigation |
| `tabler-components/preview/pages/empty.html` | preview page | feedback, layout/navigation |
| `tabler-components/preview/pages/error-404.html` | preview page | layout/content |
| `tabler-components/preview/pages/error-500.html` | preview page | layout/content |
| `tabler-components/preview/pages/error-maintenance.html` | preview page | layout/content |
| `tabler-components/preview/pages/faq.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/flags.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/forgot-password.html` | preview page | auth |
| `tabler-components/preview/pages/form-elements.html` | preview page | table, form, card, feedback, avatar, invoice/payment, layout/navigation |
| `tabler-components/preview/pages/form-layout.html` | preview page | form, card, layout/navigation |
| `tabler-components/preview/pages/fullcalendar.html` | preview page | card, calendar, layout/navigation |
| `tabler-components/preview/pages/gallery.html` | preview page | card, pagination, layout/navigation |
| `tabler-components/preview/pages/icons.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/illustrations.html` | preview page | form, card, layout/navigation |
| `tabler-components/preview/pages/index.html` | preview page | layout/content |
| `tabler-components/preview/pages/inline-player.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/invoice.html` | preview page | invoice/payment, layout/navigation |
| `tabler-components/preview/pages/job-listing.html` | preview page | form, card, list, status, avatar, layout/navigation |
| `tabler-components/preview/pages/layout-boxed.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/layout-combo.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/layout-condensed.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/layout-fluid-vertical.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/layout-fluid.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/layout-horizontal.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/layout-navbar-dark.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/layout-navbar-overlap.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/layout-navbar-sticky.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/layout-rtl.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/layout-vertical-right.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/layout-vertical-transparent.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/layout-vertical.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/license.html` | preview page | card, list, layout/navigation |
| `tabler-components/preview/pages/lightbox.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/lists.html` | preview page | card, list, layout/navigation |
| `tabler-components/preview/pages/logs.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/map-fullsize.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/maps-vector.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/maps.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/marketing/about.html` | preview page | layout/content |
| `tabler-components/preview/pages/marketing/hero.html` | preview page | layout/content |
| `tabler-components/preview/pages/marketing/index.html` | preview page | layout/content |
| `tabler-components/preview/pages/marketing/pricing.html` | preview page | layout/content |
| `tabler-components/preview/pages/marketing/real-estate.html` | preview page | form, card |
| `tabler-components/preview/pages/marketing/testimonials.html` | preview page | layout/content |
| `tabler-components/preview/pages/marketing/text.html` | preview page | feedback |
| `tabler-components/preview/pages/modals.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/music.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/navigation.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/offcanvas.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/page-loader.html` | preview page | progress, layout/navigation |
| `tabler-components/preview/pages/pagination.html` | preview page | card, pagination, layout/navigation |
| `tabler-components/preview/pages/payment-providers.html` | preview page | card, invoice/payment, layout/navigation |
| `tabler-components/preview/pages/photogrid.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/placeholder.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/pricing-table.html` | preview page | table, card, invoice/payment, layout/navigation |
| `tabler-components/preview/pages/pricing.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/profile.html` | preview page | card, timeline/activity, layout/navigation |
| `tabler-components/preview/pages/scroll-spy.html` | preview page | card, tabs, layout/navigation |
| `tabler-components/preview/pages/search-results.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/segmented-control.html` | preview page | card, calendar, layout/navigation |
| `tabler-components/preview/pages/settings-plan.html` | preview page | card |
| `tabler-components/preview/pages/settings.html` | preview page | form, card, avatar |
| `tabler-components/preview/pages/sign-in-cover.html` | preview page | layout/navigation, auth |
| `tabler-components/preview/pages/sign-in-illustration.html` | preview page | layout/navigation, auth |
| `tabler-components/preview/pages/sign-in-link.html` | preview page | auth |
| `tabler-components/preview/pages/sign-in.html` | preview page | auth |
| `tabler-components/preview/pages/sign-up.html` | preview page | auth |
| `tabler-components/preview/pages/signatures.html` | preview page | form, card, modal, layout/navigation |
| `tabler-components/preview/pages/social-icons.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/stars-rating.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/steps.html` | preview page | card, layout/navigation |
| `tabler-components/preview/pages/tables.html` | preview page | card, avatar, progress, invoice/payment, layout/navigation |
| `tabler-components/preview/pages/tabs.html` | preview page | card, dropdown, timeline/activity, layout/navigation |
| `tabler-components/preview/pages/tags.html` | preview page | card, status, avatar, layout/navigation |
| `tabler-components/preview/pages/tasks.html` | preview page | layout/navigation |
| `tabler-components/preview/pages/terms-of-service.html` | preview page | card |
| `tabler-components/preview/pages/text-features.html` | preview page | card, avatar, layout/navigation |
| `tabler-components/preview/pages/toasts.html` | preview page | card, feedback, layout/navigation |
| `tabler-components/preview/pages/trial-ended.html` | preview page | card, list |
| `tabler-components/preview/pages/turbo-loader.html` | preview page | card, progress |
| `tabler-components/preview/pages/typography.html` | preview page | card, list, feedback, layout/navigation |
| `tabler-components/preview/pages/uptime.html` | preview page | table, card, chart, layout/navigation |
| `tabler-components/preview/pages/users.html` | preview page | card, status, pagination, avatar, progress, layout/navigation |
| `tabler-components/preview/pages/widgets.html` | preview page | card, status, progress, chart, invoice/payment, layout/navigation |
| `tabler-components/preview/pages/wizard.html` | preview page | form, card, progress |
| `tabler-components/preview/pages/wysiwyg.html` | preview page | form, card, layout/navigation |
| `tabler-components/shared/includes/cards/activity.html` | shared include | card, timeline/activity |
| `tabler-components/shared/includes/cards/auth-lock.html` | shared include | form, card, avatar, auth |
| `tabler-components/shared/includes/cards/blog-single.html` | shared include | card, avatar |
| `tabler-components/shared/includes/cards/body-placeholder.html` | shared include | card |
| `tabler-components/shared/includes/cards/card-group.html` | shared include | card |
| `tabler-components/shared/includes/cards/card-image.html` | shared include | card |
| `tabler-components/shared/includes/cards/card-tabs.html` | shared include | card, tabs, layout/navigation |
| `tabler-components/shared/includes/cards/card.html` | shared include | card, feedback, status, avatar, progress |
| `tabler-components/shared/includes/cards/carousel.html` | shared include | card |
| `tabler-components/shared/includes/cards/charts/active-users.html` | shared include | card, dropdown, chart |
| `tabler-components/shared/includes/cards/charts/heatmap.html` | shared include | card, chart |
| `tabler-components/shared/includes/cards/charts/new-clients.html` | shared include | card, dropdown, chart |
| `tabler-components/shared/includes/cards/charts/revenue.html` | shared include | card, dropdown, chart |
| `tabler-components/shared/includes/cards/charts/sales.html` | shared include | card, dropdown, progress |
| `tabler-components/shared/includes/cards/charts/total-sales.html` | shared include | card, dropdown, chart |
| `tabler-components/shared/includes/cards/code.html` | shared include | card |
| `tabler-components/shared/includes/cards/comments.html` | shared include | card, avatar |
| `tabler-components/shared/includes/cards/company-employees.html` | shared include | table, card, avatar, progress |
| `tabler-components/shared/includes/cards/company-lookup.html` | shared include | card, list, avatar |
| `tabler-components/shared/includes/cards/configuration.html` | shared include | card |
| `tabler-components/shared/includes/cards/credit-card.html` | shared include | form, card |
| `tabler-components/shared/includes/cards/development-activity.html` | shared include | table, card, avatar, chart, timeline/activity |
| `tabler-components/shared/includes/cards/forgot-password.html` | shared include | form, card, auth |
| `tabler-components/shared/includes/cards/form/layout.html` | shared include | form, card |
| `tabler-components/shared/includes/cards/gallery-photo.html` | shared include | card, avatar |
| `tabler-components/shared/includes/cards/icons-banner.html` | shared include | card |
| `tabler-components/shared/includes/cards/icons.html` | shared include | card |
| `tabler-components/shared/includes/cards/invoice.html` | shared include | table, card, invoice/payment |
| `tabler-components/shared/includes/cards/invoices.html` | shared include | table, form, card, status, pagination, dropdown, invoice/payment |
| `tabler-components/shared/includes/cards/map-vector.html` | shared include | card |
| `tabler-components/shared/includes/cards/most-visited-pages.html` | shared include | table, card, chart |
| `tabler-components/shared/includes/cards/music/track-info.html` | shared include | card |
| `tabler-components/shared/includes/cards/music/tracks-list.html` | shared include | card, list, dropdown |
| `tabler-components/shared/includes/cards/navbar-apps.html` | shared include | card |
| `tabler-components/shared/includes/cards/navbar-notifications.html` | shared include | card, list, status, dropdown |
| `tabler-components/shared/includes/cards/order-statistics.html` | shared include | card, list, progress |
| `tabler-components/shared/includes/cards/placeholder/card-1.html` | shared include | card |
| `tabler-components/shared/includes/cards/placeholder/card-2.html` | shared include | card |
| `tabler-components/shared/includes/cards/placeholder/card-3.html` | shared include | card, avatar |
| `tabler-components/shared/includes/cards/placeholder/card-4.html` | shared include | card, avatar |
| `tabler-components/shared/includes/cards/placeholder/card-5.html` | shared include | card |
| `tabler-components/shared/includes/cards/placeholder/card-6.html` | shared include | card, list, avatar |
| `tabler-components/shared/includes/cards/pricing-card-enterprise.html` | shared include | card |
| `tabler-components/shared/includes/cards/pricing-card.html` | shared include | card, list |
| `tabler-components/shared/includes/cards/profile-2.html` | shared include | card, list, avatar |
| `tabler-components/shared/includes/cards/profile-edit-big.html` | shared include | form, card |
| `tabler-components/shared/includes/cards/profile-edit.html` | shared include | form, card, avatar |
| `tabler-components/shared/includes/cards/profile-timeline.html` | shared include | form, card, list, avatar |
| `tabler-components/shared/includes/cards/profile.html` | shared include | card |
| `tabler-components/shared/includes/cards/project-kanban.html` | shared include | card, status, avatar, progress |
| `tabler-components/shared/includes/cards/project-progress.html` | shared include | card, dropdown, progress |
| `tabler-components/shared/includes/cards/project-summary.html` | shared include | card, status, avatar, progress |
| `tabler-components/shared/includes/cards/ribbon.html` | shared include | card |
| `tabler-components/shared/includes/cards/sign-in.html` | shared include | card, auth |
| `tabler-components/shared/includes/cards/sign-up.html` | shared include | form, card, auth |
| `tabler-components/shared/includes/cards/small-stats-2.html` | shared include | card, avatar |
| `tabler-components/shared/includes/cards/small-stats-3.html` | shared include | card |
| `tabler-components/shared/includes/cards/small-stats.html` | shared include | card, avatar, chart |
| `tabler-components/shared/includes/cards/social-traffic.html` | shared include | table, card, progress |
| `tabler-components/shared/includes/cards/sponsor.html` | shared include | card |
| `tabler-components/shared/includes/cards/store-list.html` | shared include | table, card, status |
| `tabler-components/shared/includes/cards/store-product-grid.html` | shared include | card, pagination |
| `tabler-components/shared/includes/cards/store-product.html` | shared include | card |
| `tabler-components/shared/includes/cards/subscribe.html` | shared include | card, status, dropdown, avatar |
| `tabler-components/shared/includes/cards/table-users.html` | shared include | table, card, avatar, progress, chart, timeline/activity, invoice/payment |
| `tabler-components/shared/includes/cards/tables/progressbg.html` | shared include | table, card, progress |
| `tabler-components/shared/includes/cards/tabs.html` | shared include | card, tabs, dropdown, timeline/activity, layout/navigation |
| `tabler-components/shared/includes/cards/tasks.html` | shared include | table, form, card, avatar, calendar |
| `tabler-components/shared/includes/cards/user-card-bg.html` | shared include | card, avatar |
| `tabler-components/shared/includes/cards/user-card-big.html` | shared include | card, avatar |
| `tabler-components/shared/includes/cards/user-card.html` | shared include | card, avatar |
| `tabler-components/shared/includes/cards/user-info.html` | shared include | card, calendar |
| `tabler-components/shared/includes/cards/users-list-2.html` | shared include | card, avatar |
| `tabler-components/shared/includes/cards/users-list-headers.html` | shared include | card, list, avatar |
| `tabler-components/shared/includes/cards/users-list.html` | shared include | form, card, list, status, avatar |
| `tabler-components/shared/includes/cards/welcome.html` | shared include | card, progress |
| `tabler-components/shared/includes/docs/colors.html` | shared include | layout/content |
| `tabler-components/shared/includes/docs/docs-card.html` | shared include | card |
| `tabler-components/shared/includes/docs/download-button.html` | shared include | layout/content |
| `tabler-components/shared/includes/docs/example.html` | shared include | layout/content |
| `tabler-components/shared/includes/docs/flags.html` | shared include | table |
| `tabler-components/shared/includes/docs/logo.html` | shared include | layout/content |
| `tabler-components/shared/includes/docs/menu.html` | shared include | layout/navigation |
| `tabler-components/shared/includes/docs/navbar.html` | shared include | status, layout/navigation |
| `tabler-components/shared/includes/docs/open-source-resources.html` | shared include | table |
| `tabler-components/shared/includes/docs/pagination.html` | shared include | card, pagination |
| `tabler-components/shared/includes/docs/payments.html` | shared include | table, invoice/payment |
| `tabler-components/shared/includes/docs/socials.html` | shared include | table |
| `tabler-components/shared/includes/docs/tabs-package.html` | shared include | layout/navigation |
| `tabler-components/shared/includes/docs/toc.html` | shared include | card, layout/navigation |
| `tabler-components/shared/includes/docs/ui/icon.html` | shared include | layout/content |
| `tabler-components/shared/includes/example/colors-table.html` | shared include | avatar |
| `tabler-components/shared/includes/forms/form-elements-1.html` | shared include | form, dropdown |
| `tabler-components/shared/includes/forms/form-elements-2.html` | shared include | form |
| `tabler-components/shared/includes/forms/form-elements-3.html` | shared include | form |
| `tabler-components/shared/includes/forms/form-elements-4.html` | shared include | form, dropdown, invoice/payment |
| `tabler-components/shared/includes/forms/form-elements-5.html` | shared include | form |
| `tabler-components/shared/includes/forms/form-elements-6.html` | shared include | form, avatar, progress |
| `tabler-components/shared/includes/forms/sign-in.html` | shared include | form, auth |
| `tabler-components/shared/includes/js/countup.html` | shared include | layout/content |
| `tabler-components/shared/includes/js/nouislider.html` | shared include | layout/content |
| `tabler-components/shared/includes/js/tabler-list.html` | shared include | layout/content |
| `tabler-components/shared/includes/layout/analytics.html` | shared include | layout/content |
| `tabler-components/shared/includes/layout/banner.html` | shared include | layout/content |
| `tabler-components/shared/includes/layout/css.html` | shared include | layout/content |
| `tabler-components/shared/includes/layout/footer.html` | shared include | list |
| `tabler-components/shared/includes/layout/header-actions/add-board.html` | shared include | layout/content |
| `tabler-components/shared/includes/layout/header-actions/add-job.html` | shared include | layout/content |
| `tabler-components/shared/includes/layout/header-actions/breadcrumb.html` | shared include | layout/navigation |
| `tabler-components/shared/includes/layout/header-actions/buttons.html` | shared include | layout/navigation |
| `tabler-components/shared/includes/layout/header-actions/calendar.html` | shared include | layout/content |
| `tabler-components/shared/includes/layout/header-actions/new-project.html` | shared include | layout/content |
| `tabler-components/shared/includes/layout/header-actions/photos.html` | shared include | layout/content |
| `tabler-components/shared/includes/layout/header-actions/print.html` | shared include | invoice/payment |
| `tabler-components/shared/includes/layout/header-actions/users.html` | shared include | form |
| `tabler-components/shared/includes/layout/headers/page-header-1.html` | shared include | avatar, layout/navigation |
| `tabler-components/shared/includes/layout/headers/page-header-2.html` | shared include | form, layout/navigation |
| `tabler-components/shared/includes/layout/headers/page-header-3.html` | shared include | calendar, layout/navigation |
| `tabler-components/shared/includes/layout/headers/page-header-4.html` | shared include | layout/navigation |
| `tabler-components/shared/includes/layout/headers/page-header-5.html` | shared include | layout/navigation |
| `tabler-components/shared/includes/layout/headers/profile.html` | shared include | list, avatar, layout/navigation |
| `tabler-components/shared/includes/layout/headers/uptime.html` | shared include | list, status, layout/navigation |
| `tabler-components/shared/includes/layout/homepage.html` | shared include | card, progress, chart, timeline/activity, invoice/payment |
| `tabler-components/shared/includes/layout/js-libs.html` | shared include | layout/content |
| `tabler-components/shared/includes/layout/js.html` | shared include | layout/content |
| `tabler-components/shared/includes/layout/layouts-list.html` | shared include | status |
| `tabler-components/shared/includes/layout/layouts.html` | shared include | card |
| `tabler-components/shared/includes/layout/navbar-logo.html` | shared include | layout/navigation |
| `tabler-components/shared/includes/layout/navbar-menu.html` | shared include | modal, status, dropdown, layout/navigation |
| `tabler-components/shared/includes/layout/navbar-search.html` | shared include | form |
| `tabler-components/shared/includes/layout/navbar-side.html` | shared include | card, status, dropdown, avatar, layout/navigation |
| `tabler-components/shared/includes/layout/navbar-toggler.html` | shared include | layout/navigation |
| `tabler-components/shared/includes/layout/navbar.html` | shared include | status, layout/navigation |
| `tabler-components/shared/includes/layout/og.html` | shared include | layout/content |
| `tabler-components/shared/includes/layout/page-header.html` | shared include | layout/navigation |
| `tabler-components/shared/includes/layout/sentry.html` | shared include | layout/content |
| `tabler-components/shared/includes/layout/sidebar.html` | shared include | layout/navigation |
| `tabler-components/shared/includes/marketing/hero/browser.html` | shared include | layout/content |
| `tabler-components/shared/includes/marketing/hero/side.html` | shared include | layout/content |
| `tabler-components/shared/includes/marketing/navbar.html` | shared include | layout/navigation |
| `tabler-components/shared/includes/marketing/section-divider.html` | shared include | layout/content |
| `tabler-components/shared/includes/marketing/sections/companies.html` | shared include | layout/content |
| `tabler-components/shared/includes/marketing/sections/counters.html` | shared include | layout/content |
| `tabler-components/shared/includes/marketing/sections/cta.html` | shared include | layout/content |
| `tabler-components/shared/includes/marketing/sections/faq-2.html` | shared include | layout/content |
| `tabler-components/shared/includes/marketing/sections/faq.html` | shared include | layout/content |
| `tabler-components/shared/includes/marketing/sections/features-2.html` | shared include | layout/content |
| `tabler-components/shared/includes/marketing/sections/features-3.html` | shared include | layout/content |
| `tabler-components/shared/includes/marketing/sections/features.html` | shared include | layout/content |
| `tabler-components/shared/includes/marketing/sections/pricing-banner.html` | shared include | list |
| `tabler-components/shared/includes/marketing/sections/pricing.html` | shared include | card, status |
| `tabler-components/shared/includes/marketing/sections/subscribe.html` | shared include | form |
| `tabler-components/shared/includes/marketing/sections/testimonials.html` | shared include | card, avatar |
| `tabler-components/shared/includes/parts/activity.html` | shared include | status, avatar, timeline/activity |
| `tabler-components/shared/includes/parts/calendar.html` | shared include | calendar |
| `tabler-components/shared/includes/parts/charts/activity.html` | shared include | card, status, dropdown, chart |
| `tabler-components/shared/includes/parts/datagrid.html` | shared include | form, avatar |
| `tabler-components/shared/includes/parts/demo-layout.html` | shared include | layout/navigation |
| `tabler-components/shared/includes/parts/dropdown/days.html` | shared include | dropdown |
| `tabler-components/shared/includes/parts/dropdown/months.html` | shared include | dropdown |
| `tabler-components/shared/includes/parts/form/checkboxes-list.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/fieldset.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-checkboxes-inline.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-checkboxes.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-color.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-colorpicker.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-datalist.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-file.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-icon-separated.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-icon.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-image-people.html` | shared include | form, avatar |
| `tabler-components/shared/includes/parts/form/input-image-radio.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-image.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-radios-inline.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-radios.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-range.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-selectgroups.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-sizes.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-toggle-single.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input-toggle.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/input.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/select.html` | shared include | form |
| `tabler-components/shared/includes/parts/form/selectgroup-payments.html` | shared include | form, invoice/payment |
| `tabler-components/shared/includes/parts/form/selectgroup-project-manager.html` | shared include | form, avatar |
| `tabler-components/shared/includes/parts/form/validation-states.html` | shared include | form |
| `tabler-components/shared/includes/parts/modals/danger.html` | shared include | feedback |
| `tabler-components/shared/includes/parts/modals/deactivate.html` | shared include | feedback, avatar |
| `tabler-components/shared/includes/parts/modals/full-width.html` | shared include | layout/content |
| `tabler-components/shared/includes/parts/modals/large.html` | shared include | layout/content |
| `tabler-components/shared/includes/parts/modals/report.html` | shared include | form, chart |
| `tabler-components/shared/includes/parts/modals/scrollable.html` | shared include | layout/content |
| `tabler-components/shared/includes/parts/modals/signature.html` | shared include | form, card |
| `tabler-components/shared/includes/parts/modals/simple.html` | shared include | layout/content |
| `tabler-components/shared/includes/parts/modals/small.html` | shared include | layout/content |
| `tabler-components/shared/includes/parts/modals/success.html` | shared include | invoice/payment |
| `tabler-components/shared/includes/parts/modals/team.html` | shared include | form, avatar |
| `tabler-components/shared/includes/parts/nav/nav-aside.html` | shared include | form, list |
| `tabler-components/shared/includes/parts/tasks.html` | shared include | card, status, avatar, timeline/activity, calendar |
| `tabler-components/shared/includes/redirect.html` | shared include | layout/content |
| `tabler-components/shared/includes/settings.html` | shared include | form |
| `tabler-components/shared/includes/ui/accordion.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/advanced-table.html` | shared include | table, form, card, status, pagination, dropdown, avatar, invoice/payment |
| `tabler-components/shared/includes/ui/alert.html` | shared include | feedback, avatar |
| `tabler-components/shared/includes/ui/avatar-list.html` | shared include | avatar |
| `tabler-components/shared/includes/ui/avatar-upload.html` | shared include | avatar |
| `tabler-components/shared/includes/ui/avatar.html` | shared include | status, dropdown, avatar |
| `tabler-components/shared/includes/ui/badge.html` | shared include | status, avatar |
| `tabler-components/shared/includes/ui/breadcrumb.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/button-group.html` | shared include | dropdown |
| `tabler-components/shared/includes/ui/button.html` | shared include | modal, feedback |
| `tabler-components/shared/includes/ui/card-dropdown.html` | shared include | dropdown |
| `tabler-components/shared/includes/ui/carousel.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/chart-heatmap.html` | shared include | feedback, chart |
| `tabler-components/shared/includes/ui/chart-sparkline.html` | shared include | chart |
| `tabler-components/shared/includes/ui/chart.html` | shared include | feedback, chart |
| `tabler-components/shared/includes/ui/chat.html` | shared include | avatar |
| `tabler-components/shared/includes/ui/colorpicker.html` | shared include | form |
| `tabler-components/shared/includes/ui/datepicker.html` | shared include | form, calendar |
| `tabler-components/shared/includes/ui/dropdown-menu-all.html` | shared include | form, dropdown, avatar, timeline/activity |
| `tabler-components/shared/includes/ui/dropdown-menu.html` | shared include | form, status, dropdown, avatar, timeline/activity |
| `tabler-components/shared/includes/ui/dropdown.html` | shared include | dropdown |
| `tabler-components/shared/includes/ui/dropzone.html` | shared include | form, upload/editor |
| `tabler-components/shared/includes/ui/empty.html` | shared include | feedback |
| `tabler-components/shared/includes/ui/flag.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/form/check.html` | shared include | form, feedback |
| `tabler-components/shared/includes/ui/form/input-file.html` | shared include | form |
| `tabler-components/shared/includes/ui/form/input-group.html` | shared include | form |
| `tabler-components/shared/includes/ui/form/input-icon.html` | shared include | form |
| `tabler-components/shared/includes/ui/form/input-mask.html` | shared include | form |
| `tabler-components/shared/includes/ui/form/input-selectgroup.html` | shared include | form |
| `tabler-components/shared/includes/ui/form/textarea-autosize.html` | shared include | form |
| `tabler-components/shared/includes/ui/fullcalendar.html` | shared include | feedback, calendar |
| `tabler-components/shared/includes/ui/hr.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/icon.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/illustration.html` | shared include | feedback |
| `tabler-components/shared/includes/ui/inline-player.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/map-vector.html` | shared include | feedback |
| `tabler-components/shared/includes/ui/map.html` | shared include | card |
| `tabler-components/shared/includes/ui/marketing/browser.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/modal.html` | shared include | modal |
| `tabler-components/shared/includes/ui/modal/close.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/modal/footer.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/modal/header.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/nav-segmented.html` | shared include | layout/navigation |
| `tabler-components/shared/includes/ui/nav.html` | shared include | card, tabs, layout/navigation |
| `tabler-components/shared/includes/ui/pagination.html` | shared include | pagination |
| `tabler-components/shared/includes/ui/payment.html` | shared include | invoice/payment |
| `tabler-components/shared/includes/ui/photo.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/progress-description.html` | shared include | progress |
| `tabler-components/shared/includes/ui/progress.html` | shared include | progress |
| `tabler-components/shared/includes/ui/range.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/rating.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/responsive-image.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/ribbon.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/select.html` | shared include | form, status, dropdown, avatar |
| `tabler-components/shared/includes/ui/shape.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/signature.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/spinner.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/stars.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/status-dot.html` | shared include | status |
| `tabler-components/shared/includes/ui/status-indicator.html` | shared include | status |
| `tabler-components/shared/includes/ui/status.html` | shared include | status |
| `tabler-components/shared/includes/ui/steps.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/svg.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/switch-icon.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/table.html` | shared include | table, card, dropdown, avatar |
| `tabler-components/shared/includes/ui/tag.html` | shared include | form, status, avatar, invoice/payment |
| `tabler-components/shared/includes/ui/timeline.html` | shared include | card, avatar, timeline/activity |
| `tabler-components/shared/includes/ui/toast.html` | shared include | feedback, avatar |
| `tabler-components/shared/includes/ui/tracking.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/trending.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/typed.html` | shared include | layout/content |
| `tabler-components/shared/includes/ui/wysiwyg.html` | shared include | form, feedback |
| `tabler-components/shared/layouts/base.html` | shared layout | status |
| `tabler-components/shared/layouts/card.html` | shared layout | card |
| `tabler-components/shared/layouts/default.html` | shared layout | layout/navigation |
| `tabler-components/shared/layouts/docs/default.html` | shared layout | pagination, layout/navigation |
| `tabler-components/shared/layouts/error.html` | shared layout | feedback |
| `tabler-components/shared/layouts/homepage.html` | shared layout | chart, layout/navigation |
| `tabler-components/shared/layouts/markdown.html` | shared layout | card |
| `tabler-components/shared/layouts/marketing.html` | shared layout | list, invoice/payment, layout/navigation |
| `tabler-components/shared/layouts/redirect.html` | shared layout | layout/content |
| `tabler-components/shared/layouts/settings.html` | shared layout | card, list, invoice/payment, layout/navigation |
| `tabler-components/shared/layouts/single.html` | shared layout | layout/navigation |
| `tables-and-pagination.html` | project reference | table, form, card, feedback, status, pagination, avatar, layout/navigation |
| `tabs-and-empty.html` | project reference | card, list, feedback, status, tabs, avatar, layout/navigation |
