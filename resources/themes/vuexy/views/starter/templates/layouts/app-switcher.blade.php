<div class="dropdown position-relative" data-starter-app-switcher>
    <button type="button" class="btn btn-icon btn-text-secondary rounded-pill" aria-label="Tampilkan menu app" aria-expanded="false" data-starter-app-toggle>@include('starter.templates.layouts.icon', ['name' => 'apps'])</button>
    <div class="dropdown-menu dropdown-menu-end mt-3 vuexy-app-menu" data-starter-app-menu>
        <h6 class="dropdown-header d-flex align-items-center justify-content-between"><span>App Saya</span><span class="badge bg-label-primary rounded-pill">{{ count($appOptions) }}</span></h6>
        @forelse ($appOptions as $appOption)
            <a class="dropdown-item d-flex align-items-center gap-3 {{ $appOption['active'] ? 'active' : '' }}" href="{{ $appOption['url'] }}" target="_blank" rel="noopener noreferrer" data-starter-app-link data-starter-app-name="{{ $appOption['name'] }}" data-starter-app-host="{{ parse_url($appOption['url'], PHP_URL_HOST) }}"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-primary">@include('starter.templates.layouts.icon', ['name' => $appOption['icon'], 'class' => 'icon-sm'])</span></span><span class="flex-grow-1">{{ $appOption['name'] }}</span>@if ($appOption['active'])<span class="badge bg-primary rounded-pill">Aktif</span>@endif</a>
        @empty
            <span class="dropdown-item-text">Belum ada app tersedia</span>
        @endforelse
    </div>
</div>
