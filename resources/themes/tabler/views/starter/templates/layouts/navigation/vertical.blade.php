<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#starter-sidebar-menu" aria-controls="starter-sidebar-menu" aria-expanded="false" aria-label="Buka atau tutup navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="navbar-brand navbar-brand-autodark">
            <a href="{{ $currentDashboardUrl }}" class="starter-sidebar-brand" aria-label="{{ $brandLogoAlt }}" data-starter-navigate>
                <img
                    src="{{ $brandLogoUrl }}"
                    class="starter-sidebar-brand-image"
                    alt="{{ $brandLogoAlt }}"
                    data-starter-brand-logo
                    data-fallback-src="{{ $defaultBrandLogoUrl }}"
                    @if ($clientLogoUrl) data-company-logo="true" @endif
                >
            </a>
        </div>

        <div class="navbar-nav flex-row d-lg-none ms-auto align-items-center gap-2" x-persist="{{ $accountPersistBase }}-mobile">
            @includeIf('extensions.starter.header-actions.index', ['compact' => true])
            @include('starter.templates.layouts.app-switcher', ['compact' => true])
            @include('starter.templates.layouts.account-menu')
        </div>

        <div class="collapse navbar-collapse" id="starter-sidebar-menu" data-starter-navigation data-starter-navigation-collapse>
            <ul class="navbar-nav pt-lg-3">
                <li class="nav-item d-lg-none px-0 pt-3 pb-2">
                    <div class="small text-secondary">App Aktif</div>
                    <div class="fw-semibold text-truncate" data-starter-current-app-name>{{ $currentAppName ?? 'App' }}</div>
                </li>

                <li class="nav-item px-0 px-lg-3 pt-3 pb-1">
                    <span class="subheader">Menu Utama</span>
                </li>

                @forelse ($sidebarMods as $mod)
                    @foreach ($mod['menus'] as $menu)
                        @include('starter.templates.layouts.menu-item', ['menu' => $menu])
                    @endforeach
                @empty
                    <li class="nav-item">
                        <span class="nav-link disabled">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                @include('starter.templates.layouts.icon', ['name' => 'circle'])
                            </span>
                            <span class="nav-link-title">Belum ada menu</span>
                        </span>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</aside>

<header class="navbar navbar-expand-lg d-none d-lg-flex d-print-none" x-persist="{{ $accountPersistBase }}-topbar">
    <div class="container-fluid starter-content-container">
        <div class="d-none d-lg-flex flex-column lh-sm me-auto">
            <span class="small text-secondary">App Aktif</span>
            <span class="fw-semibold text-truncate" data-starter-current-app-name>{{ $currentAppName ?? 'App' }}</span>
        </div>

        <div class="navbar-nav flex-row order-lg-last ms-auto align-items-center">
            @includeIf('extensions.starter.header-actions.index', ['compact' => false])
            @include('starter.templates.layouts.app-switcher', ['compact' => false])
            @include('starter.templates.layouts.account-menu')
        </div>
    </div>
</header>
