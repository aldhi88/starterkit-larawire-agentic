@if (config('starter.auth.login_two_factor_enabled', true))
@php($theme = \Aldhi88\StarterKit\Support\Starter\StarterTheme::key())

@if ($theme === 'dashcode')
    <section class="card-body border-t border-slate-200" data-starter-region="two-factor-security">
        <div class="flex items-start justify-between gap-2 mb-4">
            <div>
                <h3 class="dashcode-profile-form-title">Two-Factor Authentication</h3>
                <p class="dashcode-profile-form-copy">Tambahkan kode dari Google Authenticator atau aplikasi TOTP setiap kali login.</p>
            </div>
            <span class="badge flex-none whitespace-nowrap {{ $login->hasTwoFactorAuthenticationEnabled() ? 'bg-success-500 text-white' : 'bg-slate-100 text-slate-600' }}">
                {{ $login->hasTwoFactorAuthenticationEnabled() ? 'Aktif' : 'Belum aktif' }}
            </span>
        </div>

        @if ($twoFactorRecoveryCodes !== [])
            <div class="dashcode-alert dashcode-alert-warning mb-4" role="status">
                <div class="font-medium">Simpan kode pemulihan sekarang</div>
                <p class="text-sm mt-1 mb-3">Setiap kode hanya dapat dipakai satu kali. Kode ini tidak akan ditampilkan lagi setelah halaman dimuat ulang.</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 font-mono">
                    @foreach ($twoFactorRecoveryCodes as $recoveryCode)
                        <span class="badge bg-white text-slate-800 text-center py-2">{{ $recoveryCode }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($twoFactorSetupActive)
            <div class="dashcode-alert dashcode-alert-info mb-4" role="note">
                <div class="font-medium">1. Pindai QR code</div>
                <p class="text-sm mt-1">Di Google Authenticator pilih tambah akun, lalu pindai QR code berikut.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 items-center md:grid-cols-2">
                <div class="text-center">
                    <img src="{{ $twoFactorQrCode }}" width="208" height="208" class="inline-block rounded border border-slate-200 bg-white p-2" alt="QR code aktivasi authenticator">
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Tidak dapat memindai? Masukkan setup key ini secara manual:</p>
                    <div class="font-mono font-semibold break-all mb-4">{{ $twoFactorSetupKey }}</div>
                    <label class="form-label" for="two-factor-confirm-code">2. Masukkan kode 6 digit</label>
                    <input type="text" class="form-control py-2 font-mono @error('twoFactorForm.code') is-invalid @enderror" id="two-factor-confirm-code" wire:model.defer="twoFactorForm.code" wire:keydown.enter.prevent="confirmTwoFactorSetup" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" placeholder="000000">
                    @error('twoFactorForm.code')<div class="invalid-feedback block">{{ $message }}</div>@enderror
                    <div class="flex flex-wrap gap-2 mt-3">
                        <button type="button" class="btn btn-primary" wire:click="confirmTwoFactorSetup" wire:loading.attr="disabled" wire:target="confirmTwoFactorSetup">
                            <span wire:loading.remove wire:target="confirmTwoFactorSetup">Aktifkan Authenticator</span>
                            <span wire:loading wire:target="confirmTwoFactorSetup">Memverifikasi...</span>
                        </button>
                        <button type="button" class="btn btn-outline-dark" wire:click="cancelTwoFactorSetup" wire:loading.attr="disabled">Batal</button>
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
            <div class="dashcode-alert dashcode-alert-info mb-4" role="note">Setelah aktif, password saja tidak cukup untuk masuk. Siapkan Google Authenticator sebelum memulai.</div>
            <div class="dashcode-form-grid dashcode-form-grid-2 items-end">
                <div>
                    <label class="form-label" for="two-factor-password">Konfirmasi Password Saat Ini</label>
                    <input type="password" class="form-control @error('twoFactorForm.password') is-invalid @enderror" id="two-factor-password" wire:model.defer="twoFactorForm.password" wire:keydown.enter.prevent="startTwoFactorSetup" autocomplete="current-password">
                    @error('twoFactorForm.password')<div class="invalid-feedback block">{{ $message }}</div>@enderror
                </div>
                <div>
                    <button type="button" class="btn btn-outline-primary" wire:click="startTwoFactorSetup" wire:loading.attr="disabled" wire:target="startTwoFactorSetup">
                        <span wire:loading.remove wire:target="startTwoFactorSetup">Mulai Aktivasi</span>
                        <span wire:loading wire:target="startTwoFactorSetup">Menyiapkan...</span>
                    </button>
                </div>
            </div>
        @endif
    </section>
@else
    <section class="card-body border-top" data-starter-region="two-factor-security">
        <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-2 mb-3">
            <div>
                <h3 class="card-title mb-1">Two-Factor Authentication</h3>
                <div class="text-secondary small">Tambahkan kode dari Google Authenticator atau aplikasi TOTP setiap kali login.</div>
            </div>
            <span class="badge {{ $login->hasTwoFactorAuthenticationEnabled() ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary' }}">
                {{ $login->hasTwoFactorAuthenticationEnabled() ? 'Aktif' : 'Belum aktif' }}
            </span>
        </div>

        @if ($twoFactorRecoveryCodes !== [])
            <div class="alert alert-warning" role="status">
                <div class="fw-medium">Simpan kode pemulihan sekarang</div>
                <div class="small mb-3">Setiap kode hanya dapat dipakai satu kali. Kode ini tidak akan ditampilkan lagi setelah halaman dimuat ulang.</div>
                <div class="row g-2 font-monospace">
                    @foreach ($twoFactorRecoveryCodes as $recoveryCode)
                        <div class="col-6 col-md-3"><span class="badge bg-light text-dark w-100 py-2">{{ $recoveryCode }}</span></div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($twoFactorSetupActive)
            <div class="alert alert-info" role="note">
                <div class="fw-medium mb-1">1. Pindai QR code</div>
                <div class="small">Di Google Authenticator pilih tambah akun, lalu pindai QR code berikut.</div>
            </div>
            <div class="row g-3 align-items-center mb-3">
                <div class="col-12 col-md-auto text-center">
                    <img src="{{ $twoFactorQrCode }}" width="208" height="208" class="img-thumbnail bg-white" alt="QR code aktivasi authenticator">
                </div>
                <div class="col">
                    <div class="text-secondary small mb-1">Tidak dapat memindai? Masukkan setup key ini secara manual:</div>
                    <div class="font-monospace fw-semibold text-break mb-3">{{ $twoFactorSetupKey }}</div>
                    <label class="form-label" for="two-factor-confirm-code">2. Masukkan kode 6 digit</label>
                    <input type="text" class="form-control font-monospace @error('twoFactorForm.code') is-invalid @enderror" id="two-factor-confirm-code" wire:model.defer="twoFactorForm.code" wire:keydown.enter.prevent="confirmTwoFactorSetup" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" placeholder="000000">
                    @error('twoFactorForm.code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="btn-list mt-3">
                        <button type="button" class="btn btn-primary" wire:click="confirmTwoFactorSetup" wire:loading.attr="disabled" wire:target="confirmTwoFactorSetup">
                            <span wire:loading.remove wire:target="confirmTwoFactorSetup">Aktifkan Authenticator</span>
                            <span wire:loading wire:target="confirmTwoFactorSetup">Memverifikasi...</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" wire:click="cancelTwoFactorSetup" wire:loading.attr="disabled">Batal</button>
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
            <div class="alert alert-info" role="note">Setelah aktif, password saja tidak cukup untuk masuk. Siapkan Google Authenticator sebelum memulai.</div>
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label" for="two-factor-password">Konfirmasi Password Saat Ini</label>
                    <input type="password" class="form-control @error('twoFactorForm.password') is-invalid @enderror" id="two-factor-password" wire:model.defer="twoFactorForm.password" wire:keydown.enter.prevent="startTwoFactorSetup" autocomplete="current-password">
                    @error('twoFactorForm.password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-outline-primary w-100" wire:click="startTwoFactorSetup" wire:loading.attr="disabled" wire:target="startTwoFactorSetup">
                        <span wire:loading.remove wire:target="startTwoFactorSetup">Mulai Aktivasi</span>
                        <span wire:loading wire:target="startTwoFactorSetup">Menyiapkan...</span>
                    </button>
                </div>
            </div>
        @endif
    </section>
@endif
@endif
