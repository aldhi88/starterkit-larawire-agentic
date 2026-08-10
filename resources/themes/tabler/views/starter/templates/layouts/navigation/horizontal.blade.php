<header class="navbar navbar-expand-md navbar-overlap sticky-top d-print-none starter-navbar-horizontal" data-bs-theme="dark">
    <div class="container-fluid starter-content-container">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#starter-horizontal-menu" aria-controls="starter-horizontal-menu" aria-expanded="false" aria-label="Buka atau tutup navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="navbar-brand navbar-brand-autodark me-2 me-md-3">
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

        <div class="navbar-nav flex-row order-md-last ms-auto align-items-center gap-2" x-persist="{{ $accountPersistBase }}-horizontal">
            @includeIf('extensions.starter.header-actions.index', ['compact' => true])
            @include('starter.templates.layouts.app-switcher', ['compact' => true])
            @include('starter.templates.layouts.account-menu')
        </div>

        <div class="collapse navbar-collapse" id="starter-horizontal-menu" data-starter-navigation data-starter-navigation-collapse>
            <ul class="navbar-nav">
                <li class="nav-item d-md-none px-2 pt-3 pb-2">
                    <div class="small text-secondary">App Aktif</div>
                    <div class="fw-semibold text-truncate" data-starter-current-app-name>{{ $currentAppName ?? 'App' }}</div>
                </li>

                @forelse ($sidebarMods as $mod)
                    @foreach ($mod['menus'] as $menu)
                        @include('starter.templates.layouts.menu-item-horizontal', ['menu' => $menu])
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
</header>
