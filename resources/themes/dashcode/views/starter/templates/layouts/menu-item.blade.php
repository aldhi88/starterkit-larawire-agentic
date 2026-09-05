@php
    $level = (int) ($level ?? 1);
    $menuId = 'sidebar-menu-'.substr(md5($menu['label'].$menu['url']), 0, 10);
    $isExpanded = (bool) ($menu['expanded'] ?? false);
    $isActive = (bool) ($menu['active'] ?? false);
    $icon = $menu['icon'] ?? 'circle';
@endphp

@if ($level === 1)
    <li class="{{ $isExpanded || $isActive ? 'active' : '' }}" data-starter-menu-item>
        @if ($menu['hasChildren'])
            <details class="starter-sidebar-details starter-navigation-details" data-starter-navigation-details @if ($isExpanded) open @endif>
                <summary class="navItem" role="button" aria-controls="{{ $menuId }}">
                    <span class="starter-parent-menu-content">
                        @include('starter.templates.layouts.icon', ['name' => $icon, 'class' => 'nav-icon'])
                        <span>{{ $menu['label'] }}</span>
                    </span>
                    @include('starter.templates.layouts.icon', ['name' => 'chevron-right', 'class' => 'icon-arrow'])
                </summary>
                <ul class="sidebar-submenu" id="{{ $menuId }}">
                    @foreach ($menu['children'] as $child)
                        @include('starter.templates.layouts.menu-item', ['menu' => $child, 'level' => 2])
                    @endforeach
                </ul>
            </details>
        @elseif ($menu['url'] === '#')
            <span class="navItem is-disabled" aria-disabled="true">
                <span class="starter-parent-menu-content">
                    @include('starter.templates.layouts.icon', ['name' => $icon, 'class' => 'nav-icon'])
                    <span>{{ $menu['label'] }}</span>
                </span>
            </span>
        @else
            <a href="{{ $menu['url'] }}" class="navItem {{ $isActive ? 'active' : '' }}" @if ($isActive) data-current="true" @endif data-starter-navigate data-starter-menu-url="{{ $menu['url'] }}">
                <span class="starter-parent-menu-content">
                    @include('starter.templates.layouts.icon', ['name' => $icon, 'class' => 'nav-icon'])
                    <span>{{ $menu['label'] }}</span>
                </span>
            </a>
        @endif
    </li>
@elseif ($menu['hasChildren'])
    <li>
        <details class="starter-sidebar-details starter-sidebar-details-nested starter-navigation-details" data-starter-navigation-details @if ($isExpanded) open @endif>
            <summary class="starter-submenu-link {{ $isExpanded ? 'active' : '' }}" role="button" aria-controls="{{ $menuId }}">
                <span>{{ $menu['label'] }}</span>
                @include('starter.templates.layouts.icon', ['name' => 'chevron-right', 'class' => 'icon-arrow'])
            </summary>
            <ul class="sidebar-submenu" id="{{ $menuId }}">
                @foreach ($menu['children'] as $child)
                    @include('starter.templates.layouts.menu-item', ['menu' => $child, 'level' => $level + 1])
                @endforeach
            </ul>
        </details>
    </li>
@elseif ($menu['url'] === '#')
    <li><span class="starter-submenu-link is-disabled" aria-disabled="true">{{ $menu['label'] }}</span></li>
@else
    <li><a href="{{ $menu['url'] }}" class="starter-submenu-link {{ $isActive ? 'active' : '' }}" @if ($isActive) data-current="true" @endif data-starter-navigate data-starter-menu-url="{{ $menu['url'] }}">{{ $menu['label'] }}</a></li>
@endif
