<div>
    @unless ($embedded)
        <div class="page-header d-print-none mt-0 mb-3">
            <div class="row g-3 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Starter / Manajemen User</div>
                    <h2 class="page-title">Manajemen User</h2>
                    <div class="text-secondary">Kelola user aktif, arsip, pemulihan, dan penghapusan permanen.</div>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('starter.user-management.users.create') }}" class="btn btn-primary" data-starter-navigate>
                        @include('starter.templates.layouts.icon', ['name' => 'user-plus', 'class' => 'icon-sm me-1'])
                        Tambah User
                    </a>
                </div>
            </div>
        </div>
    @endunless

    @if ($temporaryPassword)
        <div class="alert alert-warning alert-dismissible" role="alert" data-temporary-credentials-alert>
            <h3 class="alert-title">Simpan kredensial sementara ini sekarang</h3>
            <div>Username: <strong class="font-monospace">{{ $temporaryPasswordUsername }}</strong></div>
            <div>Password: <strong class="font-monospace">{{ $temporaryPassword }}</strong></div>
            <div class="small mt-1">Password tidak akan ditampilkan lagi dan pengguna wajib menggantinya saat login.</div>
            <button type="button" class="btn-close" wire:click="dismissTemporaryPassword" aria-label="Tutup"></button>
        </div>
    @endif

    <livewire:starter.user-management.users-table />

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
