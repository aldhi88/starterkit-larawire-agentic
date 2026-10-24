<div>
    @unless ($embedded)
        <div class="page-header d-print-none mt-0 mb-3" data-starter-region="page-header">
            <div>
                <h2 class="page-title">Notifikasi</h2>
                <p class="text-secondary mb-0">Atur preferensi audio notifikasi untuk akun Anda.</p>
            </div>
        </div>
    @endunless

    <form class="{{ $embedded ? '' : 'card' }}" wire:submit="save"
        x-data="StarterTemplate.notificationPreferences(@js($sound))"
        x-on:starter-notification-sound-updated.window="sound = $event.detail.sound">
        <div class="card-body">
            <h3 class="card-title starter-form-section-title">Suara Notifikasi</h3>
            <div class="row g-3" data-starter-region="notification-audio">
                <div class="col-md-6">
                    <label class="form-label" for="notification-sound">Jenis suara</label>
                    <select id="notification-sound" class="form-select @error('sound') is-invalid @enderror"
                        wire:model.defer="sound" x-model="sound" x-on:change="previewStatus = ''">
                        @foreach ($sounds as $key => $option)
                            <option value="{{ $key }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @error('sound') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <p class="text-secondary small mt-2 mb-0">Pilihan hanya berlaku untuk akun Anda. Simpan untuk menerapkannya.</p>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <button type="button" class="btn btn-outline-primary" x-on:click="preview()">Dengarkan Pratinjau</button>
                <button type="button" class="btn btn-outline-secondary" x-on:click="useDefault()">Gunakan Default</button>
            </div>
            <p class="text-secondary small mt-2 mb-0" role="status" x-text="previewStatus"></p>
        </div>
        <div class="card-body border-top" data-starter-region="page-actions">
            <div class="d-flex justify-content-end gap-2">
                @if (! $embedded && $canManageSettings)
                    <a href="{{ route('starter.settings', ['section' => 'notifications']) }}" class="btn btn-outline-secondary" data-starter-navigate>Pengaturan</a>
                @endif
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Simpan Preferensi</span>
                    <span wire:loading wire:target="save">Menyimpan…</span>
                </button>
            </div>
        </div>
    </form>
</div>
