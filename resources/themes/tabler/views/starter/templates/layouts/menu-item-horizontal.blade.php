@php
    $level = (int) ($level ?? 1);
    $menuId = 'horizontal-menu-'.substr(md5($menu['label'].$menu['url']), 0, 10);
    $isExpanded = (bool) ($menu['expanded'] ?? false);
    $isActive = (bool) ($menu['active'] ?? false);
    $icon = $menu['icon'] ?? 'circle';
@endphp

@if ($level === 1)
    <li class="nav-item {{ $isExpanded || $isActive ? 'active' : '' }}" data-starter-menu-item>
        @if ($menu['hasChildren'])
            <details class="starter-horizontal-details starter-navigation-details" data-starter-details data-starter-navigation-details @if ($isExpanded) open @endif>
                <summary class="nav-link cursor-pointer user-select-none" role="button" aria-controls="{{ $menuId }}">
                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                        @include('starter.templates.layouts.icon', ['name' => $icon])
                    </span>
                    <span class="nav-link-title">{{ $menu['label'] }}</span>
                </summary>
                <div class="dropdown-menu starter-horizontal-submenu" id="{{ $menuId }}">
                    @foreach ($menu['children'] as $child)
                        @include('starter.templates.layouts.menu-item-horizontal', ['menu' => $child, 'level' => 2])
                    @endforeach
                </div>
            </details>
        @elseif ($menu['url'] === '#')
            <span class="nav-link disabled" aria-disabled="true">
                <span class="nav-link-icon d-md-none d-lg-inline-block">
                    @include('starter.templates.layouts.icon', ['name' => $icon])
                </span>
                <span class="nav-link-title">{{ $menu['label'] }}</span>
            </span>
        @else
            <a href="{{ $menu['url'] }}" class="nav-link" @if ($isActive) data-current="true" @endif data-starter-navigate data-starter-menu-url="{{ $menu['url'] }}">
                <span class="nav-link-icon d-md-none d-lg-inline-block">
                    @include('starter.templates.layouts.icon', ['name' => $icon])
                </span>
                <span class="nav-link-title">{{ $menu['label'] }}</span>
            </a>
        @endif
    </li>
@elseif ($menu['hasChildren'])
    <details class="starter-horizontal-details starter-horizontal-details-nested starter-navigation-details" data-starter-details data-starter-navigation-details @if ($isExpanded) open @endif>
        <summary class="dropdown-item cursor-pointer user-select-none {{ $isExpanded ? 'active' : '' }}" role="button" aria-controls="{{ $menuId }}">
            <span>{{ $menu['label'] }}</span>
        </summary>
        <div class="dropdown-menu starter-horizontal-submenu" id="{{ $menuId }}">
            @foreach ($menu['children'] as $child)
                @include('starter.templates.layouts.menu-item-horizontal', ['menu' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    </details>
@elseif ($menu['url'] === '#')
    <span class="dropdown-item disabled" aria-disabled="true">{{ $menu['label'] }}</span>
@else
    <a href="{{ $menu['url'] }}" class="dropdown-item {{ $isActive ? 'active' : '' }}" @if ($isActive) data-current="true" @endif data-starter-navigate data-starter-menu-url="{{ $menu['url'] }}">
        {{ $menu['label'] }}
    </a>
@endif
