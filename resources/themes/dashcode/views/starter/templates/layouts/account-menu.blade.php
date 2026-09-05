<details class="starter-account-menu {{ $class ?? '' }}" data-starter-details>
    <summary class="inline-flex cursor-pointer items-center rounded-lg text-center text-sm font-medium text-slate-800" role="button" aria-label="Buka menu user" data-starter-account-summary>
        <span class="h-7 w-7 flex-none rounded-full bg-slate-200 bg-cover bg-center ltr:mr-[10px] rtl:ml-[10px] lg:h-8 lg:w-8" style="background-image: url({{ $loginAvatarUrl }})" data-starter-account-avatar></span>
        <span class="hidden max-w-[160px] flex-none items-center overflow-hidden text-ellipsis whitespace-nowrap text-sm font-normal text-slate-600 lg:flex" data-starter-account-name>{{ $loginName ?? 'User' }}</span>
        @include('starter.templates.layouts.icon', ['name' => 'chevron-down', 'class' => 'ml-[10px] hidden h-[16px] w-[16px] lg:inline-block'])
    </summary>

    <div class="starter-account-panel">
        <div class="starter-dropdown-label">Akun Saya</div>
        <a href="{{ $currentProfileUrl }}" class="starter-dropdown-item" data-starter-navigate>
            @include('starter.templates.layouts.icon', ['name' => 'user-circle'])
            Edit Profil Saya
        </a>
        @if ($login?->role?->canManageSettings())
            <a href="{{ route('starter.settings') }}" class="starter-dropdown-item" data-starter-navigate>
                @include('starter.templates.layouts.icon', ['name' => 'settings'])
                Pengaturan
            </a>
        @endif
        @if ($login?->role?->canViewLogs())
            <a href="{{ route('starter.logs.index') }}" class="starter-dropdown-item" data-starter-navigate>
                @include('starter.templates.layouts.icon', ['name' => 'history'])
                Log Aktivitas
            </a>
        @endif
        @includeIf('extensions.starter.profile-menu.index')
        <div class="starter-dropdown-divider"></div>
        @if ($lockScreenEnabled ?? false)
            <a href="{{ route('starter.lock-screen', ['manual' => 1]) }}" class="starter-dropdown-item" data-starter-navigate>
                @include('starter.templates.layouts.icon', ['name' => 'lock'])
                Kunci Layar
            </a>
        @endif
        <form method="POST" action="{{ route('auth.logout') }}" data-starter-logout-form>
            @csrf
            <button type="submit" class="starter-dropdown-item starter-dropdown-danger">
                @include('starter.templates.layouts.icon', ['name' => 'logout'])
                Logout
            </button>
        </form>
    </div>
</details>
