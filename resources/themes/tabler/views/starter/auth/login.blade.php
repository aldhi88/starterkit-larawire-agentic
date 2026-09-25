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
    @elseif ($authenticatorRequired)
        <div class="text-center mb-4" data-starter-region="authenticator-introduction">
            <h3 class="h3 mb-2">Verifikasi authenticator</h3>
            <p class="text-secondary mb-0">Buka Google Authenticator atau aplikasi TOTP Anda, lalu masukkan kode yang sedang aktif.</p>
        </div>

        <form wire:submit="verifyAuthenticator" autocomplete="off" data-starter-region="authenticator-form">
            <div class="mb-3">
                <label class="form-label" for="login-authenticator">{{ $authenticatorRecoveryMode ? 'Kode Pemulihan' : 'Kode Authenticator' }}</label>
                @if ($authenticatorRecoveryMode)
                    <input type="text" class="form-control font-monospace text-uppercase @error('authenticatorForm.code') is-invalid @enderror" id="login-authenticator" wire:model.defer="authenticatorForm.code" placeholder="ABCD-EFGH" maxlength="9" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false">
                @else
                    @include('starter-shared::components.otp-code-input', [
                        'model' => 'authenticatorForm.code',
                        'field' => 'authenticatorForm.code',
                        'inputId' => 'login-authenticator',
                        'errorId' => 'login-authenticator-error',
                        'label' => 'Kode authenticator 6 digit',
                        'brand' => 'google-authenticator',
                    ])
                @endif
                @error('authenticatorForm.code')
                    <div class="invalid-feedback d-block" id="login-authenticator-error">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-primary w-100" type="submit" wire:loading.attr="disabled" wire:target="verifyAuthenticator">
                <span wire:loading.remove wire:target="verifyAuthenticator">Verifikasi &amp; Login</span>
                <span wire:loading wire:target="verifyAuthenticator">Memverifikasi...</span>
            </button>
        </form>

        <div class="d-flex justify-content-between align-items-center gap-3 mt-4">
            <button class="btn btn-link p-0" type="button" wire:click="cancelTwoFactor" wire:loading.attr="disabled">Ganti akun</button>
            <button class="btn btn-link p-0" type="button" wire:click="toggleAuthenticatorRecoveryMode" wire:loading.attr="disabled">
                {{ $authenticatorRecoveryMode ? 'Gunakan authenticator' : 'Gunakan kode pemulihan' }}
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
                <div class="input-group input-group-flat" x-data="{ visible: false }">
                    <input x-bind:type="visible ? 'text' : 'password'" class="form-control @error('form.password') is-invalid @enderror" id="password" wire:model.defer="form.password" placeholder="Password" autocomplete="current-password">
                    <button type="button" class="input-group-text" x-on:click="visible = ! visible" x-bind:aria-label="visible ? 'Sembunyikan Password' : 'Tampilkan Password'">
                        <span x-show="! visible">@include('starter.templates.layouts.icon', ['name' => 'eye', 'class' => 'icon-sm'])</span>
                        <span x-show="visible" x-cloak>@include('starter.templates.layouts.icon', ['name' => 'eye-off', 'class' => 'icon-sm'])</span>
                    </button>
                </div>
                @error('form.password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            @if ($humanChallengeImage !== '')
                <div class="mb-3" data-starter-region="human-challenge">
                    <label class="form-label" for="human-challenge">Verifikasi keamanan</label>
                    <div class="input-group flex-nowrap mb-2">
                        <span class="input-group-text flex-grow-1 justify-content-center p-1 bg-light overflow-hidden">
                            <img src="{{ $humanChallengeImage }}" class="d-block" width="143" height="38" alt="Lima angka keamanan acak">
                        </span>
                        <button class="btn btn-outline-secondary" type="button" wire:click="refreshHumanChallenge" wire:loading.attr="disabled" wire:target="refreshHumanChallenge" aria-label="Tampilkan angka keamanan baru">
                            @include('starter.templates.layouts.icon', ['name' => 'refresh', 'class' => 'm-0'])
                        </button>
                    </div>
                    <input type="text" class="form-control font-monospace @error('form.human_challenge') is-invalid @enderror" id="human-challenge" wire:model.defer="form.human_challenge" inputmode="numeric" pattern="[0-9]*" maxlength="5" autocomplete="off" placeholder="Ketik 5 angka di atas" aria-describedby="human-challenge-help @error('form.human_challenge') human-challenge-error @enderror">
                    <div class="form-hint" id="human-challenge-help">Setiap angka hanya muncul satu kali. Muat ulang jika sulit dibaca.</div>
                    @error('form.human_challenge')
                        <div class="invalid-feedback" id="human-challenge-error">{{ $message }}</div>
                    @enderror
                </div>
            @endif

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
