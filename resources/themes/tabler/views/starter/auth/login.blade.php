<div>
    @if ($otpRequired)
        <div class="text-center mb-4" data-starter-region="otp-introduction">
            <h3 class="h3 mb-2">Verifikasi email</h3>
            <p class="text-secondary mb-0">Masukkan kode 6 digit yang dikirim ke <strong>{{ $maskedOtpEmail }}</strong>. Kode berlaku selama 5 menit.</p>
        </div>

        @if ($otpStatus !== '')
            <div class="alert alert-success" role="status">{{ $otpStatus }}</div>
        @endif

        <form wire:submit="verifyOtp" autocomplete="off" data-starter-region="otp-form">
            <div class="mb-3">
                <label class="form-label" for="login-otp">Kode OTP</label>
                @include('starter-shared::components.otp-code-input')
                @error('otpForm.code')
                    <div class="invalid-feedback d-block" id="login-otp-error">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-primary w-100" type="submit" wire:loading.attr="disabled" wire:target="verifyOtp">
                <span wire:loading.remove wire:target="verifyOtp">Verifikasi &amp; Login</span>
                <span wire:loading wire:target="verifyOtp">Memverifikasi...</span>
            </button>
        </form>

        <div class="d-flex justify-content-between align-items-center gap-3 mt-4">
            <button class="btn btn-link p-0" type="button" wire:click="cancelOtp" wire:loading.attr="disabled">Ganti akun</button>
            <button class="btn btn-link p-0" type="button" wire:click="resendOtp" wire:loading.attr="disabled" wire:target="resendOtp">
                <span wire:loading.remove wire:target="resendOtp">Kirim ulang kode</span>
                <span wire:loading wire:target="resendOtp">Mengirim...</span>
            </button>
        </div>
    @else
        <form wire:submit="authenticate" autocomplete="on" data-starter-region="credentials-form">
            <div class="mb-3">
                <label class="form-label" for="username">Username atau Email</label>
                <input type="text" class="form-control @error('form.identifier') is-invalid @enderror" id="username" name="username" wire:model.defer="form.identifier" placeholder="Contoh: superuser atau nama@perusahaan.com" autofocus autocomplete="username" autocapitalize="none" spellcheck="false">
                @error('form.identifier')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-2">
                <label class="form-label" for="password">Password</label>
                <input type="password" class="form-control @error('form.password') is-invalid @enderror" id="password" wire:model.defer="form.password" placeholder="Password" autocomplete="current-password">
                @error('form.password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            @if ($rememberMeEnabled)
                <label class="form-check mb-4">
                    <input type="checkbox" class="form-check-input" id="remember" wire:model.defer="form.remember">
                    <span class="form-check-label">Ingat saya di perangkat ini</span>
                </label>
            @else
                <div class="mb-4"></div>
            @endif

            <div class="form-footer">
                <button class="btn btn-primary w-100" type="submit" wire:loading.attr="disabled" wire:target="authenticate">
                    <span wire:loading.remove wire:target="authenticate">Login</span>
                    <span wire:loading wire:target="authenticate">Memproses...</span>
                </button>
            </div>
        </form>

        <div class="text-center text-secondary mt-3" data-starter-region="secondary-help">Hubungi administrator jika lupa password.</div>
    @endif
</div>
