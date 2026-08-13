@php($horizontal = (bool) ($horizontal ?? false))
<header class="z-[99] {{ $horizontal ? 'starter-header-horizontal' : '' }}" id="app_header">
    <div class="app-header bg-white shadow-sm ltr:ml-[248px] rtl:mr-[248px]">
        <div class="flex h-full items-center justify-between gap-4">
            <div class="vertical-box items-center gap-3">
                <button type="button" class="starter-icon-button xl:hidden" data-starter-sidebar-open aria-label="Buka navigasi">
                    @include('starter.templates.layouts.icon', ['name' => 'menu-2'])
                </button>
                <div class="min-w-0">
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-400">App Aktif</div>
                    <div class="truncate text-sm font-semibold text-slate-800" data-starter-current-app-name>{{ $currentAppName ?? 'App' }}</div>
                </div>
            </div>

            <div class="horizental-box items-center gap-3">
                <a href="{{ $currentDashboardUrl }}" class="starter-header-brand" data-starter-navigate aria-label="{{ $brandLogoAlt }}">
                    <img src="{{ $brandLogoDarkUrl }}" alt="{{ $brandLogoAlt }}" data-starter-brand-logo data-fallback-src="{{ $defaultBrandLogoDarkUrl }}" @if ($clientLogoUrl) data-company-logo="true" @endif>
                </a>
                <button type="button" class="starter-icon-button xl:hidden" data-starter-sidebar-open aria-label="Buka navigasi">
                    @include('starter.templates.layouts.icon', ['name' => 'menu-2'])
                </button>
            </div>

            <nav class="main-menu" data-starter-navigation aria-label="Navigasi utama">
                <ul>
                    @forelse ($sidebarMods as $mod)
                        @foreach ($mod['menus'] as $menu)
                            @include('starter.templates.layouts.menu-item-horizontal', ['menu' => $menu])
                        @endforeach
                    @empty
                        <li><span class="starter-horizontal-link is-disabled">Belum ada menu</span></li>
                    @endforelse
                </ul>
            </nav>

            <div class="flex items-center gap-2" x-persist="{{ $accountPersistBase }}-dashcode-header">
                @includeIf('extensions.starter.header-actions.index', ['compact' => $horizontal])
                @include('starter.templates.layouts.app-switcher', ['compact' => $horizontal])
                @include('starter.templates.layouts.account-menu')
            </div>
        </div>
    </div>
</header>
