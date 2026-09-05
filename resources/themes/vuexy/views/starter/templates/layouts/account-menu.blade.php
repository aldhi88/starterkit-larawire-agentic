<div class="position-relative" data-vuexy-dropdown>
    <button class="nav-link dropdown-toggle hide-arrow p-0 d-flex align-items-center gap-2" type="button" aria-label="Buka menu user" aria-expanded="false" data-vuexy-dropdown-toggle data-starter-account-summary>
        <span class="avatar avatar-online"><img class="rounded-circle" src="{{ $loginAvatarUrl }}" alt="" data-starter-account-avatar></span>
        <span class="d-none d-lg-flex flex-column align-items-start lh-sm me-1">
            <span class="fw-medium text-heading text-truncate vuexy-account-name" data-starter-account-name>{{ $loginName }}</span>
            <small class="text-body-secondary" data-starter-account-role>{{ $login?->role?->name ?? 'User' }}</small>
        </span>
        @include('starter.templates.layouts.icon', ['name' => 'chevron-down', 'class' => 'icon-sm d-none d-lg-inline'])
    </button>
    <div class="dropdown-menu dropdown-menu-end mt-3" data-vuexy-dropdown-menu>
        <div class="dropdown-item-text py-3"><div class="d-flex align-items-center gap-3"><span class="avatar"><img class="rounded-circle" src="{{ $loginAvatarUrl }}" alt=""></span><div class="lh-sm"><div class="fw-semibold text-heading">{{ $loginName }}</div><small class="text-body-secondary">{{ $login?->role?->name ?? 'User' }}</small></div></div></div>
        <div class="dropdown-divider"></div>
        <a href="{{ $currentProfileUrl }}" class="dropdown-item" data-starter-navigate>@include('starter.templates.layouts.icon', ['name' => 'user', 'class' => 'me-3']) Edit Profil Saya</a>
        @if ($login?->role?->canManageSettings())
            <a href="{{ route('starter.settings') }}" class="dropdown-item" data-starter-navigate>@include('starter.templates.layouts.icon', ['name' => 'settings', 'class' => 'me-3']) Pengaturan</a>
        @endif
        @if ($login?->role?->canViewLogs())
            <a href="{{ route('starter.logs.index') }}" class="dropdown-item" data-starter-navigate>@include('starter.templates.layouts.icon', ['name' => 'history', 'class' => 'me-3']) Log Aktivitas</a>
        @endif
        @includeIf('extensions.starter.profile-menu.index')
        @if ($lockScreenEnabled ?? false)
            <a href="{{ route('starter.lock-screen', ['manual' => 1]) }}" class="dropdown-item" data-starter-navigate>@include('starter.templates.layouts.icon', ['name' => 'lock', 'class' => 'me-3']) Kunci Layar</a>
        @endif
        <div class="dropdown-divider"></div>
        <form method="POST" action="{{ route('auth.logout') }}" data-starter-logout-form>@csrf<button class="dropdown-item text-danger" type="submit">@include('starter.templates.layouts.icon', ['name' => 'logout', 'class' => 'me-3']) Logout</button></form>
    </div>
</div>
