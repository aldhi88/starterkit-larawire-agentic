<div class="dashcode-roles-page">
    @unless ($embedded)
        <div class="page-header mb-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between" aria-label="Header halaman">
            <div>
                <div class="page-pretitle">Starter / Manajemen User</div>
                <h2 class="page-title">Roles</h2>
                <div class="text-secondary">Kelola role tanpa mencampurkan daftar dengan form pengaturan akses.</div>
            </div>
            <a href="{{ route('starter.settings.roles.create') }}" class="btn btn-primary inline-flex items-center justify-center gap-2 self-start md:self-auto" data-starter-navigate>
                @include('starter.templates.layouts.icon', ['name' => 'file-plus'])
                <span>Tambah Role</span>
            </a>
        </div>
    @endunless

    @if ($embedded)
        <livewire:starter.user-management.roles-table />
    @else
        <div class="card dashcode-table-card">
            <livewire:starter.user-management.roles-table />
        </div>
    @endif

    @if ($roleUsersModalOpen)
        <div class="modal dashcode-detail-modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="role-users-modal-title" wire:click.self="closeRoleUsersModal">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h3 class="modal-title" id="role-users-modal-title">User dalam Role</h3>
                            <div class="text-secondary small">{{ $roleUsersRoleName }} · {{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal(count($roleUsers)) }} user</div>
                        </div>
                        <button type="button" class="dashcode-icon-button" aria-label="Tutup" wire:click="closeRoleUsersModal">
                            @include('starter.templates.layouts.icon', ['name' => 'circle-x', 'class' => 'icon-sm'])
                        </button>
                    </div>
                    <div class="modal-body overflow-auto" style="max-height: min(32rem, calc(100vh - 14rem));">
                        <div class="dashcode-stacked-list dashcode-stacked-list-flush">
                            @forelse ($roleUsers as $user)
                                <div class="dashcode-stacked-list-item px-0">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="avatar avatar-sm">{{ str($user['name'])->substr(0, 1)->upper() }}</span>
                                        <div class="overflow-hidden">
                                            <div class="fw-semibold text-truncate">{{ $user['name'] }}</div>
                                            <div class="text-secondary small text-truncate">{{ $user['email'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="dashcode-empty-state">
                                    <p class="dashcode-empty-state-title">Belum ada user</p>
                                    <p class="dashcode-empty-state-description">Role ini belum digunakan oleh akun login mana pun.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" wire:click="closeRoleUsersModal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    @if ($roleAccessModalOpen)
        <div class="modal dashcode-detail-modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="role-access-modal-title" wire:click.self="closeRoleAccessModal">
            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h3 class="modal-title" id="role-access-modal-title">Detail Akses Role</h3>
                            <div class="text-secondary small">
                                {{ $roleAccessRoleName }}
                                <span class="mx-1">·</span>
                                <span class="font-monospace">{{ $roleAccessRoleCode }}</span>
                            </div>
                        </div>
                        <button type="button" class="dashcode-icon-button" aria-label="Tutup" wire:click="closeRoleAccessModal">
                            @include('starter.templates.layouts.icon', ['name' => 'circle-x', 'class' => 'icon-sm'])
                        </button>
                    </div>
                    <div class="modal-body">
                        @if ($roleAccessIsFull)
                            <div class="dashcode-alert dashcode-alert-success" role="note">
                                <div class="d-flex gap-2">
                                    @include('starter.templates.layouts.icon', ['name' => 'shield-check', 'class' => 'icon-sm flex-shrink-0 mt-1'])
                                    <div>
                                        <div class="fw-semibold">Akses penuh role default</div>
                                        <div class="small">Superuser otomatis dapat mengakses seluruh app dan module yang terdaftar.</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (! $roleAccessIsFull)
                            <div class="card card-sm mb-3">
                                <div class="dashcode-stacked-list">
                                    <div class="dashcode-stacked-list-item">
                                        <div class="d-flex gap-2">
                                            @include('starter.templates.layouts.icon', ['name' => $roleAccessCanManageSettings ? 'settings' : 'lock', 'class' => 'icon-sm flex-shrink-0 mt-1 '.($roleAccessCanManageSettings ? 'text-azure' : 'text-secondary')])
                                            <div>
                                                <div class="fw-semibold">
                                                    {{ $roleAccessCanManageSettings ? 'Dapat mengakses Pengaturan' : 'Tidak dapat mengakses Pengaturan' }}
                                                </div>
                                                <div class="small text-secondary">
                                                    {{ $roleAccessCanManageSettings
                                                        ? 'Dapat mengelola role, user, dan profil perusahaan.'
                                                        : 'Tidak memiliki izin mengelola pusat Pengaturan.' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="dashcode-stacked-list-item">
                                        <div class="d-flex gap-2">
                                            @include('starter.templates.layouts.icon', ['name' => $roleAccessCanViewLogs ? 'history' : 'lock', 'class' => 'icon-sm flex-shrink-0 mt-1 '.($roleAccessCanViewLogs ? 'text-purple' : 'text-secondary')])
                                            <div>
                                                <div class="fw-semibold">
                                                    {{ $roleAccessCanViewLogs ? 'Dapat melihat Log Aktivitas' : 'Tidak dapat melihat Log Aktivitas' }}
                                                </div>
                                                <div class="small text-secondary">
                                                    {{ $roleAccessCanViewLogs
                                                        ? 'Dapat meninjau riwayat pembuatan, perubahan, dan penghapusan data.'
                                                        : 'Riwayat perubahan data tidak dapat dibuka oleh role ini.' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="vstack gap-3" data-role-access-detail>
                            @forelse ($roleAccessApps as $app)
                                <div class="border rounded overflow-hidden">
                                    <div class="flex items-center gap-2 bg-slate-50 px-3 py-2 border-bottom">
                                        @include('starter.templates.layouts.icon', ['name' => 'apps', 'class' => 'icon-sm text-primary flex-shrink-0'])
                                        <div class="fw-semibold">{{ $app['name'] }}</div>
                                        <span class="badge bg-secondary-lt ms-auto">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal(count($app['modules'])) }} module</span>
                                    </div>
                                    <div class="dashcode-stacked-list">
                                        @foreach ($app['modules'] as $module)
                                            <div class="dashcode-stacked-list-item">
                                                <div class="d-flex align-items-baseline gap-2">
                                                    <span class="fw-semibold">{{ $module['name'] }}</span>
                                                    <span class="small text-secondary font-monospace">{{ $module['code'] }}</span>
                                                </div>
                                                @if (filled($module['desc']))
                                                    <div class="small text-secondary mt-1">{{ $module['desc'] }}</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="dashcode-empty-state py-4">
                                    <div class="dashcode-empty-state-icon">
                                        @include('starter.templates.layouts.icon', ['name' => 'shield-lock'])
                                    </div>
                                    <p class="dashcode-empty-state-title">Belum ada akses module</p>
                                    <p class="dashcode-empty-state-description">Role ini belum memiliki app atau module yang dapat diakses.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="text-secondary small me-auto">
                            {{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($roleAccessAppCount) }} app · {{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($roleAccessModuleCount) }} module
                        </div>
                        <button type="button" class="btn" wire:click="closeRoleAccessModal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
