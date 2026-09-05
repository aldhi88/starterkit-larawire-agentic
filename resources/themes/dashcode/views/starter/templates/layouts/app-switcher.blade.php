@php
    $compact = $compact ?? false;
    $activeApp = $appOptions->firstWhere('active', true);
    $triggerLabel = $triggerLabel ?? ($activeApp['name'] ?? $currentAppName ?? 'App');
@endphp

<div class="starter-app-switcher" data-starter-app-switcher>
    <button type="button" class="starter-app-toggle flex h-[28px] w-[28px] cursor-pointer flex-col items-center justify-center rounded-full bg-slate-100 text-[20px] text-slate-900 lg:h-8 lg:w-8" aria-label="Tampilkan menu app" aria-expanded="false" data-starter-app-toggle>
        @include('starter.templates.layouts.icon', ['name' => 'apps'])
        <span class="starter-notification-dot"></span>
    </button>

    <div class="starter-app-panel" data-starter-app-menu>
        <div class="flex items-center justify-between px-4 py-3">
            <div class="text-sm font-medium text-slate-700">App Saya</div>
            <div class="text-xs text-slate-400">{{ $appOptions->count() }} tersedia</div>
        </div>
        <div class="divide-y divide-slate-100" role="menu">
            @forelse ($appOptions as $appOption)
                <a href="{{ $appOption['url'] }}" class="starter-app-option flex w-full items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-100 {{ $appOption['active'] ? 'bg-slate-100' : '' }}" target="_blank" rel="noopener noreferrer" role="menuitem" data-starter-app-link data-starter-app-name="{{ $appOption['name'] }}" data-starter-app-host="{{ parse_url($appOption['url'], PHP_URL_HOST) }}" title="Buka {{ $appOption['name'] }} di tab baru">
                    <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-primary-lt text-primary">
                        @include('starter.templates.layouts.icon', ['name' => $appOption['icon'], 'class' => 'm-0'])
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-slate-700">{{ $appOption['name'] }}</span>
                        <span class="mt-1 block text-xs text-slate-400">Buka di tab baru</span>
                    </span>
                </a>
            @empty
                <span class="starter-app-empty block px-4 py-3">Belum ada app tersedia</span>
            @endforelse
        </div>
    </div>
</div>
