<aside id="layout-menu" class="layout-menu menu-vertical menu" data-starter-navigation data-bs-theme="dark">
    <div class="app-brand">
        @include('starter.templates.layouts.brand', ['url' => $currentDashboardUrl])
        <button type="button" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none" data-vuexy-menu-toggle aria-label="Tutup navigasi" aria-expanded="true">
            @include('starter.templates.layouts.icon', ['name' => 'x', 'class' => 'icon-md'])
        </button>
    </div>
    <div class="menu-inner-shadow"></div>
    <ul class="menu-inner py-1">
        <li class="menu-header small"><span class="menu-header-text">Menu Utama</span></li>
        @forelse ($sidebarMods as $mod)
            @foreach ($mod['menus'] as $menu)
                @include('starter.templates.layouts.menu-item', ['menu' => $menu])
            @endforeach
        @empty
            <li class="menu-item disabled"><span class="menu-link">Belum ada menu</span></li>
        @endforelse
    </ul>
</aside>
<div class="menu-mobile-toggler d-xl-none rounded-1">
    <button type="button" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1 border-0" data-vuexy-menu-toggle aria-label="Buka navigasi" aria-expanded="false">
        @include('starter.templates.layouts.icon', ['name' => 'menu', 'class' => 'icon-sm'])
        @include('starter.templates.layouts.icon', ['name' => 'chevron-right', 'class' => 'icon-sm'])
    </button>
</div>
