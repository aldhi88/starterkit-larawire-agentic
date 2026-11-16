<?php

namespace Aldhi88\StarterKit\Support\Cms;

final class CmsCardPresets
{
    public static function all(): array
    {
        $make = static fn (string $label, string $help, array $fields, array $labels = []): array => compact('label', 'help', 'fields', 'labels');

        return [
            'free' => $make('Bebas', 'Gunakan field sesuai kebutuhan bagian website Anda.', ['title', 'subtitle', 'excerpt', 'body', 'image_id', 'thumbnail_id', 'logo_id', 'link_label', 'link_url']),
            'banner' => $make('Banner / Slide', 'Susun slideshow dengan gambar, caption, dan tautan pada setiap slide.', ['title', 'subtitle', 'excerpt', 'image_id', 'link_label', 'link_url']),
            'service' => $make('Layanan / Fitur', 'Jelaskan satu layanan atau keunggulan secara singkat.', ['title', 'excerpt', 'logo_id', 'image_id', 'link_label', 'link_url']),
            'product' => $make('Produk / Program', 'Kenalkan satu produk, program studi, atau pelatihan.', ['title', 'subtitle', 'excerpt', 'body', 'image_id', 'link_label', 'link_url']),
            'profile' => $make('Tim / Profil', 'Susun anggota tim beserta foto dan jabatan, atau tampilkan satu profil.', ['title', 'subtitle', 'image_id', 'body', 'link_url'], ['title' => 'Nama lengkap', 'subtitle' => 'Jabatan / peran', 'body' => 'Biografi', 'image_id' => 'Foto profil', 'link_url' => 'Tautan profil']),
            'partner' => $make('Logo / Mitra', 'Tampilkan identitas mitra, sponsor, atau afiliasi.', ['title', 'logo_id', 'link_url'], ['title' => 'Nama mitra', 'link_url' => 'Website mitra']),
            'greeting' => $make('Sambutan', 'Tulis sambutan dan identitas orang yang menyampaikannya.', ['title', 'card_data.person', 'card_data.role', 'image_id', 'body'], ['body' => 'Isi sambutan']),
            'testimonial' => $make('Testimoni / Kutipan', 'Isi satu kutipan beserta identitas pemberinya.', ['excerpt', 'card_data.person', 'card_data.role', 'image_id'], ['excerpt' => 'Kutipan / testimoni', 'image_id' => 'Foto pemberi testimoni']),
            'statistic' => $make('Statistik / Angka', 'Pisahkan nilai, satuan, dan label. Contoh: 12.500 · mahasiswa · Mahasiswa aktif.', ['title', 'card_data.value', 'card_data.unit', 'excerpt', 'logo_id'], ['title' => 'Label statistik', 'excerpt' => 'Keterangan angka']),
            'cta' => $make('Ajakan / CTA', 'Arahkan pengunjung ke satu tindakan, misalnya Daftar Sekarang.', ['title', 'excerpt', 'image_id', 'link_label', 'link_url']),
            'faq' => $make('FAQ', 'Kelola daftar pertanyaan dan jawaban dalam satu Card.', ['title', 'body'], ['title' => 'Pertanyaan', 'body' => 'Jawaban']),
            'step' => $make('Langkah / Proses', 'Susun langkah-langkah proses dalam urutan yang mudah diikuti.', ['title', 'excerpt', 'body', 'logo_id'], ['title' => 'Nama langkah']),
            'timeline' => $make('Linimasa / Sejarah', 'Catat satu tonggak perjalanan institusi.', ['title', 'card_data.period', 'body', 'image_id'], ['title' => 'Judul peristiwa']),
            'event' => $make('Kegiatan / Agenda', 'Waktu kegiatan berbeda dari waktu publikasi card.', ['title', 'excerpt', 'image_id', 'card_data.starts_at', 'card_data.ends_at', 'card_data.location', 'link_label', 'link_url']),
            'award' => $make('Prestasi / Penghargaan', 'Kenalkan penghargaan, akreditasi, atau sertifikasi.', ['title', 'card_data.organization', 'card_data.period', 'excerpt', 'image_id']),
            'facility' => $make('Fasilitas / Lokasi', 'Kenalkan fasilitas atau lokasi beserta informasinya.', ['title', 'excerpt', 'body', 'image_id', 'card_data.location', 'link_url']),
            'contact' => $make('Kontak / Kanal Layanan', 'Tampilkan satu kanal layanan beserta jam operasionalnya.', ['title', 'card_data.contact', 'card_data.hours', 'logo_id', 'link_label', 'link_url']),
            'download' => $make('Dokumen / Unduhan', 'Tambahkan judul, sampul opsional, dan tautan file yang dapat diunduh.', ['title', 'excerpt', 'thumbnail_id', 'link_label', 'link_url'], ['link_url' => 'Tautan file / PDF', 'link_label' => 'Teks tombol unduh']),
            'video' => $make('Video / Media Unggulan', 'Gunakan URL video, bukan kode embed. Developer menentukan pemutarnya.', ['title', 'excerpt', 'thumbnail_id', 'link_url'], ['link_url' => 'Tautan video']),
        ];
    }

    /** Presets whose usual website presentation contains repeatable entries. */
    public static function collectionLabels(): array
    {
        return [
            'banner' => 'slide', 'service' => 'layanan', 'product' => 'program',
            'profile' => 'anggota', 'partner' => 'mitra', 'testimonial' => 'testimoni',
            'statistic' => 'statistik', 'faq' => 'pertanyaan', 'step' => 'langkah',
            'timeline' => 'peristiwa', 'event' => 'kegiatan', 'award' => 'prestasi',
            'facility' => 'fasilitas', 'contact' => 'kontak', 'download' => 'dokumen', 'video' => 'video',
        ];
    }

    public static function itemFields(): array
    {
        return ['title', 'subtitle', 'excerpt', 'body', 'image_id', 'thumbnail_id', 'logo_id',
            'link_label', 'link_url', ...array_keys(self::extraFields())];
    }

    public static function extraFields(): array
    {
        return [
            'person' => ['Nama pemberi sambutan / kutipan', 'text', 'Dr. Ratna Pratiwi'],
            'role' => ['Jabatan / afiliasi', 'text', 'Rektor Universitas'],
            'value' => ['Nilai statistik', 'text', '12.500'],
            'unit' => ['Satuan', 'text', 'mahasiswa'],
            'period' => ['Tahun / periode', 'text', '2026'],
            'organization' => ['Pemberi penghargaan', 'text', 'Lembaga Akreditasi'],
            'starts_at' => ['Mulai kegiatan (WIB)', 'datetime-local', ''],
            'ends_at' => ['Selesai kegiatan (WIB)', 'datetime-local', ''],
            'location' => ['Lokasi / alamat', 'text', 'Auditorium Kampus Utama'],
            'contact' => ['Informasi kontak', 'text', 'admisi@kampus.ac.id'],
            'hours' => ['Jam layanan', 'text', 'Senin–Jumat, 08.00–16.00 WIB'],
        ];
    }
}
