@php($nested = $nested ?? false)
<li class="menu-item {{ ($menu['active'] ?? false) ? 'active' : '' }} {{ ($menu['expanded'] ?? false) ? 'open' : '' }}" data-starter-menu-item>
    @if ($menu['hasChildren'])
        <button class="menu-link menu-toggle starter-menu-toggle border-0 bg-transparent" type="button" data-vuexy-submenu aria-expanded="{{ ($menu['expanded'] ?? false) ? 'true' : 'false' }}">
            @unless ($nested)@include('starter.templates.layouts.icon', ['name' => $menu['icon'] ?? 'folder', 'class' => 'menu-icon'])@endunless<div>{{ $menu['label'] }}</div>
        </button>
        <ul class="menu-sub">
            @foreach ($menu['children'] as $child)
                @include('starter.templates.layouts.menu-item', ['menu' => $child, 'nested' => true])
            @endforeach
        </ul>
    @else
        <a class="menu-link {{ $menu['url'] === '#' ? 'disabled' : '' }}" href="{{ $menu['url'] }}" data-starter-navigate data-starter-menu-url="{{ $menu['url'] }}" @if ($menu['active'] ?? false) data-current="true" aria-current="page" @endif>
            @unless ($nested)@include('starter.templates.layouts.icon', ['name' => $menu['icon'] ?? 'circle', 'class' => 'menu-icon'])@endunless<div>{{ $menu['label'] }}</div>
        </a>
    @endif
</li>
