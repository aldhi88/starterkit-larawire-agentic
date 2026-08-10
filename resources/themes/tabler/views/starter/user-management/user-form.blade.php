<div>
    <div class="page-header d-print-none mt-0 mb-3">
        <div class="row g-3 align-items-center">
            <div class="col">
                <div class="page-pretitle">Starter / Manajemen User / Users</div>
                <h2 class="page-title">{{ $userLoginId ? 'Edit User' : 'Tambah User' }}</h2>
                <div class="text-secondary">Atur identitas akun, role, dan status akun.</div>
            </div>
            <div class="col-auto">
                <a href="{{ route('starter.settings', ['section' => 'users']) }}" class="btn" data-starter-navigate>
                    @include('starter.templates.layouts.icon', ['name' => 'arrow-left', 'class' => 'icon-sm me-1'])
                    Kembali ke Users
                </a>
            </div>
        </div>
    </div>

    @if ($temporaryPassword)
        <div class="alert alert-warning alert-dismissible" role="alert" data-temporary-credentials-alert>
            <div>
                <h3 class="alert-title">Simpan kredensial sementara ini sekarang</h3>
                <div>Username: <strong class="font-monospace">{{ $temporaryPasswordUsername }}</strong></div>
                <div>Password: <strong class="font-monospace">{{ $temporaryPassword }}</strong></div>
                <div class="small mt-1">Password tidak akan ditampilkan lagi. User wajib menggantinya setelah login.</div>
            </div>
            <button type="button" class="btn-close" wire:click="dismissTemporaryPassword" aria-label="Tutup" data-temporary-credentials-dismiss></button>
        </div>
    @endif

    <form wire:submit="save">
        <div class="row row-cards">
            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Detail User</h3>
                            <p class="card-subtitle">Identitas login dan status akun.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="user-name">Nama Tampilan</label>
                                <input type="text" id="user-name" class="form-control @error('userForm.name') is-invalid @enderror" wire:model.defer="userForm.name" autocomplete="name">
                                @error('userForm.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="user-username">Username</label>
                                <input type="text" id="user-username" class="form-control @error('userForm.username') is-invalid @enderror" wire:model.defer="userForm.username" autocomplete="username">
                                @error('userForm.username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="user-email">Email</label>
                                <input type="email" id="user-email" class="form-control @error('userForm.email') is-invalid @enderror" wire:model.defer="userForm.email" autocomplete="email">
                                <div class="form-hint">Digunakan untuk notifikasi dan persiapan reset password mandiri.</div>
                                @error('userForm.email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="user-status">Status</label>
                                <select id="user-status" class="form-select @error('userForm.status') is-invalid @enderror" wire:model.defer="userForm.status">
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Nonaktif</option>
                                    <option value="locked">Terkunci</option>
                                </select>
                                @error('userForm.status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="user-role">Role</label>
                                <select id="user-role" class="form-select @error('userForm.role_id') is-invalid @enderror" wire:model.live="userForm.role_id">
                                    <option value="">Pilih Role</option>
                                    @foreach ($roles as $role)
                                        @if (! $role->isSuperuser() || (int) $userForm['role_id'] === $role->id)
                                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <div class="form-hint">Akses module mengikuti role yang dipilih dan tidak dapat diubah dari halaman ini.</div>
                                @error('userForm.role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="btn-list justify-content-end">
                            <a href="{{ route('starter.settings', ['section' => 'users']) }}" class="btn" data-starter-navigate>
                                @include('starter.templates.layouts.icon', ['name' => 'arrow-left', 'class' => 'icon-sm me-1'])
                                Batal dan Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                @include('starter.templates.layouts.icon', ['name' => 'check', 'class' => 'icon-sm me-1'])
                                Simpan User
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card h-100">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Akses Role</h3>
                            <p class="card-subtitle">Module yang dapat diakses oleh user ini.</p>
                        </div>
                        @if ($selectedRole)
                            <div class="card-actions">
                                <span class="badge {{ $selectedRole->isSuperuser() ? 'bg-danger-lt text-danger' : 'bg-primary-lt text-primary' }}">
                                    {{ $selectedRole->isSuperuser() ? 'Akses Penuh' : \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($selectedRoleModules->flatten(1)->count()).' Module' }}
                                </span>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        @if (! $selectedRole)
                            <div class="empty py-4">
                                <div class="empty-icon">
                                    @include('starter.templates.layouts.icon', ['name' => 'shield-lock'])
                                </div>
                                <p class="empty-title">Pilih role</p>
                                <p class="empty-subtitle text-secondary">Akses app dan module dari role akan ditampilkan di sini.</p>
                            </div>
                        @elseif ($selectedRoleModules->isEmpty())
                            <div class="alert alert-warning mb-0" role="alert">
                                <h3 class="alert-title">Tidak ada akses module</h3>
                                Role ini belum diberi akses ke module apa pun.
                            </div>
                        @else
                            <div class="vstack gap-4">
                                @foreach ($selectedRoleModules as $appName => $modules)
                                    <div wire:key="role-access-app-{{ $modules->first()?->app_id ?? 'none' }}">
                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                            <div class="fw-semibold">{{ $appName }}</div>
                                            <span class="badge bg-secondary-lt">{{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($modules->count()) }} module</span>
                                        </div>
                                        <div class="list-group list-group-flush border rounded">
                                            @foreach ($modules as $module)
                                                <div class="list-group-item" wire:key="role-access-module-{{ $module->id }}">
                                                    <div class="fw-semibold">{{ $module->name }}</div>
                                                    <div class="small text-secondary">
                                                        <span class="font-monospace">{{ $module->code }}</span>
                                                        @if (filled($module->desc))
                                                            · {{ $module->desc }}
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
