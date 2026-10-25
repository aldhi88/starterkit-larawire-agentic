<div>
    @unless ($embedded)
        <div class="page-header mb-5" data-starter-region="page-header">
            <div>
                <h2 class="page-title">Notifikasi</h2>
                <p class="text-secondary mb-0">Atur preferensi audio notifikasi untuk akun Anda.</p>
            </div>
        </div>
    @endunless

    <form class="{{ $embedded ? '' : 'card' }}" wire:submit="save" novalidate
        x-data="StarterTemplate.notificationPreferences(@js($sound), @js($customAudioUrl))"
        x-on:starter-notification-sound-updated.window="sound = $event.detail.sound; customUrl = $event.detail.customUrl || ''">
        <div class="card-body">
            <div class="dashcode-form-grid dashcode-form-grid-2" data-starter-region="notification-audio">
                <div>
                    <label class="form-label" for="notification-sound">Suara notifikasi</label>
                    <div class="starter-notification-choice">
                        <select id="notification-sound" class="form-control @error('sound') is-invalid @enderror"
                            wire:model.defer="sound" x-model="sound" x-on:change="previewStatus = ''">
                            @foreach ($sounds as $key => $option)
                                <option value="{{ $key }}">{{ $option['label'] }}</option>
                            @endforeach
                            @if ($hasCustomSound)
                                <option value="custom">Audio unggahan saya</option>
                            @endif
                            <option value="upload">Unggah audio pribadi…</option>
                        </select>
                        <button type="button" class="btn btn-outline-dark" x-bind:disabled="sound === 'upload'" x-on:click="preview()" aria-label="Dengarkan pratinjau suara">
                            @include('starter.templates.layouts.icon', ['name' => 'player-play', 'class' => 'icon-sm me-1'])
                            Dengarkan
                        </button>
                    </div>
                    @error('sound') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <p class="text-secondary small mt-2 mb-0">Dengarkan contoh suara sebelum menyimpan pilihan Anda.</p>
                    <p role="status" x-bind:class="previewStatus === 'Pratinjau diputar.' ? 'dashcode-visually-hidden' : 'text-danger small mt-2 mb-0'" x-show="previewStatus" x-text="previewStatus" x-cloak></p>
                    <div class="mt-3" data-starter-region="notification-upload" x-show="sound === 'upload'" x-cloak>
                        <label class="form-label" for="notification-sound-upload">File audio</label>
                        <input id="notification-sound-upload" type="file" accept=".mp3,.wav,audio/mpeg,audio/wav,audio/x-wav"
                            class="form-control @error('soundUpload') is-invalid @enderror" wire:model="soundUpload">
                        @error('soundUpload') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <p class="text-secondary small mt-2 mb-0">MP3 atau WAV, maksimal 5 MB. Audio diterapkan saat disimpan dan menggantikan unggahan sebelumnya.</p>
                        <span class="text-secondary small" wire:loading wire:target="soundUpload" role="status">Menyiapkan audio…</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body border-top" data-starter-region="page-actions">
            <div class="dashcode-actions">
                @if (! $embedded && $canManageSettings)
                    <a href="{{ route('starter.settings', ['section' => 'notifications']) }}" class="btn btn-outline-secondary" data-starter-navigate>Pengaturan</a>
                @endif
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save,soundUpload">
                    <span wire:loading.remove wire:target="save">Simpan perubahan</span>
                    <span wire:loading wire:target="save">Menyimpan…</span>
                </button>
            </div>
        </div>
    </form>
</div>
