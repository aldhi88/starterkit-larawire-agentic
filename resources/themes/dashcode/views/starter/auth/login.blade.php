<div class="dashcode-auth-form">
    @if ($otpRequired)
        <div class="mb-6 text-center" data-starter-region="otp-introduction">
            <h2 class="text-xl font-semibold text-slate-800">Verifikasi email</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Masukkan kode 6 digit yang dikirim ke <strong>{{ $maskedOtpEmail }}</strong>. Kode berlaku selama 5 menit.</p>
        </div>

        @if ($otpStatus !== '')
            <div class="dashcode-alert dashcode-alert-success mb-4" role="status">{{ $otpStatus }}</div>
        @endif

        <form class="space-y-4" wire:submit="verifyOtp" autocomplete="off" data-starter-region="otp-form">
            <div>
                <label class="block capitalize form-label" for="login-otp">Kode OTP</label>
                @include('starter-shared::components.otp-code-input')
                @error('otpForm.code')
                    <div class="invalid-feedback block" id="login-otp-error">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-dark block w-full text-center" type="submit" wire:loading.attr="disabled" wire:target="verifyOtp">
                <span wire:loading.remove wire:target="verifyOtp">Verifikasi &amp; Login</span>
                <span wire:loading wire:target="verifyOtp">Memverifikasi...</span>
            </button>
        </form>

        <div class="mt-5 flex items-center justify-between gap-3">
            <button class="btn-link text-sm" type="button" wire:click="cancelOtp" wire:loading.attr="disabled">Ganti akun</button>
            <button class="btn-link text-sm" type="button" wire:click="resendOtp" wire:loading.attr="disabled" wire:target="resendOtp">
                <span wire:loading.remove wire:target="resendOtp">Kirim ulang kode</span>
                <span wire:loading wire:target="resendOtp">Mengirim...</span>
            </button>
        </div>
    @elseif ($authenticatorRequired)
        <div class="mb-6 text-center" data-starter-region="authenticator-introduction">
            <h2 class="text-xl font-semibold text-slate-800">Verifikasi authenticator</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Buka Google Authenticator atau aplikasi TOTP Anda, lalu masukkan kode yang sedang aktif.</p>
        </div>

        <form class="space-y-4" wire:submit="verifyAuthenticator" autocomplete="off" data-starter-region="authenticator-form">
            <div>
                <label class="block capitalize form-label" for="login-authenticator">{{ $authenticatorRecoveryMode ? 'Kode Pemulihan' : 'Kode Authenticator' }}</label>
                @if ($authenticatorRecoveryMode)
                    <input type="text" class="form-control py-2 uppercase font-mono @error('authenticatorForm.code') is-invalid @enderror" id="login-authenticator" wire:model.defer="authenticatorForm.code" placeholder="ABCD-EFGH" maxlength="9" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false">
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
                    <div class="invalid-feedback block" id="login-authenticator-error">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-dark block w-full text-center" type="submit" wire:loading.attr="disabled" wire:target="verifyAuthenticator">
                <span wire:loading.remove wire:target="verifyAuthenticator">Verifikasi &amp; Login</span>
                <span wire:loading wire:target="verifyAuthenticator">Memverifikasi...</span>
            </button>
        </form>

        <div class="mt-5 flex items-center justify-between gap-3">
            <button class="btn-link text-sm" type="button" wire:click="cancelTwoFactor" wire:loading.attr="disabled">Ganti akun</button>
            <button class="btn-link text-sm" type="button" wire:click="toggleAuthenticatorRecoveryMode" wire:loading.attr="disabled">
                {{ $authenticatorRecoveryMode ? 'Gunakan authenticator' : 'Gunakan kode pemulihan' }}
            </button>
        </div>
    @else
        <form class="space-y-4" wire:submit="authenticate" autocomplete="on" data-starter-region="credentials-form">
            <div>
                <label class="block capitalize form-label" for="username">Username atau Email</label>
                <input type="text" class="form-control py-2 @error('form.identifier') is-invalid @enderror" id="username" name="username" wire:model.defer="form.identifier" placeholder="Contoh: superuser atau nama@perusahaan.com" autofocus autocomplete="username" autocapitalize="none" spellcheck="false">
                @error('form.identifier')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block capitalize form-label" for="password">Password</label>
                <input type="password" class="form-control py-2 @error('form.password') is-invalid @enderror" id="password" wire:model.defer="form.password" placeholder="Masukkan password" autocomplete="current-password">
                @error('form.password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            @if ($humanChallengeImage !== '')
                <div data-starter-region="human-challenge">
                    <label class="block capitalize form-label" for="human-challenge">Verifikasi keamanan</label>
                    <div class="flex items-stretch gap-2 mb-2">
                        <div class="form-control w-auto p-1 flex items-center justify-center bg-slate-50">
                            <img src="{{ $humanChallengeImage }}" class="block" width="143" height="38" alt="Lima angka keamanan acak">
                        </div>
                        <button class="btn btn-outline-dark px-3" type="button" wire:click="refreshHumanChallenge" wire:loading.attr="disabled" wire:target="refreshHumanChallenge" aria-label="Tampilkan angka keamanan baru">
                            @include('starter.templates.layouts.icon', ['name' => 'refresh', 'class' => 'm-0'])
                        </button>
                    </div>
                    <input type="text" class="form-control py-2 font-mono @error('form.human_challenge') is-invalid @enderror" id="human-challenge" wire:model.defer="form.human_challenge" inputmode="numeric" pattern="[0-9]*" maxlength="5" autocomplete="off" placeholder="Ketik 5 angka di atas" aria-describedby="human-challenge-help @error('form.human_challenge') human-challenge-error @enderror">
                    <p class="mt-1 text-xs leading-5 text-slate-500" id="human-challenge-help">Setiap angka hanya muncul satu kali. Muat ulang jika sulit dibaca.</p>
                    @error('form.human_challenge')
                        <div class="invalid-feedback block" id="human-challenge-error">{{ $message }}</div>
                    @enderror
                </div>
            @endif

            @if ($rememberMeEnabled)
                <div class="flex">
                    <label class="flex items-center gap-2 cursor-pointer" for="remember">
                        <input type="checkbox" class="h-4 w-4 flex-none" id="remember" wire:model.defer="form.remember">
                        <span class="text-slate-500 text-sm leading-6">Ingat saya di perangkat ini</span>
                    </label>
                </div>
            @endif

            <div>
                <button class="btn btn-dark block w-full text-center" type="submit" wire:loading.attr="disabled" wire:target="authenticate">
                    <span wire:loading.remove wire:target="authenticate">Login</span>
                    <span wire:loading wire:target="authenticate">Memproses...</span>
                </button>
            </div>
        </form>

        <div class="text-center text-secondary mt-3" data-starter-region="secondary-help">Hubungi administrator jika lupa password.</div>
    @endif
</div>
