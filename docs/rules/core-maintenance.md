# Canonical Starter Core Maintenance

Rule ini berlaku ketika repository yang sedang dikerjakan adalah repository
canonical Composer package `aldhi88/starterkit-larawire-agentic`.

## Eksekusi langsung

- Perubahan core starterkit yang diminta langsung oleh developer dieksekusi
  tanpa prosedur konfirmasi pemahaman, file planning, file `issues/*.md`,
  persetujuan spesifikasi, atau persetujuan eksekusi tambahan.
- Lakukan discovery source yang relevan, implementasi perubahan, verifikasi
  secara proporsional, lalu laporkan hasilnya.
- Jangan membuat atau mengarsipkan issue feature/bug milik Laravel host untuk
  maintenance repository canonical starterkit.
- Permintaan developer yang secara eksplisit mengubah rule starterkit sudah
  menjadi konfirmasi perubahan rule; jangan meminta konfirmasi duplikat.
- Tetap minta keputusan hanya jika terdapat pilihan bisnis, authorization,
  data, atau scope material yang benar-benar belum ditentukan dan tidak dapat
  dibuktikan dari source.

## Batas penerapan

- Pengecualian ini hanya untuk perubahan core universal di repository
  canonical starterkit.
- Ketika starterkit dipakai untuk mengembangkan feature atau memperbaiki bug
  aplikasi Laravel turunannya, prosedur confirmation → `issues/*.md` → approval
  → implementation pada `feature-development.md` tetap wajib.
- Feature project tidak boleh disamarkan sebagai core improvement untuk
  melewati prosedur aplikasi turunan.

## Git dan verifikasi

- Pastikan perubahan core berada pada branch package yang benar sebelum commit,
  tag release, atau push.
- Verifikasi core melalui Laravel host bila perubahan menyentuh integrasi
  framework, installer, Artisan, migration, route, Livewire, theme, atau asset.
- Untuk perubahan theme, verifikasi terhadap HTML dan atlas vendor theme aktif,
  bukan terhadap tampilan theme lain. Kesetaraan lintas theme hanya berlaku pada
  capability dan perilaku; struktur dan visual tetap diverifikasi per theme.
- Jangan menyinkronkan commit starterkit ke Laravel host mana pun, termasuk
  `dosen/dosen-bo` dan `starterkit-test`, kecuali developer memerintahkannya
  secara eksplisit pada task yang sedang berjalan. Permintaan perubahan atau
  push repository canonical tidak otomatis mengizinkan sinkronisasi host.
- Jika verifikasi host diperintahkan, pasang package melalui Composer path/VCS
  repository, jalankan `starter:sync`, dan verifikasi relevan. Jangan mengedit
  source di dalam `vendor`.
- Commit/push yang diminta untuk maintenance tidak otomatis mengizinkan tag,
  GitHub Release, Packagist publication, deployment, atau perubahan repository
  Laravel host. Untuk publikasi versi baca dan penuhi `release.md`, lalu gunakan
  authority eksplisit yang diberikan developer pada task tersebut.
