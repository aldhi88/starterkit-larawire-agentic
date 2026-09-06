<aside class="sidebar-wrapper group" data-starter-navigation-shell>
    <button type="button" class="starter-sidebar-overlay" data-starter-sidebar-close aria-label="Tutup navigasi"></button>

    <div class="logo-segment">
        <a class="starter-brand" href="{{ $currentDashboardUrl }}" data-starter-navigate aria-label="{{ $brandLogoAlt }}">
            <img src="{{ $brandLogoDarkUrl }}" class="starter-brand-image black_logo" alt="{{ $brandLogoAlt }}" data-starter-brand-logo data-fallback-src="{{ $defaultBrandLogoDarkUrl }}" @if ($clientLogoUrl) data-company-logo="true" @endif>
            <img src="{{ $brandLogoUrl }}" class="starter-brand-image white_logo" alt="{{ $brandLogoAlt }}" data-starter-brand-logo data-fallback-src="{{ $defaultBrandLogoUrl }}" @if ($clientLogoUrl) data-company-logo="true" @endif>
        </a>
        <div class="sidebarCloseIcon">
            <button type="button" class="starter-icon-button" data-starter-sidebar-close aria-label="Tutup navigasi">
                @include('starter.templates.layouts.icon', ['name' => 'circle-x'])
            </button>
        </div>
    </div>

    @if ($horizontal ?? false)
        <div class="border-b border-slate-200 px-4 py-3 xl:hidden" data-starter-active-app>
            <div class="text-xs font-medium uppercase leading-none tracking-wide text-slate-400">App Aktif</div>
            <div class="mt-1 truncate text-sm font-semibold leading-tight text-slate-800" data-starter-current-app-name>{{ $currentAppName ?? 'App' }}</div>
        </div>
    @endif

    <nav class="sidebar-menus" data-starter-navigation>
        <ul class="sidebar-menu">
            <li class="sidebar-menu-title">Menu Utama</li>
            @forelse ($sidebarMods as $mod)
                @foreach ($mod['menus'] as $menu)
                    @include('starter.templates.layouts.menu-item', ['menu' => $menu])
                @endforeach
            @empty
                <li><span class="navItem is-disabled">@include('starter.templates.layouts.icon', ['name' => 'circle']) <span>Belum ada menu</span></span></li>
            @endforelse
        </ul>
    </nav>
</aside>
