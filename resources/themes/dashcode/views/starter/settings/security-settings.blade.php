<form class="dashcode-security-settings {{ $embedded ? '' : 'card' }}" wire:submit="save">
    <div class="card-body">
        @if(! $embedded)
            <h2 class="mb-4">Keamanan Sistem</h2>
        @endif

        <h3 class="card-title starter-settings-section-title">Sesi dan Lock Screen</h3>
        <div class="dashcode-form-grid dashcode-form-grid-2 dashcode-section-gap mt-4" data-starter-region="session-security">
            <div class="dashcode-stack">
                <label class="starter-switch-row">
                    <span class="starter-switch-control">
                        <input class="starter-switch-input" type="checkbox" wire:model.defer="securityForm.remember_me_enabled">
                        <span class="starter-switch-track" aria-hidden="true"></span>
                    </span>
                    <span class="starter-switch-label">
                        <span class="starter-switch-title">Aktifkan Remember Me</span>
                        <span class="dashcode-help-text">User dapat memilih tetap login pada perangkat yang dipercaya.</span>
                    </span>
                </label>

                <label class="starter-switch-row">
                    <span class="starter-switch-control">
                        <input class="starter-switch-input" type="checkbox" wire:model.defer="securityForm.lock_screen_enabled">
                        <span class="starter-switch-track" aria-hidden="true"></span>
                    </span>
                    <span class="starter-switch-label">
                        <span class="starter-switch-title">Aktifkan Lock Screen Otomatis</span>
                        <span class="dashcode-help-text">Aplikasi dikunci tanpa mengakhiri sesi login.</span>
                    </span>
                </label>
            </div>

            <div>
                <label class="form-label" for="lock-timeout">Kunci setelah tidak aktif</label>
                <div class="relative">
                    <input
                        type="number"
                        class="form-control appearance-none !pr-14 @error('securityForm.lock_screen_timeout_minutes') is-invalid @enderror"
                        id="lock-timeout"
                        min="1"
                        max="1440"
                        wire:model.defer="securityForm.lock_screen_timeout_minutes"
                        @disabled(! $securityForm['lock_screen_enabled'])
                    >
                    <span class="absolute right-0 top-1/2 h-full -translate-y-1/2 border-none px-3 flex items-center justify-center">menit</span>
                </div>
                @error('securityForm.lock_screen_timeout_minutes')
                    <div class="invalid-feedback block">{{ $message }}</div>
                @enderror
                <div class="form-hint">Rentang 1–1.440 menit. Rekomendasi untuk komputer bersama: 10–15 menit.</div>
            </div>
        </div>

        <h3 class="card-title starter-settings-section-title mt-4">Proteksi Login</h3>
        <div class="dashcode-form-grid dashcode-form-grid-2 mt-4" data-starter-region="login-protection">
            <div>
                <label class="form-label" for="login-attempts">Maksimum percobaan login</label>
                <div class="relative">
                    <input type="number" class="form-control appearance-none !pr-14 @error('securityForm.login_max_attempts') is-invalid @enderror" id="login-attempts" min="1" max="20" wire:model.defer="securityForm.login_max_attempts">
                    <span class="absolute right-0 top-1/2 h-full -translate-y-1/2 border-none px-3 flex items-center justify-center">kali</span>
                </div>
                @error('securityForm.login_max_attempts')
                    <div class="invalid-feedback block">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label class="form-label" for="login-decay">Durasi pembatasan</label>
                <div class="relative">
                    <input type="number" class="form-control appearance-none !pr-14 @error('securityForm.login_decay_seconds') is-invalid @enderror" id="login-decay" min="30" max="3600" wire:model.defer="securityForm.login_decay_seconds">
                    <span class="absolute right-0 top-1/2 h-full -translate-y-1/2 border-none px-3 flex items-center justify-center">detik</span>
                </div>
                @error('securityForm.login_decay_seconds')
                    <div class="invalid-feedback block">{{ $message }}</div>
                @enderror
                <div class="form-hint">Penghitung akan kembali normal setelah user berhasil login.</div>
            </div>
        </div>
    </div>

    <div class="{{ $embedded ? 'card-body border-top bg-transparent' : 'card-footer bg-transparent mt-auto' }}" data-starter-region="page-actions">
        <div class="dashcode-actions">
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
