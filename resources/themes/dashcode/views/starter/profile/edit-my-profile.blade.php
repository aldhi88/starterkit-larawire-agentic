<div class="dashcode-profile-page" x-data="{ activeTab: @js($activeTab) }">
    <div class="page-header mt-0 mb-3" aria-label="Header halaman">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Starter / Profil Saya</div>
                <h2 class="page-title">Edit Profil Saya</h2>
            </div>
        </div>
    </div>

    @if ($login->must_change_password)
        <div class="dashcode-alert dashcode-alert-warning mb-3" role="alert">
            <div class="d-flex gap-3">
                <span class="dashcode-alert-icon flex-shrink-0">
                    @include('starter.templates.layouts.icon', ['name' => 'alert-triangle', 'class' => 'm-0'])
                </span>
                <div>
                    <h3 class="dashcode-alert-title">Password sementara harus diganti</h3>
                    <div>
                        Masukkan password sementara yang diberikan admin pada kolom <strong>Password Saat Ini</strong>,
                        kemudian buat <strong>Password Baru</strong>. Anda dapat melanjutkan ke halaman lain setelah password berhasil diubah.
                    </div>
                </div>
            </div>
        </div>
    @endif

    <section class="card dashcode-profile-overview mb-4" aria-label="Ringkasan akun">
        <div class="card-body">
            <div class="dashcode-profile-overview-layout">
                <div class="dashcode-profile-identity">
                    <span class="avatar avatar-lg flex-shrink-0" style="background-image: url({{ $loginAvatarUrl }})"></span>
                    <div class="min-w-0">
                        <div class="dashcode-profile-name text-truncate">{{ $login->name }}</div>
                        <div class="text-secondary text-truncate" title="{{ $login->email }}">{{ $login->email }}</div>
                        <div class="mt-2">
                            <span class="badge bg-primary-lt">{{ $login->role?->name ?? 'Tanpa Role' }}</span>
                        </div>
                    </div>
                </div>

                <div class="dashcode-profile-summary-grid">
                    <div class="dashcode-profile-summary-item">
                        <span class="dashcode-profile-summary-icon dashcode-profile-summary-icon-info">
                            @include('starter.templates.layouts.icon', ['name' => 'shield-check', 'class' => 'm-0'])
                        </span>
                        <div class="min-w-0">
                            <div class="dashcode-profile-summary-label">Email terverifikasi</div>
                            <div class="dashcode-profile-summary-value">{{ $login->email_verified_at?->format('d M Y H:i') ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="dashcode-profile-summary-item">
                        <span class="dashcode-profile-summary-icon dashcode-profile-summary-icon-primary">
                            @include('starter.templates.layouts.icon', ['name' => 'user-circle', 'class' => 'm-0'])
                        </span>
                        <div class="min-w-0">
                            <div class="dashcode-profile-summary-label">Username</div>
                            <div class="dashcode-profile-summary-value text-truncate" title="{{ $login->username }}">{{ $login->username }}</div>
                        </div>
                    </div>
                    <div class="dashcode-profile-summary-item">
                        <span class="dashcode-profile-summary-icon dashcode-profile-summary-icon-success">
                            @include('starter.templates.layouts.icon', ['name' => 'history', 'class' => 'm-0'])
                        </span>
                        <div class="min-w-0">
                            <div class="dashcode-profile-summary-label">Login terakhir</div>
                            <div class="dashcode-profile-summary-value">{{ $login->last_login_at?->format('d M Y H:i') ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-3">
            <aside class="card dashcode-profile-menu-card" aria-label="Pengaturan akun">
                <div class="card-body">
                    <h3 class="dashcode-profile-menu-title">Pengaturan Akun</h3>
                    <p class="dashcode-profile-menu-copy">Kelola identitas dan keamanan akun Anda.</p>
                    <div class="dashcode-profile-nav" id="profile-settings-tabs" role="tablist">
                        <a
                            href="#account-details"
                            class="dashcode-profile-nav-item d-flex align-items-center {{ $login->must_change_password ? 'disabled' : '' }}"
                            @unless ($login->must_change_password) x-on:click.prevent="activeTab = 'account-details'" @endunless
                            x-bind:class="{ active: activeTab === 'account-details' }"
                            role="tab"
                            aria-controls="account-details"
                            x-bind:aria-selected="activeTab === 'account-details'"
                            aria-disabled="{{ $login->must_change_password ? 'true' : 'false' }}"
                            @if ($login->must_change_password) tabindex="-1" @endif
                        >
                            @include('starter.templates.layouts.icon', ['name' => 'user-circle', 'class' => 'me-2'])
                            Detail Akun
                        </a>
                        <a
                            href="#security"
                            class="dashcode-profile-nav-item d-flex align-items-center"
                            x-on:click.prevent="activeTab = 'security'"
                            x-bind:class="{ active: activeTab === 'security' }"
                            role="tab"
                            aria-controls="security"
                            x-bind:aria-selected="activeTab === 'security'"
                        >
                            @include('starter.templates.layouts.icon', ['name' => 'lock', 'class' => 'me-2'])
                            Keamanan
                            @if ($login->must_change_password)
                                <span class="badge bg-warning-lt text-warning ms-auto">Wajib</span>
                            @endif
                        </a>
                    </div>
                </div>
            </aside>
        </div>

        <div class="col-12 col-lg-9">
            <div class="tab-content">
                <form
                    id="account-details"
                    class="card tab-pane fade"
                    x-bind:class="{ 'show active': activeTab === 'account-details' }"
                    x-show="activeTab === 'account-details'"
                    x-cloak
                    role="tabpanel"
                    wire:submit="saveAccount"
                >
                    <header class="dashcode-profile-form-header">
                        <div>
                            <h3 class="dashcode-profile-form-title">Detail Akun</h3>
                            <p class="dashcode-profile-form-copy">Perbarui foto, nama tampilan, dan email yang digunakan untuk login.</p>
                        </div>
                    </header>
                    <div class="card-body">
                        <div class="dashcode-profile-photo-block mb-4">
                            <span class="avatar avatar-xl flex-shrink-0" style="background-image: url({{ $profilePhotoPreviewUrl }})"></span>
                            <div class="dashcode-profile-photo-actions">
                                <div class="btn-list">
                                    <label class="btn btn-outline-primary mb-0" for="profile-photo-upload">
                                        Ganti Foto Profil
                                    </label>
                                    <input type="file" id="profile-photo-upload" class="d-none @error('profilePhotoUpload') is-invalid @enderror" wire:model="profilePhotoUpload" accept="image/*">
                                    <button type="button" class="btn btn-ghost-danger" data-starter-modal-open="#delete-profile-photo-modal">
                                        Hapus Foto Profil
                                    </button>
                                </div>
                                <div class="text-secondary small mt-2">Gunakan gambar persegi agar foto tampil proporsional.</div>
                                @error('profilePhotoUpload') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                                <div class="text-secondary small mt-2" wire:loading wire:target="profilePhotoUpload">Mengunggah...</div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="profile-display-name">Nama Tampilan</label>
                                <input type="text" id="profile-display-name" class="form-control @error('accountForm.name') is-invalid @enderror" wire:model.defer="accountForm.name" autocomplete="name">
                                @error('accountForm.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="profile-email">Email Login</label>
                                <input type="email" id="profile-email" class="form-control @error('accountForm.email') is-invalid @enderror" wire:model.defer="accountForm.email" autocomplete="email">
                                @error('accountForm.email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <footer class="dashcode-profile-actions">
                        <button type="submit" class="btn btn-primary">
                            @include('starter.templates.layouts.icon', ['name' => 'check', 'class' => 'me-1'])
                            Simpan Akun
                        </button>
                    </footer>
                </form>

                <form
                    id="security"
                    class="card tab-pane fade"
                    x-bind:class="{ 'show active': activeTab === 'security' }"
                    x-show="activeTab === 'security'"
                    x-cloak
                    role="tabpanel"
                    wire:submit="changePassword"
                >
                    <header class="dashcode-profile-form-header">
                        <div>
                            <h3 class="dashcode-profile-form-title">Keamanan Akun</h3>
                            <p class="dashcode-profile-form-copy">Gunakan password unik yang tidak dipakai pada aplikasi lain.</p>
                        </div>
                        <span class="dashcode-profile-form-icon">
                            @include('starter.templates.layouts.icon', ['name' => 'shield-lock', 'class' => 'm-0'])
                        </span>
                    </header>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="profile-current-password">Password Saat Ini</label>
                                <div class="input-group input-group-flat" x-data="{ visible: false }">
                                    <input
                                        :type="visible ? 'text' : 'password'"
                                        type="password"
                                        id="profile-current-password"
                                        class="form-control @error('passwordForm.current_password') is-invalid @enderror"
                                        wire:model.defer="passwordForm.current_password"
                                        autocomplete="current-password"
                                        @error('passwordForm.current_password') aria-invalid="true" aria-describedby="profile-current-password-error" @enderror
                                    >
                                    <span class="input-group-text">
                                        <button type="button" class="btn btn-link btn-sm p-0 text-secondary" x-on:click="visible = ! visible" x-bind:aria-pressed="visible" x-bind:aria-label="visible ? 'Sembunyikan Password Saat Ini' : 'Tampilkan Password Saat Ini'">
                                            <span x-show="! visible">@include('starter.templates.layouts.icon', ['name' => 'eye', 'class' => 'icon-sm m-0'])</span>
                                            <span x-show="visible" style="display: none;">@include('starter.templates.layouts.icon', ['name' => 'eye-off', 'class' => 'icon-sm m-0'])</span>
                                        </button>
                                    </span>
                                </div>
                                @error('passwordForm.current_password') <div id="profile-current-password-error" class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <div class="dashcode-profile-security-guide h-100">
                                    <span class="dashcode-profile-security-guide-icon">
                                        @include('starter.templates.layouts.icon', ['name' => 'info-circle', 'class' => 'm-0'])
                                    </span>
                                    <div>
                                        <div class="dashcode-profile-security-guide-title">Syarat password baru</div>
                                        <div class="dashcode-profile-security-guide-copy">Minimal 10 karakter serta memiliki huruf besar, huruf kecil, dan angka.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="profile-new-password">Password Baru</label>
                                <div class="input-group input-group-flat" x-data="{ visible: false }">
                                    <input
                                        :type="visible ? 'text' : 'password'"
                                        type="password"
                                        id="profile-new-password"
                                        class="form-control @error('passwordForm.password') is-invalid @enderror"
                                        wire:model.defer="passwordForm.password"
                                        autocomplete="new-password"
                                        @error('passwordForm.password') aria-invalid="true" aria-describedby="profile-new-password-error" @enderror
                                    >
                                    <span class="input-group-text">
                                        <button type="button" class="btn btn-link btn-sm p-0 text-secondary" x-on:click="visible = ! visible" x-bind:aria-pressed="visible" x-bind:aria-label="visible ? 'Sembunyikan Password Baru' : 'Tampilkan Password Baru'">
                                            <span x-show="! visible">@include('starter.templates.layouts.icon', ['name' => 'eye', 'class' => 'icon-sm m-0'])</span>
                                            <span x-show="visible" style="display: none;">@include('starter.templates.layouts.icon', ['name' => 'eye-off', 'class' => 'icon-sm m-0'])</span>
                                        </button>
                                    </span>
                                </div>
                                @error('passwordForm.password') <div id="profile-new-password-error" class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="profile-password-confirmation">Konfirmasi Password Baru</label>
                                <div class="input-group input-group-flat" x-data="{ visible: false }">
                                    <input
                                        :type="visible ? 'text' : 'password'"
                                        type="password"
                                        id="profile-password-confirmation"
                                        class="form-control @error('passwordForm.password_confirmation') is-invalid @enderror"
                                        wire:model.defer="passwordForm.password_confirmation"
                                        autocomplete="new-password"
                                        @error('passwordForm.password_confirmation') aria-invalid="true" aria-describedby="profile-password-confirmation-error" @enderror
                                    >
                                    <span class="input-group-text">
                                        <button type="button" class="btn btn-link btn-sm p-0 text-secondary" x-on:click="visible = ! visible" x-bind:aria-pressed="visible" x-bind:aria-label="visible ? 'Sembunyikan Konfirmasi Password' : 'Tampilkan Konfirmasi Password'">
                                            <span x-show="! visible">@include('starter.templates.layouts.icon', ['name' => 'eye', 'class' => 'icon-sm m-0'])</span>
                                            <span x-show="visible" style="display: none;">@include('starter.templates.layouts.icon', ['name' => 'eye-off', 'class' => 'icon-sm m-0'])</span>
                                        </button>
                                    </span>
                                </div>
                                @error('passwordForm.password_confirmation') <div id="profile-password-confirmation-error" class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <footer class="dashcode-profile-actions">
                        <button type="submit" class="btn btn-primary">
                            @include('starter.templates.layouts.icon', ['name' => 'lock', 'class' => 'me-1'])
                            Ubah Password
                        </button>
                    </footer>
                </form>
            </div>
        </div>
    </div>

    @include('starter.templates.components.danger-modal', [
        'id' => 'delete-profile-photo-modal',
        'title' => 'Hapus foto profil?',
        'message' => 'Foto profil saat ini akan diganti dengan foto default.',
        'confirmText' => 'Hapus Foto Profil',
        'confirmAction' => 'resetProfilePhoto',
        'dismissOnConfirm' => true,
    ])
</div>
