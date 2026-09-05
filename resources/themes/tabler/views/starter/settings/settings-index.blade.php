@php
    $sections = [
        'roles' => [
            'label' => 'Roles',
            'description' => 'Atur hak akses module dan halaman awal.',
            'icon' => 'shield-lock',
        ],
        'users' => [
            'label' => 'Users',
            'description' => 'Kelola akun, status, role, dan password.',
            'icon' => 'users',
        ],
        'company' => [
            'label' => 'Profil Perusahaan',
            'description' => 'Perbarui identitas dan kontak perusahaan.',
            'icon' => 'building',
        ],
        'security' => [
            'label' => 'Keamanan Sistem',
            'description' => 'Atur login, lock screen, dan batas upload.',
            'icon' => 'shield-check',
        ],
    ];
    $activeSection = $sections[$section];
@endphp

<div>
    <div class="page-header d-print-none mt-0 mb-3" aria-label="Header halaman" data-starter-region="page-header">
        <div class="row g-3 align-items-center">
            <div class="col">
                <div class="page-pretitle">Administrasi Sistem</div>
                <h2 class="page-title">Pengaturan</h2>
                <div class="text-secondary">Kelola akses, akun user, dan identitas perusahaan dari satu tempat.</div>
            </div>
            <div class="col-auto ms-auto d-none d-md-block">
                <div class="d-flex align-items-center justify-content-end gap-2 text-end text-secondary small">
                    @include('starter.templates.layouts.icon', ['name' => 'info-circle', 'class' => 'icon-sm'])
                    Perubahan konfigurasi sistem tercatat di audit log.
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards mb-3" data-starter-region="summary">
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar bg-primary-lt text-primary me-3">
                            {{ str($client->name)->substr(0, 1)->upper() }}
                        </span>
                        <div class="overflow-hidden">
                            <div class="text-secondary">Perusahaan</div>
                            <div class="fw-semibold text-truncate">{{ $client->name }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar bg-purple-lt text-purple me-3">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($roleCount) }}</span>
                        <div>
                            <div class="text-secondary">Role</div>
                            <div class="fw-semibold">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($roleCount) }} role terdaftar</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar bg-green-lt text-green me-3">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($userCount) }}</span>
                        <div>
                            <div class="text-secondary">User</div>
                            <div class="fw-semibold">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($userCount) }} akun dikelola</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar bg-azure-lt text-azure me-3">
                            @include('starter.templates.layouts.icon', ['name' => 'apps', 'class' => 'icon-sm'])
                        </span>
                        <div>
                            <div class="text-secondary">Total Aplikasi</div>
                            <div class="fw-semibold">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($appCount) }} aplikasi tersedia</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards align-items-start">
        <div class="col-12">
            <div class="card">
                <div class="card-header" data-starter-region="section-navigation">
                    <ul class="nav nav-tabs card-header-tabs">
                        @foreach ($sections as $sectionKey => $sectionItem)
                            <li class="nav-item">
                                <a href="{{ route('starter.settings', ['section' => $sectionKey]) }}"
                                   class="nav-link {{ $section === $sectionKey ? 'active fw-bold' : 'text-secondary' }}"
                                   @if ($section === $sectionKey) aria-current="page" @endif
                                   data-starter-navigate>
                                    @include('starter.templates.layouts.icon', ['name' => $sectionItem['icon'], 'class' => 'icon me-2'])
                                    {{ $sectionItem['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-body border-bottom" data-starter-region="section-header">
                    <div class="d-flex align-items-start justify-content-between pb-1">
                        <div>
                            <h3 class="card-title mb-1">{{ $activeSection['label'] }}</h3>
                            <p class="text-secondary mb-0">{{ $activeSection['description'] }}</p>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            @if ($section === 'roles')
                                <a href="{{ route('starter.settings.roles.create') }}" class="btn btn-primary" data-starter-navigate>
                                    @include('starter.templates.layouts.icon', ['name' => 'file-plus', 'class' => 'icon-sm me-1'])
                                    Tambah Role
                                </a>
                            @elseif ($section === 'users')
                                <a href="{{ route('starter.user-management.users.create') }}" class="btn btn-primary" data-starter-navigate>
                                    @include('starter.templates.layouts.icon', ['name' => 'user-plus', 'class' => 'icon-sm me-1'])
                                    Tambah User
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div data-starter-region="section-content">
                    @if ($section === 'roles')
                        <livewire:starter.user-management.roles :embedded="true" key="settings-roles" />
                    @elseif ($section === 'users')
                        <livewire:starter.user-management.users :embedded="true" key="settings-users" />
                    @elseif ($section === 'company')
                        <livewire:starter.settings.client-profile :embedded="true" key="settings-company" />
                    @else
                        <livewire:starter.settings.security-settings :embedded="true" key="settings-security" />
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
