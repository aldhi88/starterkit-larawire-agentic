<div class="dashcode-users-page">
    @unless ($embedded)
        <div class="page-header mb-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="page-title">Manajemen User</h2>
                <div class="text-secondary">Kelola user aktif, arsip, pemulihan, dan penghapusan permanen.</div>
            </div>
            <a href="{{ route('starter.user-management.users.create') }}" class="btn btn-primary inline-flex items-center justify-center gap-2 self-start md:self-auto" data-starter-navigate>
                @include('starter.templates.layouts.icon', ['name' => 'user-plus'])
                <span>Tambah User</span>
            </a>
        </div>
    @endunless

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
        'message' => filled($passwordResetUserName) ? 'Password sementara baru untuk '.$passwordResetUserName.' akan dikirim ke '.$passwordResetUserEmail.'.' : 'Password sementara baru akan dikirim ke email user ini.',
        'confirmText' => 'Reset dan Kirim Email',
        'loadingText' => 'Memproses email...',
        'confirmAction' => 'resetSelectedPassword',
        'cancelAction' => 'cancelPasswordReset',
        'visible' => $passwordResetModalOpen,
        'dismissOnConfirm' => false,
    ])
</div>
