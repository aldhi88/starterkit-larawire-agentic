@php
    $compact = $compact ?? false;
    $activeApp = $appOptions->firstWhere('active', true);
    $triggerLabel = $triggerLabel ?? ($activeApp['name'] ?? $currentAppName ?? 'App');
@endphp

<div class="nav-item dropdown {{ $compact ? '' : 'd-none d-md-flex me-3' }}" data-starter-app-switcher>
    <a href="#" class="nav-link px-0" tabindex="-1" aria-label="Tampilkan menu app" aria-expanded="false" data-starter-app-toggle>
        @include('starter.templates.layouts.icon', ['name' => 'apps'])
        <span class="badge bg-primary"></span>
    </a>

    <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card" data-starter-app-menu>
        <div class="card">
            <div class="card-header">
                <div class="card-title">App Saya</div>
            </div>
            <div class="card-body scroll-y p-2" style="max-height: 50vh">
                <div class="row g-0">
                    @forelse ($appOptions as $appOption)
                        <div class="col-4">
                            <a href="{{ $appOption['url'] }}" class="d-flex flex-column flex-center text-center text-secondary py-2 px-2 link-hoverable {{ $appOption['active'] ? 'bg-primary-lt text-primary' : '' }}" target="_blank" rel="noopener noreferrer" data-starter-app-link data-starter-app-name="{{ $appOption['name'] }}" data-starter-app-host="{{ parse_url($appOption['url'], PHP_URL_HOST) }}" title="Buka {{ $appOption['name'] }} di tab baru">
                                @include('starter.templates.layouts.icon', ['name' => $appOption['icon'], 'class' => 'w-6 h-6 mx-auto mb-2'])
                                <span class="h5 mb-0">{{ $appOption['name'] }}</span>
                            </a>
                        </div>
                    @empty
                        <div class="col-12">
                            <span class="d-block text-secondary px-3 py-2">Belum ada app tersedia</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
