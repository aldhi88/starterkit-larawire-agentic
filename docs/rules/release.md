# Package Release and Publication

Rule ini berlaku untuk tag versi, GitHub Release, Packagist, atau publikasi
package. Commit dan push branch bukan release.

## Authority dan versioning

- Release hanya dilakukan setelah developer secara eksplisit meminta publikasi
  atau menyetujui versi/tag pada task aktif. Jangan menebak versi, membuat tag,
  mengubah default branch, membuat GitHub Release, atau menghubungkan Packagist
  hanya karena commit/push diizinkan.
- Gunakan Semantic Versioning. Patch untuk perbaikan kompatibel, minor untuk
  capability kompatibel, major untuk perubahan command/kontrak/source/DB yang
  memerlukan tindakan host. Catat migration, environment, dan upgrade command.
- Jangan rewrite, delete, atau force-push tag/branch yang sudah dipublikasikan.
  Perbaikan release menggunakan versi baru.

## Release gate

Sebelum tag, seluruh poin berikut wajib memiliki bukti current-run:

1. `composer validate --strict`, `composer audit --locked`, `composer analyse`,
   `vendor/bin/pint --test`, dan `composer test` lulus.
2. CI pada supported PHP/Laravel matrix lulus. Constraint Composer, minimum
   version checker, README, dan matrix harus selaras; jangan mengklaim versi
   Laravel yang belum diuji.
3. `composer archive` hanya memuat source runtime, rules/docs AGENTS, view dan
   adapter theme, indeks/recipe, lisensi, dan notices. Tidak ada HTML/aset vendor,
   `theme-intake`, `.env`, credential, database/dump, log, cache, host project,
   vendor/node_modules, atau bundle theme duplikat. Ukuran dan daftar archive diperiksa.
4. Fresh supported Laravel host memasang package melalui Composer, menolak
   source non-fresh sebelum mutation, menyelesaikan wizard install, login/route
   registry/test, `starter:sync`, rollback failure, dan production-like deploy
   preflight termasuk stale config cache.
5. `README.md` default Indonesia dan `README.en.md` menjelaskan install/update/
   deploy yang benar. `CHANGELOG.md`, `LICENSE`, `THIRD_PARTY_NOTICES.md`,
   `SECURITY.md`, dan compatibility notes sudah current.
6. Worktree dan staged diff diperiksa; perubahan unrelated tidak ikut. Secret
   scan dan pencarian nama package/namespace/theme lama tidak menemukan residu
   material.

Setiap integrasi theme wajib memenuhi `theme-package-contract.json`: source index
lengkap, manifest capability/runtime, recipe aset exact beserta hash,
license/distribution yang sah, package test, fresh-host Composer install, dan
browser matrix kedua layout. Raw intake dan seluruh HTML/aset vendor tidak boleh
masuk archive.

## Publication dan handoff

- Push commit release ke repository canonical, tunggu CI, kemudian buat tag dan
  GitHub Release hanya bila authorized. Packagist memakai repository/tag yang
  sama; jangan mengunggah archive berbeda secara manual.
- Release note merangkum perubahan, compatibility, migration/env impact,
  upgrade command `composer update aldhi88/starterkit-larawire-agentic` lalu
  `php artisan starter:sync`, risiko, dan rollback version. Jangan menyalin
  secret atau data host ke release note.
- Setelah release, derived host mengubah dependency/lock di local, menjalankan
  test dan sync, commit lock, lalu production memakai `starter:deploy`.
