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

<div class="dashcode-settings-page">
    <div class="page-header dashcode-settings-header mb-5 flex flex-col gap-3 md:flex-row md:items-center" aria-label="Header halaman">
        <div>
            <div class="page-pretitle">Administrasi Sistem</div>
            <h2 class="page-title">Pengaturan</h2>
            <div class="text-secondary">Kelola akses, akun user, dan identitas perusahaan dari satu tempat.</div>
        </div>
        <div class="dashcode-settings-audit-note hidden items-center gap-2 text-sm text-slate-500 md:flex">
            @include('starter.templates.layouts.icon', ['name' => 'info-circle', 'class' => 'icon-sm'])
            Perubahan konfigurasi sistem tercatat di audit log.
        </div>
    </div>

    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card starter-settings-stat starter-settings-stat-company">
            <div class="card-body flex items-center gap-3 px-4 pb-3 pt-4">
                <span class="starter-settings-stat-icon" aria-hidden="true">
                    @include('starter.templates.layouts.icon', ['name' => 'building'])
                </span>
                <div class="overflow-hidden">
                    <div class="starter-settings-stat-label">Perusahaan</div>
                    <div class="starter-settings-stat-value text-truncate">{{ $client->name }}</div>
                </div>
            </div>
        </div>
        <div class="card starter-settings-stat starter-settings-stat-role">
            <div class="card-body flex items-center gap-3 px-4 pb-3 pt-4">
                <span class="starter-settings-stat-icon" aria-hidden="true">
                    @include('starter.templates.layouts.icon', ['name' => 'shield-lock'])
                </span>
                <div class="overflow-hidden">
                    <div class="starter-settings-stat-label">Role</div>
                    <div class="starter-settings-stat-value">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($roleCount) }} role terdaftar</div>
                </div>
            </div>
        </div>
        <div class="card starter-settings-stat starter-settings-stat-user">
            <div class="card-body flex items-center gap-3 px-4 pb-3 pt-4">
                <span class="starter-settings-stat-icon" aria-hidden="true">
                    @include('starter.templates.layouts.icon', ['name' => 'users'])
                </span>
                <div class="overflow-hidden">
                    <div class="starter-settings-stat-label">User</div>
                    <div class="starter-settings-stat-value">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($userCount) }} akun dikelola</div>
                </div>
            </div>
        </div>
        <div class="card starter-settings-stat starter-settings-stat-app">
            <div class="card-body flex items-center gap-3 px-4 pb-3 pt-4">
                <span class="starter-settings-stat-icon" aria-hidden="true">
                    @include('starter.templates.layouts.icon', ['name' => 'apps'])
                </span>
                <div class="overflow-hidden">
                    <div class="starter-settings-stat-label">Total Aplikasi</div>
                    <div class="starter-settings-stat-value">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($appCount) }} aplikasi tersedia</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card dashcode-settings-panel">
        <div class="card-header overflow-x-auto">
            <ul class="nav nav-tabs card-header-tabs flex-nowrap">
                @foreach ($sections as $sectionKey => $sectionItem)
                    <li class="nav-item">
                        <a href="{{ route('starter.settings', ['section' => $sectionKey]) }}"
                           class="nav-link inline-flex items-center gap-2 whitespace-nowrap {{ $section === $sectionKey ? 'active fw-bold' : 'text-secondary' }}"
                           @if ($section === $sectionKey) aria-current="page" @endif
                           data-starter-navigate>
                            @include('starter.templates.layouts.icon', ['name' => $sectionItem['icon'], 'class' => 'icon'])
                            {{ $sectionItem['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-body border-bottom p-6">
            <div class="flex flex-col items-start justify-between gap-4 md:flex-row">
                <div>
                    <h3 class="card-title mb-1">{{ $activeSection['label'] }}</h3>
                    <p class="text-secondary mb-0">{{ $activeSection['description'] }}</p>
                </div>
                @if ($section === 'roles')
                    <a href="{{ route('starter.settings.roles.create') }}" class="btn btn-primary inline-flex items-center justify-center gap-2" data-starter-navigate>
                        @include('starter.templates.layouts.icon', ['name' => 'file-plus'])
                        <span>Tambah Role</span>
                    </a>
                @elseif ($section === 'users')
                    <a href="{{ route('starter.user-management.users.create') }}" class="btn btn-primary inline-flex items-center justify-center gap-2" data-starter-navigate>
                        @include('starter.templates.layouts.icon', ['name' => 'user-plus'])
                        <span>Tambah User</span>
                    </a>
                @endif
            </div>
        </div>

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
