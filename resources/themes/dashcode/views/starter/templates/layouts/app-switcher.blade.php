@php
    $compact = $compact ?? false;
    $activeApp = $appOptions->firstWhere('active', true);
    $triggerLabel = $triggerLabel ?? ($activeApp['name'] ?? $currentAppName ?? 'App');
@endphp

<div class="starter-app-switcher" data-starter-app-switcher>
    <button type="button" class="starter-icon-button starter-app-toggle" aria-label="Tampilkan menu app" aria-expanded="false" data-starter-app-toggle>
        @include('starter.templates.layouts.icon', ['name' => 'apps'])
        <span class="starter-notification-dot"></span>
    </button>

    <div class="starter-app-panel" data-starter-app-menu>
        <div class="starter-app-panel-header">
            <div>
                <div class="text-sm font-semibold text-slate-800">App Saya</div>
                <div class="mt-1 text-xs text-slate-500">Pilih aplikasi yang ingin dibuka</div>
            </div>
        </div>
        <div class="starter-app-grid">
            @forelse ($appOptions as $appOption)
                <a href="{{ $appOption['url'] }}" class="starter-app-option {{ $appOption['active'] ? 'bg-primary-lt text-primary' : '' }}" target="_blank" rel="noopener noreferrer" data-starter-app-link data-starter-app-name="{{ $appOption['name'] }}" data-starter-app-host="{{ parse_url($appOption['url'], PHP_URL_HOST) }}" title="Buka {{ $appOption['name'] }} di tab baru">
                    @include('starter.templates.layouts.icon', ['name' => $appOption['icon']])
                    <span>{{ $appOption['name'] }}</span>
                </a>
            @empty
                <span class="starter-app-empty">Belum ada app tersedia</span>
            @endforelse
        </div>
    </div>
</div>
