@php
    $module = $module ?? $resource ?? 'dashboard';
    [$icon, $tone] = match ($module) {
        'posts', 'post_categories' => ['clipboard-text', 'primary'],
        'pages', 'page_categories' => ['file-text', 'info'],
        'cards', 'card_categories' => ['layout-cards', 'warning'],
        'media', 'galleries' => ['photo', 'success'],
        'menus', 'menu_categories' => ['menu-2', 'primary'],
        'comments' => ['messages', 'warning'],
        default => ['layout-dashboard', 'primary'],
    };
    $iconClasses = match (\Aldhi88\StarterKit\Support\Starter\StarterTheme::key()) {
        'vuexy' => 'avatar bg-label-'.$tone,
        'dashcode' => 'inline-flex items-center justify-center rounded bg-'.$tone.'-100 text-'.$tone.'-500',
        default => 'avatar bg-'.$tone.'-lt text-'.$tone,
    };
@endphp
<span class="{{ $iconClasses }} cms-module-icon" aria-hidden="true">@include('starter.templates.layouts.icon', ['name' => $icon, 'class' => 'icon'])</span>
