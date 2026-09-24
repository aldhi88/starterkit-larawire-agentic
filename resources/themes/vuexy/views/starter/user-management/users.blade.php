<div>
    @unless ($embedded)
        <div class="page-header d-print-none mt-0 mb-3">
            <div class="row g-3 align-items-center">
                <div class="col">
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

    <livewire:starter.user-management.users-table />

    @include('starter.templates.components.danger-modal', [
        'id' => 'reset-user-password-modal',
        'title' => 'Reset password user?',
        'message' => filled($passwordResetUserName) ? 'Password sementara baru untuk '.$passwordResetUserName.' akan dikirim ke '.$passwordResetUserEmail.'.' : 'Password sementara baru akan dikirim ke email user ini.',
        'confirmText' => 'Reset dan Kirim Email',
        'loadingText' => 'Memproses email...',
        'confirmAction' => 'resetSelectedPassword',
        'cancelAction' => 'cancelPasswordReset',
        'visible' => $passwordResetModalOpen,
        'dismissOnConfirm' => false,
    ])

    @include('starter.templates.components.danger-modal', [
        'id' => 'reset-user-authenticator-modal',
        'title' => 'Reset authenticator user?',
        'message' => filled($authenticatorResetUserName) ? 'Authenticator dan seluruh kode pemulihan '.$authenticatorResetUserName.' akan dihapus. Seluruh sesi lamanya akan berakhir dan user harus mengaktifkan authenticator kembali.' : 'Authenticator dan seluruh kode pemulihan user akan dihapus.',
        'confirmText' => 'Reset Authenticator',
        'loadingText' => 'Mereset...',
        'confirmAction' => 'resetSelectedAuthenticator',
        'cancelAction' => 'cancelAuthenticatorReset',
        'visible' => $authenticatorResetModalOpen,
        'dismissOnConfirm' => false,
    ])
    @if ($passwordResetModalOpen || $authenticatorResetModalOpen)
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
