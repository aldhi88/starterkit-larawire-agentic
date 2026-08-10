<form class="{{ $embedded ? '' : 'card' }}" wire:submit="save">
    <div class="card-body">
        @if(! $embedded)
            <h2 class="mb-4">Keamanan Sistem</h2>
        @endif

        <h3 class="card-title">Sesi dan Lock Screen</h3>
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <label class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" wire:model.defer="securityForm.remember_me_enabled">
                    <span class="form-check-label">
                        <span class="d-block fw-semibold">Aktifkan Remember Me</span>
                        <span class="d-block small text-secondary">User dapat memilih tetap login pada perangkat yang dipercaya.</span>
                    </span>
                </label>

                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" wire:model.defer="securityForm.lock_screen_enabled">
                    <span class="form-check-label">
                        <span class="d-block fw-semibold">Aktifkan Lock Screen Otomatis</span>
                        <span class="d-block small text-secondary">Aplikasi dikunci tanpa mengakhiri sesi login.</span>
                    </span>
                </label>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="lock-timeout">Kunci setelah tidak aktif</label>
                <div class="input-group">
                    <input
                        type="number"
                        class="form-control @error('securityForm.lock_screen_timeout_minutes') is-invalid @enderror"
                        id="lock-timeout"
                        min="1"
                        max="1440"
                        wire:model.defer="securityForm.lock_screen_timeout_minutes"
                        @disabled(! $securityForm['lock_screen_enabled'])
                    >
                    <span class="input-group-text">menit</span>
                    @error('securityForm.lock_screen_timeout_minutes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-hint">Rentang 1–1.440 menit. Rekomendasi untuk komputer bersama: 10–15 menit.</div>
            </div>
        </div>

        <h3 class="card-title mt-4">Proteksi Login</h3>
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label" for="login-attempts">Maksimum percobaan login</label>
                <div class="input-group">
                    <input type="number" class="form-control @error('securityForm.login_max_attempts') is-invalid @enderror" id="login-attempts" min="1" max="20" wire:model.defer="securityForm.login_max_attempts">
                    <span class="input-group-text">kali</span>
                    @error('securityForm.login_max_attempts')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="login-decay">Durasi pembatasan</label>
                <div class="input-group">
                    <input type="number" class="form-control @error('securityForm.login_decay_seconds') is-invalid @enderror" id="login-decay" min="30" max="3600" wire:model.defer="securityForm.login_decay_seconds">
                    <span class="input-group-text">detik</span>
                    @error('securityForm.login_decay_seconds')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-hint">Penghitung akan kembali normal setelah user berhasil login.</div>
            </div>
        </div>
    </div>

    <div class="{{ $embedded ? 'card-body border-top bg-transparent' : 'card-footer bg-transparent mt-auto' }}">
        <div class="btn-list justify-content-end">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">
                    @include('starter.templates.layouts.icon', ['name' => 'check', 'class' => 'icon'])
                    Simpan Konfigurasi
                </span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </button>
        </div>
    </div>
</form>
