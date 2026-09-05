<aside id="layout-menu" class="layout-menu-horizontal menu-horizontal menu flex-grow-0 bg-menu-theme" data-starter-navigation>
    <div class="container-xxl d-flex h-100"><ul class="menu-inner py-1">
        @forelse ($sidebarMods as $mod)
            @foreach ($mod['menus'] as $menu)
                @include('starter.templates.layouts.menu-item-horizontal', ['menu' => $menu])
            @endforeach
        @empty
            <li class="menu-item disabled"><span class="menu-link">Belum ada menu</span></li>
        @endforelse
    </ul></div>
</aside>
