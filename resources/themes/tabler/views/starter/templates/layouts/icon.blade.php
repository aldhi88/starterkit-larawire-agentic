@php
    $rawName = (string) ($name ?? 'circle');
    $icon = match ($rawName) {
        'ri-global-line' => 'world',
        'ri-apps-line', 'ri-apps-2-line' => 'apps',
        'ri-dashboard-line' => 'layout-dashboard',
        'ri-folder-line' => 'folder',
        'ri-user-settings-line', 'user-management' => 'users-group',
        default => str($rawName)->replaceStart('ri-', '')->replaceEnd('-line', '')->toString(),
    };

    $allowedIcons = [
        'apps',
        'alert-triangle',
        'arrow-left',
        'activity',
        'building',
        'brand-google',
        'check',
        'chevron-down',
        'chevron-right',
        'chevron-up',
        'circle',
        'circle-check',
        'circle-x',
        'dots',
        'dots-vertical',
        'eye',
        'eye-off',
        'folder',
        'folders',
        'history',
        'file-plus',
        'info-circle',
        'layout-dashboard',
        'lock',
        'logout',
        'menu-2',
        'clipboard-text',
        'report-analytics',
        'school',
        'shield-check',
        'shield-lock',
        'settings',
        'table',
        'trash',
        'user',
        'user-circle',
        'user-plus',
        'users',
        'users-group',
        'world',
        'search',
    ];

    $icon = in_array($icon, $allowedIcons, true) ? $icon : 'circle';
    $classes = trim('icon icon-tabler icon-tabler-'.$icon.' '.($class ?? ''));
@endphp

<svg xmlns="http://www.w3.org/2000/svg" class="{{ $classes }}" width="{{ $size ?? 24 }}" height="{{ $size ?? 24 }}" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    <path stroke="none" d="M0 0h24v24H0z" fill="none" />

    @switch($icon)
        @case('apps')
            <path d="M4 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
            <path d="M14 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
            <path d="M4 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
            <path d="M14 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
            @break

        @case('alert-triangle')
            <path d="M12 9v4" />
            <path d="M12 17h.01" />
            <path d="M10.24 3.957l-8.422 14.06a1.989 1.989 0 0 0 1.7 2.983h16.845a1.989 1.989 0 0 0 1.7 -2.983l-8.423 -14.06a1.989 1.989 0 0 0 -3.4 0z" />
            @break

        @case('arrow-left')
            <path d="M5 12l14 0" />
            <path d="M5 12l6 6" />
            <path d="M5 12l6 -6" />
            @break

        @case('activity')
            <path d="M3 12h4l3 8l4 -16l3 8h4" />
            @break

        @case('building')
            <path d="M3 21l18 0" />
            <path d="M9 8l1 0" />
            <path d="M9 12l1 0" />
            <path d="M9 16l1 0" />
            <path d="M14 8l1 0" />
            <path d="M14 12l1 0" />
            <path d="M14 16l1 0" />
            <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" />
            @break

        @case('brand-google')
            <text x="12" y="17" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="17" font-weight="800" fill="currentColor" stroke="none">G</text>
            @break

        @case('check')
            <path d="M5 12l5 5l10 -10" />
            @break

        @case('circle')
            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
            @break

        @case('clipboard-text')
            <path d="M9 5h6" />
            <path d="M9 3h6a2 2 0 0 1 2 2v1h1a2 2 0 0 1 2 2v11a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2h1v-1a2 2 0 0 1 2 -2z" />
            <path d="M9 12h6" />
            <path d="M9 16h6" />
            @break

        @case('chevron-down')
            <path d="M6 9l6 6l6 -6" />
            @break

        @case('chevron-right')
            <path d="M9 6l6 6l-6 6" />
            @break

        @case('chevron-up')
            <path d="M6 15l6 -6l6 6" />
            @break

        @case('circle-check')
            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
            <path d="M9 12l2 2l4 -4" />
            @break

        @case('circle-x')
            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
            <path d="M10 10l4 4m0 -4l-4 4" />
            @break

        @case('dots')
            <path d="M5 12m-1 0a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" />
            <path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" />
            <path d="M19 12m-1 0a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" />
            @break

        @case('dots-vertical')
            <path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" />
            <path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" />
            <path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" />
            @break

        @case('eye')
            <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
            <path d="M21 12c-2.4 4 -5.4 6 -9 6s-6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6s6.6 2 9 6" />
            @break

        @case('eye-off')
            <path d="M3 3l18 18" />
            <path d="M10.6 10.6a2 2 0 1 0 2.8 2.8" />
            <path d="M9.9 4.2a9.1 9.1 0 0 1 2.1 -.2c3.6 0 6.6 2 9 6a16.4 16.4 0 0 1 -2 2.7" />
            <path d="M6.6 6.6c-1.3 .9 -2.5 2 -3.6 3.4c2.4 4 5.4 6 9 6c1 0 2 -.2 2.9 -.5" />
            @break

        @case('folder')
            <path d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2" />
            @break

        @case('folders')
            <path d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2" />
            <path d="M3 14v-7a2 2 0 0 1 2 -2" />
            @break

        @case('history')
            <path d="M12 8l0 4l2 2" />
            <path d="M3.05 11a9 9 0 1 0 .5 -3m-.5 -4v4h4" />
            @break

        @case('file-plus')
            <path d="M14 3v4a1 1 0 0 0 1 1h4" />
            <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
            <path d="M12 11v6" />
            <path d="M9 14h6" />
            @break

        @case('info-circle')
            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
            <path d="M12 9h.01" />
            <path d="M11 12h1v4h1" />
            @break

        @case('layout-dashboard')
            <path d="M4 4h6v8h-6z" />
            <path d="M4 16h6v4h-6z" />
            <path d="M14 12h6v8h-6z" />
            <path d="M14 4h6v4h-6z" />
            @break

        @case('lock')
            <path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" />
            <path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" />
            <path d="M8 11v-4a4 4 0 1 1 8 0v4" />
            @break

        @case('logout')
            <path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" />
            <path d="M9 12h12l-3 -3" />
            <path d="M18 15l3 -3" />
            @break

        @case('menu-2')
            <path d="M4 6l16 0" />
            <path d="M4 12l16 0" />
            <path d="M4 18l16 0" />
            @break

        @case('report-analytics')
            <path d="M9 17v-5" />
            <path d="M12 17v-1" />
            <path d="M15 17v-3" />
            <path d="M5 4h14a2 2 0 0 1 2 2v14h-18v-14a2 2 0 0 1 2 -2z" />
            <path d="M3 20h18" />
            @break

        @case('school')
            <path d="M3 10l9 -5l9 5l-9 5z" />
            <path d="M7 12v5c3 2 7 2 10 0v-5" />
            <path d="M21 10v6" />
            @break

        @case('shield-check')
            <path d="M11.46 20.846a12 12 0 0 1 -7.46 -10.846v-4l8 -3l8 3v4a12 12 0 0 1 -7.46 10.846a1 1 0 0 1 -1.08 0z" />
            <path d="M9 12l2 2l4 -4" />
            @break

        @case('shield-lock')
            <path d="M12 22s8 -4 8 -10v-5l-8 -3l-8 3v5c0 6 8 10 8 10" />
            <path d="M10 13a2 2 0 1 1 4 0v1" />
            <path d="M9 14h6v4h-6z" />
            @break

        @case('settings')
            <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" />
            <path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
            @break

        @case('table')
            <path d="M4 5a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" />
            <path d="M4 10h16" />
            <path d="M10 3v18" />
            @break

        @case('trash')
            <path d="M4 7h16" />
            <path d="M10 11v6" />
            <path d="M14 11v6" />
            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
            @break

        @case('search')
            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
            <path d="M21 21l-6 -6" />
            @break

        @case('user')
            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
            @break

        @case('user-circle')
            <path d="M12 12a3 3 0 1 0 0 -6a3 3 0 0 0 0 6z" />
            <path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855" />
            <path d="M12 21a9 9 0 1 0 0 -18a9 9 0 0 0 0 18z" />
            @break

        @case('user-plus')
            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
            <path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
            <path d="M16 19h6" />
            <path d="M19 16v6" />
            @break

        @case('users')
            <path d="M9 7a4 4 0 1 0 0 8a4 4 0 0 0 0 -8z" />
            <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
            @break

        @case('users-group')
            <path d="M10 13a4 4 0 1 0 0 -8a4 4 0 0 0 0 8z" />
            <path d="M3 21v-2a4 4 0 0 1 4 -4h6a4 4 0 0 1 4 4v2" />
            <path d="M17 5a3 3 0 0 1 0 6" />
            <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
            @break

        @case('world')
            <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
            <path d="M3.6 9h16.8" />
            <path d="M3.6 15h16.8" />
            <path d="M11.5 3a17 17 0 0 0 0 18" />
            <path d="M12.5 3a17 17 0 0 1 0 18" />
            @break

        @default
            <path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" />
    @endswitch
</svg>
