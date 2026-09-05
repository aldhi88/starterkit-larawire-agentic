<div class="dashcode-user-form">
    <div class="dashcode-page-heading" data-starter-region="page-header">
            <div>
                <div class="page-pretitle">Starter / Manajemen User / Users</div>
                <h2 class="page-title">{{ $userLoginId ? 'Edit User' : 'Tambah User' }}</h2>
                <div class="text-secondary">Atur identitas akun, role, dan status akun.</div>
            </div>
            <div>
                <a href="{{ route('starter.settings', ['section' => 'users']) }}" class="btn btn-secondary" data-starter-navigate>
                    @include('starter.templates.layouts.icon', ['name' => 'arrow-left', 'class' => 'icon-sm'])
                    Kembali ke Users
                </a>
            </div>
    </div>

    @if ($temporaryPassword)
        <div class="dashcode-alert dashcode-alert-warning" role="alert" data-temporary-credentials-alert data-starter-region="temporary-credentials">
            <span class="dashcode-alert-icon flex-shrink-0">
                @include('starter.templates.layouts.icon', ['name' => 'lock', 'class' => 'icon-sm'])
            </span>
            <div class="dashcode-grow">
                <h3 class="dashcode-alert-title">Simpan kredensial sementara ini sekarang</h3>
                <div>Username: <strong class="font-monospace">{{ $temporaryPasswordUsername }}</strong></div>
                <div>Password: <strong class="font-monospace">{{ $temporaryPassword }}</strong></div>
                <div class="small mt-1">Password tidak akan ditampilkan lagi. User wajib menggantinya setelah login.</div>
            </div>
            <button type="button" class="dashcode-icon-button dashcode-push-right" wire:click="dismissTemporaryPassword" aria-label="Tutup" data-temporary-credentials-dismiss>
                @include('starter.templates.layouts.icon', ['name' => 'circle-x', 'class' => 'icon-sm'])
            </button>
        </div>
    @endif

    <form wire:submit="save">
        <div class="dashcode-form-layout">
            <div data-starter-region="identity-form">
                <div class="card dashcode-full-height">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Detail User</h3>
                            <p class="card-subtitle">Identitas login dan status akun.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="form-label" for="user-name">Nama Tampilan</label>
                                <input type="text" id="user-name" class="form-control @error('userForm.name') is-invalid @enderror" wire:model.defer="userForm.name" autocomplete="name">
                                @error('userForm.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label" for="user-username">Username</label>
                                <input type="text" id="user-username" class="form-control @error('userForm.username') is-invalid @enderror" wire:model.defer="userForm.username" autocomplete="username">
                                @error('userForm.username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label" for="user-email">Email</label>
                                <input type="email" id="user-email" class="form-control @error('userForm.email') is-invalid @enderror" wire:model.defer="userForm.email" autocomplete="email">
                                <div class="form-hint">Digunakan untuk notifikasi dan persiapan reset password mandiri.</div>
                                @error('userForm.email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label" for="user-status">Status</label>
                                <select id="user-status" class="form-control @error('userForm.status') is-invalid @enderror" wire:model.defer="userForm.status">
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Nonaktif</option>
                                    <option value="locked">Terkunci</option>
                                </select>
                                @error('userForm.status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label" for="user-role">Role</label>
                                <select id="user-role" class="form-control @error('userForm.role_id') is-invalid @enderror" wire:model.live="userForm.role_id">
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
                    <div class="card-footer" data-starter-region="page-actions">
                        <div class="dashcode-actions">
                            <a href="{{ route('starter.settings', ['section' => 'users']) }}" class="btn btn-secondary" data-starter-navigate>
                                @include('starter.templates.layouts.icon', ['name' => 'arrow-left', 'class' => 'icon-sm'])
                                Batal dan Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                @include('starter.templates.layouts.icon', ['name' => 'check', 'class' => 'icon-sm'])
                                Simpan User
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div data-starter-region="role-access">
                <div class="card dashcode-full-height">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Akses Role</h3>
                            <p class="card-subtitle">Module yang dapat diakses oleh user ini.</p>
                        </div>
                        @if ($selectedRole)
                            <div class="card-actions">
                                <span class="badge {{ $selectedRole->isSuperuser() ? 'bg-danger-lt text-danger' : 'bg-primary-lt text-primary' }}">
                                    {{ $selectedRole->isSuperuser() ? 'Akses Penuh' : \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($selectedRoleModules->flatten(1)->count()).' Module' }}
                                </span>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        @if (! $selectedRole)
                            <div class="dashcode-empty-state py-4">
                                <div class="dashcode-empty-state-icon">
                                    @include('starter.templates.layouts.icon', ['name' => 'shield-lock'])
                                </div>
                                <p class="dashcode-empty-state-title">Pilih role</p>
                                <p class="dashcode-empty-state-description">Akses app dan module dari role akan ditampilkan di sini.</p>
                            </div>
                        @elseif ($selectedRoleModules->isEmpty())
                            <div class="dashcode-alert dashcode-alert-warning mb-0" role="alert">
                                <span class="dashcode-alert-icon flex-shrink-0">
                                    @include('starter.templates.layouts.icon', ['name' => 'alert-triangle', 'class' => 'icon-sm'])
                                </span>
                                <div>
                                    <h3 class="dashcode-alert-title">Tidak ada akses module</h3>
                                    Role ini belum diberi akses ke module apa pun.
                                </div>
                            </div>
                        @else
                            <div class="dashcode-stack dashcode-stack-lg">
                                @foreach ($selectedRoleModules as $appName => $modules)
                                    <div wire:key="role-access-app-{{ $modules->first()?->app_id ?? 'none' }}">
                                        <div class="dashcode-inline-heading">
                                            <div class="dashcode-font-semibold">{{ $appName }}</div>
                                            <span class="badge bg-secondary-lt">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($modules->count()) }} module</span>
                                        </div>
                                        <div class="dashcode-stacked-list">
                                            @foreach ($modules as $module)
                                                <div class="dashcode-stacked-list-item" wire:key="role-access-module-{{ $module->id }}">
                                                    <div class="dashcode-font-semibold">{{ $module->name }}</div>
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
