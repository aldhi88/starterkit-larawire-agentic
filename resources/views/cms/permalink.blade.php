@if ($permalink)
    <div class="cms-permalink" x-data="{ copied: false, failed: false }">
        <span class="cms-permalink-url" title="{{ $permalink }}">{{ $permalink }}</span>
        <button type="button" class="cms-copy-link" @click="(async () => { try { await navigator.clipboard.writeText(@js($permalink)); copied = true; failed = false; setTimeout(() => copied = false, 2000); } catch { failed = true; } })()" x-text="copied ? 'Tersalin' : 'Salin tautan'">Salin tautan</button>
        <span class="cms-copy-failure" x-show="failed" x-cloak role="status">Tidak bisa menyalin otomatis. Pilih dan salin alamat di atas.</span>
    </div>
@endif
