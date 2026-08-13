<div class="dashcode-users-page">
    @unless ($embedded)
        <div class="page-header mb-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="page-pretitle">Starter / Manajemen User</div>
                <h2 class="page-title">Manajemen User</h2>
                <div class="text-secondary">Kelola user aktif, arsip, pemulihan, dan penghapusan permanen.</div>
            </div>
            <a href="{{ route('starter.user-management.users.create') }}" class="btn btn-primary inline-flex items-center justify-center gap-2 self-start md:self-auto" data-starter-navigate>
                @include('starter.templates.layouts.icon', ['name' => 'user-plus'])
                <span>Tambah User</span>
            </a>
        </div>
    @endunless

    @if ($temporaryPassword)
        <div class="dashcode-alert dashcode-alert-warning" role="alert" data-temporary-credentials-alert>
            <span class="dashcode-alert-icon flex-shrink-0">
                @include('starter.templates.layouts.icon', ['name' => 'lock', 'class' => 'icon-sm'])
            </span>
            <div class="flex-fill">
                <h3 class="dashcode-alert-title">Simpan kredensial sementara ini sekarang</h3>
                <div>Username: <strong class="font-monospace">{{ $temporaryPasswordUsername }}</strong></div>
                <div>Password: <strong class="font-monospace">{{ $temporaryPassword }}</strong></div>
                <div class="small mt-1">Password tidak akan ditampilkan lagi dan pengguna wajib menggantinya saat login.</div>
            </div>
            <button type="button" class="dashcode-icon-button ms-auto" wire:click="dismissTemporaryPassword" aria-label="Tutup">
                @include('starter.templates.layouts.icon', ['name' => 'circle-x', 'class' => 'icon-sm'])
            </button>
        </div>
    @endif

    @if ($embedded)
        <livewire:starter.user-management.users-table />
    @else
        <div class="card dashcode-table-card">
            <livewire:starter.user-management.users-table />
        </div>
    @endif

    @include('starter.templates.components.danger-modal', [
        'id' => 'reset-user-password-modal',
        'title' => 'Reset password user?',
        'message' => filled($passwordResetUserName) ? 'Password sementara baru akan dibuat untuk '.$passwordResetUserName.'.' : 'Password sementara baru akan dibuat untuk user ini.',
        'confirmText' => 'Reset Password',
        'confirmAction' => 'resetSelectedPassword',
        'cancelAction' => 'cancelPasswordReset',
        'visible' => $passwordResetModalOpen,
        'dismissOnConfirm' => false,
    ])
    @if ($passwordResetModalOpen)
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
