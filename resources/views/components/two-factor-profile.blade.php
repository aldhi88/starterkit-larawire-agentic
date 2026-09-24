@if (config('starter.auth.login_two_factor_enabled', true))
@php($theme = \Aldhi88\StarterKit\Support\Starter\StarterTheme::key())

@if ($theme === 'dashcode')
    <section class="card-body border-t border-slate-200" data-starter-region="two-factor-security">
        <div class="flex items-start justify-between gap-2 mb-4">
            <div>
                <h3 class="dashcode-profile-form-title">Two-Factor Authentication</h3>
                <p class="dashcode-profile-form-copy">Tambahkan kode dari Google Authenticator atau aplikasi TOTP setiap kali login.</p>
                @include('starter-shared::components.google-authenticator-brand', ['theme' => $theme, 'class' => 'mt-3'])
            </div>
            <span class="badge flex-none whitespace-nowrap {{ $login->hasTwoFactorAuthenticationEnabled() ? 'bg-success-500 text-white' : 'bg-slate-100 text-slate-600' }}">
                {{ $login->hasTwoFactorAuthenticationEnabled() ? 'Aktif' : 'Belum aktif' }}
            </span>
        </div>

        @if ($twoFactorRecoveryCodes !== [])
            <div class="dashcode-alert dashcode-alert-warning mb-4" role="status" style="display: block; min-width: 0; max-width: 100%; overflow: hidden;">
                <div class="w-full min-w-0" style="max-width: 100%;">
                    <div class="font-medium">Simpan kode pemulihan sekarang</div>
                    <p class="text-sm mt-1 mb-3">Setiap kode hanya dapat dipakai satu kali. Kode ini tidak akan ditampilkan lagi setelah halaman dimuat ulang.</p>
                    <div class="overflow-x-auto pb-1" data-starter-region="two-factor-recovery-codes" tabindex="0" aria-label="Kode pemulihan authenticator" style="max-width: 100%;">
                        <div class="flex gap-2 min-w-max font-mono">
                            @foreach ($twoFactorRecoveryCodes as $recoveryCode)
                                <span class="badge flex-none whitespace-nowrap bg-white text-slate-800 text-center px-3 py-2">{{ $recoveryCode }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($twoFactorSetupActive)
            <div class="dashcode-alert dashcode-alert-info mb-4" role="note">
                <div>
                    <div class="font-medium">1. Pindai QR code</div>
                    <p class="text-sm mt-1">Di Google Authenticator pilih tambah akun, lalu pindai QR code berikut.</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-5 items-start md:grid-cols-3" data-starter-region="two-factor-setup">
                <div class="text-center" data-starter-region="two-factor-qr">
                    <div class="inline-flex rounded border border-slate-200 bg-white p-3 shadow-sm">
                        <img src="{{ $twoFactorQrCode }}" width="184" height="184" alt="QR code aktivasi authenticator">
                    </div>
                </div>
                <div class="md:col-span-2" data-starter-region="two-factor-verification">
                    <div class="rounded border border-slate-200 bg-slate-50 p-3 mb-4" data-starter-region="two-factor-manual-key">
                        <p class="text-sm text-slate-500 mb-2">Tidak dapat memindai? Masukkan setup key ini secara manual:</p>
                        <div class="font-mono font-semibold leading-6 break-words">{{ $twoFactorSetupKey }}</div>
                    </div>
                    <div class="max-w-xl">
                        <label class="form-label" for="two-factor-confirm-code">2. Masukkan kode 6 digit</label>
                        <input type="text" class="form-control py-2 font-mono tracking-widest @error('twoFactorForm.code') is-invalid @enderror" id="two-factor-confirm-code" wire:model.defer="twoFactorForm.code" wire:keydown.enter.prevent="confirmTwoFactorSetup" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" placeholder="000000">
                        @error('twoFactorForm.code')<div class="invalid-feedback block">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid grid-cols-1 gap-2 max-w-md mt-4 md:grid-cols-2" data-starter-region="two-factor-actions">
                        <button type="button" class="btn btn-primary w-full" wire:click="confirmTwoFactorSetup" wire:loading.attr="disabled" wire:target="confirmTwoFactorSetup">
                            <span wire:loading.remove wire:target="confirmTwoFactorSetup">Aktifkan Authenticator</span>
                            <span wire:loading wire:target="confirmTwoFactorSetup">Memverifikasi...</span>
                        </button>
                        <button type="button" class="btn btn-outline-dark w-full" wire:click="cancelTwoFactorSetup" wire:loading.attr="disabled">Batalkan Aktivasi</button>
                    </div>
                </div>
            </div>
        @elseif ($login->hasTwoFactorAuthenticationEnabled())
            <div class="dashcode-alert dashcode-alert-success mb-4" role="status">
                Authenticator aktif. Login berikutnya akan meminta kode setelah password{{ config('starter.auth.login_otp_enabled') ? ' dan OTP email' : '' }} berhasil diverifikasi.
            </div>
            <div class="dashcode-form-grid dashcode-form-grid-2">
                <div>
                    <label class="form-label" for="two-factor-disable-password">Password Saat Ini</label>
                    <input type="password" class="form-control @error('twoFactorDisableForm.password') is-invalid @enderror" id="two-factor-disable-password" wire:model.defer="twoFactorDisableForm.password" wire:keydown.enter.prevent autocomplete="current-password">
                    @error('twoFactorDisableForm.password')<div class="invalid-feedback block">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="two-factor-disable-code">Kode Authenticator</label>
                    <input type="text" class="form-control font-mono @error('twoFactorDisableForm.code') is-invalid @enderror" id="two-factor-disable-code" wire:model.defer="twoFactorDisableForm.code" wire:keydown.enter.prevent inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" placeholder="000000">
                    @error('twoFactorDisableForm.code')<div class="invalid-feedback block">{{ $message }}</div>@enderror
                </div>
            </div>
            <button type="button" class="btn btn-outline-danger mt-4" wire:click="disableTwoFactorAuthentication" wire:loading.attr="disabled" wire:target="disableTwoFactorAuthentication">
                <span wire:loading.remove wire:target="disableTwoFactorAuthentication">Nonaktifkan Authenticator</span>
                <span wire:loading wire:target="disableTwoFactorAuthentication">Memproses...</span>
            </button>
        @else
            <div class="dashcode-alert dashcode-alert-info mb-4" role="note">
                <div class="min-w-0 text-sm leading-6">Setelah aktif, password saja tidak cukup untuk masuk. Siapkan Google Authenticator sebelum memulai.</div>
            </div>
            <div class="max-w-3xl" data-starter-region="two-factor-start">
                <label class="form-label" for="two-factor-password">Konfirmasi Password Saat Ini</label>
                <div class="dashcode-responsive-row" style="align-items: stretch;">
                    <input type="password" class="form-control dashcode-grow @error('twoFactorForm.password') is-invalid @enderror" style="min-height: 40px !important;" id="two-factor-password" wire:model.defer="twoFactorForm.password" wire:keydown.enter.prevent="startTwoFactorSetup" autocomplete="current-password">
                    <button type="button" class="btn btn-outline-primary dashcode-push-right px-4 py-2" style="height: 40px !important;" wire:click="startTwoFactorSetup" wire:loading.attr="disabled" wire:target="startTwoFactorSetup">
                        <span wire:loading.remove wire:target="startTwoFactorSetup">Mulai Aktivasi</span>
                        <span wire:loading wire:target="startTwoFactorSetup">Menyiapkan...</span>
                    </button>
                </div>
                @error('twoFactorForm.password')<div class="invalid-feedback block mt-1">{{ $message }}</div>@enderror
            </div>
        @endif
    </section>
@else
    <section class="card-body border-top" data-starter-region="two-factor-security">
        <div class="d-flex flex-column flex-md-row align-items-start justify-content-between gap-2 mb-4">
            <div>
                <h3 class="card-title mb-1">Two-Factor Authentication</h3>
                <div class="text-secondary small">Tambahkan kode dari Google Authenticator atau aplikasi TOTP setiap kali login.</div>
                @include('starter-shared::components.google-authenticator-brand', ['theme' => $theme, 'class' => 'mt-3'])
            </div>
            <span class="badge flex-shrink-0 {{ $theme === 'vuexy' ? ($login->hasTwoFactorAuthenticationEnabled() ? 'bg-label-success' : 'bg-label-secondary') : ($login->hasTwoFactorAuthenticationEnabled() ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary') }}">
                {{ $login->hasTwoFactorAuthenticationEnabled() ? 'Aktif' : 'Belum aktif' }}
            </span>
        </div>

        @if ($twoFactorRecoveryCodes !== [])
            <div class="alert alert-warning d-block" role="status">
                <div class="w-100 overflow-hidden">
                    <div class="fw-medium">Simpan kode pemulihan sekarang</div>
                    <div class="small mb-3">Setiap kode hanya dapat dipakai satu kali. Kode ini tidak akan ditampilkan lagi setelah halaman dimuat ulang.</div>
                    <div class="overflow-auto pb-1" data-starter-region="two-factor-recovery-codes" tabindex="0" aria-label="Kode pemulihan authenticator">
                        <div class="d-flex flex-nowrap gap-2 font-monospace">
                            @foreach ($twoFactorRecoveryCodes as $recoveryCode)
                                <span class="badge flex-shrink-0 text-nowrap bg-light text-dark px-3 py-2">{{ $recoveryCode }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($twoFactorSetupActive)
            <div class="alert alert-info mb-4" role="note">
                <div>
                    <div class="fw-medium mb-1">1. Pindai QR code</div>
                    <div class="small">Di Google Authenticator pilih tambah akun, lalu pindai QR code berikut.</div>
                </div>
            </div>
            <div class="row g-4 align-items-start mb-3" data-starter-region="two-factor-setup">
                <div class="col-12 col-lg-4 text-center" data-starter-region="two-factor-qr">
                    <div class="d-inline-flex bg-white border rounded p-3 shadow-sm">
                        <img src="{{ $twoFactorQrCode }}" width="184" height="184" alt="QR code aktivasi authenticator">
                    </div>
                </div>
                <div class="col-12 col-lg-8" data-starter-region="two-factor-verification">
                    <div class="{{ $theme === 'vuexy' ? 'alert alert-warning' : 'bg-light border rounded p-3' }} mb-4" data-starter-region="two-factor-manual-key">
                        <div class="text-secondary small mb-2">Tidak dapat memindai? Masukkan setup key ini secara manual:</div>
                        <div class="font-monospace fw-semibold text-break">{{ $twoFactorSetupKey }}</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-8 col-xl-7">
                            <label class="form-label" for="two-factor-confirm-code">2. Masukkan kode 6 digit</label>
                            <input type="text" class="form-control font-monospace @error('twoFactorForm.code') is-invalid @enderror" id="two-factor-confirm-code" wire:model.defer="twoFactorForm.code" wire:keydown.enter.prevent="confirmTwoFactorSetup" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" placeholder="000000">
                            @error('twoFactorForm.code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="d-grid d-sm-flex gap-2 mt-3" data-starter-region="two-factor-actions" style="max-width: 30rem;">
                        <button type="button" class="btn btn-primary px-4" style="flex: 1 1 0;" wire:click="confirmTwoFactorSetup" wire:loading.attr="disabled" wire:target="confirmTwoFactorSetup">
                            <span wire:loading.remove wire:target="confirmTwoFactorSetup">Aktifkan Authenticator</span>
                            <span wire:loading wire:target="confirmTwoFactorSetup">Memverifikasi...</span>
                        </button>
                        <button type="button" class="btn px-4 {{ $theme === 'vuexy' ? 'btn-label-secondary' : 'btn-outline-secondary' }}" style="flex: 1 1 0;" wire:click="cancelTwoFactorSetup" wire:loading.attr="disabled">Batalkan Aktivasi</button>
                    </div>
                </div>
            </div>
        @elseif ($login->hasTwoFactorAuthenticationEnabled())
            <div class="alert alert-success" role="status">Authenticator aktif. Login berikutnya akan meminta kode setelah password{{ config('starter.auth.login_otp_enabled') ? ' dan OTP email' : '' }} berhasil diverifikasi.</div>
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label" for="two-factor-disable-password">Password Saat Ini</label>
                    <input type="password" class="form-control @error('twoFactorDisableForm.password') is-invalid @enderror" id="two-factor-disable-password" wire:model.defer="twoFactorDisableForm.password" wire:keydown.enter.prevent autocomplete="current-password">
                    @error('twoFactorDisableForm.password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="two-factor-disable-code">Kode Authenticator</label>
                    <input type="text" class="form-control font-monospace @error('twoFactorDisableForm.code') is-invalid @enderror" id="two-factor-disable-code" wire:model.defer="twoFactorDisableForm.code" wire:keydown.enter.prevent inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" placeholder="000000">
                    @error('twoFactorDisableForm.code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-outline-danger w-100" wire:click="disableTwoFactorAuthentication" wire:loading.attr="disabled" wire:target="disableTwoFactorAuthentication">
                        <span wire:loading.remove wire:target="disableTwoFactorAuthentication">Nonaktifkan</span>
                        <span wire:loading wire:target="disableTwoFactorAuthentication">Memproses...</span>
                    </button>
                </div>
            </div>
        @else
            <div class="alert alert-info mb-4" role="note">
                <div class="w-100 small lh-base" style="min-width: 0; overflow-wrap: anywhere;">Setelah aktif, password saja tidak cukup untuk masuk. Siapkan Google Authenticator sebelum memulai.</div>
            </div>
            <div style="max-width: 46rem;" data-starter-region="two-factor-start">
                <label class="form-label" for="two-factor-password">Konfirmasi Password Saat Ini</label>
                <div class="row g-2 align-items-stretch">
                    <div class="col-12 col-sm">
                        <input type="password" class="form-control h-100 @error('twoFactorForm.password') is-invalid @enderror" style="min-height: 2.5rem;" id="two-factor-password" wire:model.defer="twoFactorForm.password" wire:keydown.enter.prevent="startTwoFactorSetup" autocomplete="current-password">
                    </div>
                    <div class="col-12 col-sm-auto d-grid">
                        <button type="button" class="btn btn-outline-primary px-4 py-2" style="min-height: 2.5rem; min-width: 9rem;" wire:click="startTwoFactorSetup" wire:loading.attr="disabled" wire:target="startTwoFactorSetup">
                            <span wire:loading.remove wire:target="startTwoFactorSetup">Mulai Aktivasi</span>
                            <span wire:loading wire:target="startTwoFactorSetup">Menyiapkan...</span>
                        </button>
                    </div>
                </div>
                @error('twoFactorForm.password')<div class="invalid-feedback d-block mt-1">{{ $message }}</div>@enderror
            </div>
        @endif
    </section>
@endif
@endif
