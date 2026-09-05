@php
    $level = (int) ($level ?? 1);
    $menuId = 'horizontal-menu-'.substr(md5($menu['label'].$menu['url']), 0, 10);
    $isExpanded = (bool) ($menu['expanded'] ?? false);
    $isActive = (bool) ($menu['active'] ?? false);
    $icon = $menu['icon'] ?? 'circle';
@endphp

@if ($level === 1)
    <li class="{{ $menu['hasChildren'] ? 'menu-item-has-children' : '' }} {{ $isExpanded || $isActive ? 'active' : '' }}" data-starter-menu-item>
        @if ($menu['hasChildren'])
            <details class="starter-horizontal-details starter-navigation-details" data-starter-details data-starter-navigation-details>
                <summary class="starter-horizontal-link" role="button" aria-controls="{{ $menuId }}">
                    <span class="flex items-center gap-[6px]">
                        <span class="icon-box">@include('starter.templates.layouts.icon', ['name' => $icon])</span>
                        <span>{{ $menu['label'] }}</span>
                    </span>
                    @include('starter.templates.layouts.icon', ['name' => 'chevron-down', 'class' => 'starter-horizontal-chevron'])
                </summary>
                <ul class="sub-menu" id="{{ $menuId }}">
                    @foreach ($menu['children'] as $child)
                        @include('starter.templates.layouts.menu-item-horizontal', ['menu' => $child, 'level' => 2])
                    @endforeach
                </ul>
            </details>
        @elseif ($menu['url'] === '#')
            <span class="starter-horizontal-link is-disabled" aria-disabled="true">
                <span class="icon-box">@include('starter.templates.layouts.icon', ['name' => $icon])</span>
                <span>{{ $menu['label'] }}</span>
            </span>
        @else
            <a href="{{ $menu['url'] }}" class="starter-horizontal-link {{ $isActive ? 'active' : '' }}" @if ($isActive) data-current="true" @endif data-starter-navigate data-starter-menu-url="{{ $menu['url'] }}">
                <span class="icon-box">@include('starter.templates.layouts.icon', ['name' => $icon])</span>
                <span>{{ $menu['label'] }}</span>
            </a>
        @endif
    </li>
@elseif ($menu['hasChildren'])
    <li>
        <details class="starter-horizontal-details starter-horizontal-details-nested starter-navigation-details" data-starter-details data-starter-navigation-details>
            <summary class="starter-horizontal-submenu-link {{ $isExpanded ? 'active' : '' }}" role="button" aria-controls="{{ $menuId }}">
                <span>{{ $menu['label'] }}</span>
                @include('starter.templates.layouts.icon', ['name' => 'chevron-right', 'class' => 'starter-horizontal-chevron'])
            </summary>
            <ul class="starter-horizontal-nested" id="{{ $menuId }}">
                @foreach ($menu['children'] as $child)
                    @include('starter.templates.layouts.menu-item-horizontal', ['menu' => $child, 'level' => $level + 1])
                @endforeach
            </ul>
        </details>
    </li>
@elseif ($menu['url'] === '#')
    <li><span class="starter-horizontal-submenu-link is-disabled" aria-disabled="true">{{ $menu['label'] }}</span></li>
@else
    <li><a href="{{ $menu['url'] }}" class="starter-horizontal-submenu-link {{ $isActive ? 'active' : '' }}" @if ($isActive) data-current="true" @endif data-starter-navigate data-starter-menu-url="{{ $menu['url'] }}">{{ $menu['label'] }}</a></li>
@endif
