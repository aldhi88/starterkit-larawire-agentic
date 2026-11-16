<section class="card mb-3"><div class="card-body p-4">
    <h2 class="card-title">Card: preset form dan mapping</h2>
    <p>Ambil beberapa Card melalui <code>$cms->cards('kode-kategori')</code>. Hasil mengikuti urutan card, status terbit, dan waktu publikasi. Jenis card hanya membantu pengisian; desain website tetap ditentukan developer.</p>
    <p>Untuk satu Card, gunakan <code>$cms->card(42)</code> atau <code>$cms->card('tim-pimpinan')</code>. Kategori bersifat opsional; ID terlihat di form edit. Pembacaan tetap mengikuti status dan waktu terbit.</p>
    <p>Jenis daftar seperti slideshow, tim, mitra, layanan, statistik, FAQ, dan agenda mendukung <strong>Beberapa item</strong> dalam satu Card (maksimal 50). Sambutan dan CTA tetap tunggal. Data lama tetap memakai bentuk tunggal.</p>
    <p>Jika <code>card_data.mode</code> bernilai <code>collection</code>, iterasi <code>$card->card_data['items'] ?? []</code>. Setiap item memiliki teks, media, tautan, dan metadata sesuai jenisnya. Urutan array adalah urutan tampil. Gunakan <code>$cms->mediaUrl((int) $item['image_id'])</code> untuk gambar yang tersedia. Teks <code>body</code> per item adalah teks biasa dan harus di-escape.</p>
    <p><code>card_preset</code> menyimpan jenis card. Judul, subjudul, ringkasan, isi, gambar, thumbnail, logo, dan tautan tetap memakai field yang sama. Mengganti jenis tidak menghapus isian.</p>
    <p><code>card_data</code> berisi data tambahan: <code>person</code>, <code>role</code>, <code>value</code>, <code>unit</code>, <code>period</code>, <code>organization</code>, <code>starts_at</code>, <code>ends_at</code>, <code>location</code>, <code>contact</code>, dan <code>hours</code>. Contoh statistik: <code>$card->card_data['value'] ?? ''</code>.</p>
    <p class="mb-0">Waktu kegiatan memakai WIB dan terpisah dari waktu terbit. Tautan video/unduhan menggunakan <code>link_url</code>. Escape semua teks saat ditampilkan; gunakan renderer konten aman untuk isi rich text.</p>
</div></section>
