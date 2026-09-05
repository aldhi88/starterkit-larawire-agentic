<div class="dashcode-profile-page" x-data="{ activeTab: @js($activeTab) }">
    <div class="dashcode-page-heading" aria-label="Header halaman" data-starter-region="page-header">
            <div>
                <div class="page-pretitle">Starter / Profil Saya</div>
                <h2 class="page-title">Edit Profil Saya</h2>
            </div>
    </div>

    @if ($login->must_change_password)
        <div class="dashcode-alert dashcode-alert-warning mb-3" role="alert" data-starter-region="status-alert">
            <div class="dashcode-alert-content">
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

    <section class="mb-4" aria-label="Ringkasan akun" data-starter-region="account-summary">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
            <div class="flex min-w-0 items-center gap-4 rounded-[6px] border border-slate-600 bg-slate-900 p-4 text-white">
                <span class="avatar avatar-lg flex-shrink-0" style="background-image: url({{ $loginAvatarUrl }})"></span>
                <div class="min-w-0">
                    <div class="text-truncate text-lg font-medium text-white">{{ $login->name }}</div>
                    <div class="text-truncate text-sm text-slate-300" title="{{ $login->email }}">{{ $login->email }}</div>
                    <div class="mt-2">
                        <span class="badge bg-primary-500 bg-opacity-30 text-white">{{ $login->role?->name ?? 'Tanpa Role' }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4 rounded-[6px] border border-info-500 bg-[#E5F9FF] p-4">
                <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-info-700 text-white">
                    @include('starter.templates.layouts.icon', ['name' => 'shield-check', 'class' => 'm-0'])
                </span>
                <div class="min-w-0">
                    <div class="mb-1 text-sm font-medium text-slate-600">Email terverifikasi</div>
                    <div class="font-medium text-slate-900">{{ $login->email_verified_at?->format('d M Y H:i') ?? '-' }}</div>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-[6px] border border-primary-500 bg-[#EAE5FF] p-4">
                <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-primary-700 text-white">
                    @include('starter.templates.layouts.icon', ['name' => 'user-circle', 'class' => 'm-0'])
                </span>
                <div class="min-w-0">
                    <div class="mb-1 text-sm font-medium text-slate-600">Username</div>
                    <div class="text-truncate font-medium text-slate-900" title="{{ $login->username }}">{{ $login->username }}</div>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-[6px] border border-success-500 bg-[#EDFFE5] p-4">
                <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-success-700 text-white">
                    @include('starter.templates.layouts.icon', ['name' => 'history', 'class' => 'm-0'])
                </span>
                <div class="min-w-0">
                    <div class="mb-1 text-sm font-medium text-slate-600">Login terakhir</div>
                    <div class="font-medium text-slate-900">{{ $login->last_login_at?->format('d M Y H:i') ?? '-' }}</div>
                </div>
            </div>
        </div>
    </section>

    <div class="dashcode-profile-layout">
        <div>
            <aside class="card dashcode-profile-menu-card" aria-label="Pengaturan akun" data-starter-region="section-navigation">
                <div class="card-body">
                    <h3 class="dashcode-profile-menu-title">Pengaturan Akun</h3>
                    <p class="dashcode-profile-menu-copy">Kelola identitas dan keamanan akun Anda.</p>
                    <div class="dashcode-profile-nav" id="profile-settings-tabs" role="tablist">
                        <a
                            href="#account-details"
                            class="dashcode-profile-nav-item {{ $login->must_change_password ? 'disabled' : '' }}"
                            @unless ($login->must_change_password) x-on:click.prevent="activeTab = 'account-details'" @endunless
                            x-bind:class="{ active: activeTab === 'account-details' }"
                            role="tab"
                            aria-controls="account-details"
                            x-bind:aria-selected="activeTab === 'account-details'"
                            aria-disabled="{{ $login->must_change_password ? 'true' : 'false' }}"
                            @if ($login->must_change_password) tabindex="-1" @endif
                        >
                            @include('starter.templates.layouts.icon', ['name' => 'user-circle'])
                            Detail Akun
                        </a>
                        <a
                            href="#security"
                            class="dashcode-profile-nav-item"
                            x-on:click.prevent="activeTab = 'security'"
                            x-bind:class="{ active: activeTab === 'security' }"
                            role="tab"
                            aria-controls="security"
                            x-bind:aria-selected="activeTab === 'security'"
                        >
                            @include('starter.templates.layouts.icon', ['name' => 'lock'])
                            Keamanan
                            @if ($login->must_change_password)
                                <span class="badge bg-warning-lt text-warning dashcode-push-right">Wajib</span>
                            @endif
                        </a>
                    </div>
                </div>
            </aside>
        </div>

        <div data-starter-region="section-content">
            <div class="tab-content">
                <form
                    id="account-details"
                    class="card tab-pane"
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
                                    <input type="file" id="profile-photo-upload" class="dashcode-visually-hidden @error('profilePhotoUpload') is-invalid @enderror" wire:model="profilePhotoUpload" accept="image/*">
                                    <button type="button" class="btn btn-ghost-danger" data-starter-modal-open="#delete-profile-photo-modal">
                                        Hapus Foto Profil
                                    </button>
                                </div>
                                <div class="text-secondary small mt-2">Gunakan gambar persegi agar foto tampil proporsional.</div>
                                @error('profilePhotoUpload') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                                <div class="text-secondary small mt-2" wire:loading wire:target="profilePhotoUpload">Mengunggah...</div>
                            </div>
                        </div>

                        <div class="dashcode-form-grid dashcode-form-grid-2">
                            <div>
                                <label class="form-label" for="profile-display-name">Nama Tampilan</label>
                                <input type="text" id="profile-display-name" class="form-control @error('accountForm.name') is-invalid @enderror" wire:model.defer="accountForm.name" autocomplete="name">
                                @error('accountForm.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label" for="profile-email">Email Login</label>
                                <input type="email" id="profile-email" class="form-control @error('accountForm.email') is-invalid @enderror" wire:model.defer="accountForm.email" autocomplete="email">
                                @error('accountForm.email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <footer class="dashcode-profile-actions">
                        <button type="submit" class="btn btn-primary">
                            @include('starter.templates.layouts.icon', ['name' => 'check'])
                            Simpan Akun
                        </button>
                    </footer>
                </form>

                <form
                    id="security"
                    class="card tab-pane"
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
                        <div class="dashcode-form-grid dashcode-form-grid-2">
                            <div>
                                <label class="form-label" for="profile-current-password">Password Saat Ini</label>
                                <div class="relative" x-data="{ visible: false }">
                                    <input
                                        :type="visible ? 'text' : 'password'"
                                        type="password"
                                        id="profile-current-password"
                                        class="form-control !pr-12 @error('passwordForm.current_password') is-invalid @enderror"
                                        wire:model.defer="passwordForm.current_password"
                                        autocomplete="current-password"
                                        @error('passwordForm.current_password') aria-invalid="true" aria-describedby="profile-current-password-error" @enderror
                                    >
                                    <span class="absolute right-0 top-1/2 h-full w-9 -translate-y-1/2 border-l border-l-slate-200 flex items-center justify-center">
                                        <button type="button" class="text-secondary" x-on:click="visible = ! visible" x-bind:aria-pressed="visible" x-bind:aria-label="visible ? 'Sembunyikan Password Saat Ini' : 'Tampilkan Password Saat Ini'">
                                            <span x-show="! visible">@include('starter.templates.layouts.icon', ['name' => 'eye', 'class' => 'icon-sm m-0'])</span>
                                            <span x-show="visible" x-cloak>@include('starter.templates.layouts.icon', ['name' => 'eye-off', 'class' => 'icon-sm m-0'])</span>
                                        </button>
                                    </span>
                                </div>
                                @error('passwordForm.current_password') <div id="profile-current-password-error" class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <div class="dashcode-profile-security-guide dashcode-full-height">
                                    <span class="dashcode-profile-security-guide-icon">
                                        @include('starter.templates.layouts.icon', ['name' => 'info-circle', 'class' => 'm-0'])
                                    </span>
                                    <div>
                                        <div class="dashcode-profile-security-guide-title">Syarat password baru</div>
                                        <div class="dashcode-profile-security-guide-copy">Minimal 10 karakter serta memiliki huruf besar, huruf kecil, dan angka.</div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="form-label" for="profile-new-password">Password Baru</label>
                                <div class="relative" x-data="{ visible: false }">
                                    <input
                                        :type="visible ? 'text' : 'password'"
                                        type="password"
                                        id="profile-new-password"
                                        class="form-control !pr-12 @error('passwordForm.password') is-invalid @enderror"
                                        wire:model.defer="passwordForm.password"
                                        autocomplete="new-password"
                                        @error('passwordForm.password') aria-invalid="true" aria-describedby="profile-new-password-error" @enderror
                                    >
                                    <span class="absolute right-0 top-1/2 h-full w-9 -translate-y-1/2 border-l border-l-slate-200 flex items-center justify-center">
                                        <button type="button" class="text-secondary" x-on:click="visible = ! visible" x-bind:aria-pressed="visible" x-bind:aria-label="visible ? 'Sembunyikan Password Baru' : 'Tampilkan Password Baru'">
                                            <span x-show="! visible">@include('starter.templates.layouts.icon', ['name' => 'eye', 'class' => 'icon-sm m-0'])</span>
                                            <span x-show="visible" x-cloak>@include('starter.templates.layouts.icon', ['name' => 'eye-off', 'class' => 'icon-sm m-0'])</span>
                                        </button>
                                    </span>
                                </div>
                                @error('passwordForm.password') <div id="profile-new-password-error" class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label" for="profile-password-confirmation">Konfirmasi Password Baru</label>
                                <div class="relative" x-data="{ visible: false }">
                                    <input
                                        :type="visible ? 'text' : 'password'"
                                        type="password"
                                        id="profile-password-confirmation"
                                        class="form-control !pr-12 @error('passwordForm.password_confirmation') is-invalid @enderror"
                                        wire:model.defer="passwordForm.password_confirmation"
                                        autocomplete="new-password"
                                        @error('passwordForm.password_confirmation') aria-invalid="true" aria-describedby="profile-password-confirmation-error" @enderror
                                    >
                                    <span class="absolute right-0 top-1/2 h-full w-9 -translate-y-1/2 border-l border-l-slate-200 flex items-center justify-center">
                                        <button type="button" class="text-secondary" x-on:click="visible = ! visible" x-bind:aria-pressed="visible" x-bind:aria-label="visible ? 'Sembunyikan Konfirmasi Password' : 'Tampilkan Konfirmasi Password'">
                                            <span x-show="! visible">@include('starter.templates.layouts.icon', ['name' => 'eye', 'class' => 'icon-sm m-0'])</span>
                                            <span x-show="visible" x-cloak>@include('starter.templates.layouts.icon', ['name' => 'eye-off', 'class' => 'icon-sm m-0'])</span>
                                        </button>
                                    </span>
                                </div>
                                @error('passwordForm.password_confirmation') <div id="profile-password-confirmation-error" class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <footer class="dashcode-profile-actions">
                        <button type="submit" class="btn btn-primary">
                            @include('starter.templates.layouts.icon', ['name' => 'lock'])
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
