<nav class="layout-navbar navbar navbar-expand-xl align-items-center {{ $starterLayout === 'vertical' ? 'container-xxl navbar-detached bg-navbar-theme' : '' }}" id="layout-navbar" aria-label="Navigasi akun">
    @if ($starterLayout === 'horizontal')
        <div class="container-xxl">
            <div class="navbar-brand app-brand d-none d-xl-flex py-0 me-6 ms-0">
                @include('starter.templates.layouts.brand', ['url' => $currentDashboardUrl])
            </div>
    @endif

    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <button type="button" class="nav-item nav-link btn btn-text-secondary btn-icon rounded-pill px-0" data-vuexy-menu-toggle aria-label="Buka navigasi" aria-expanded="false">
            @include('starter.templates.layouts.icon', ['name' => 'menu-2', 'class' => 'icon-md'])
        </button>
    </div>
    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
        <div class="navbar-nav align-items-center me-auto">
            <div class="nav-item d-flex align-items-center gap-3">
                <span class="avatar avatar-sm d-none d-sm-inline-flex"><span class="avatar-initial rounded bg-label-primary">@include('starter.templates.layouts.icon', ['name' => 'layout-dashboard', 'class' => 'icon-sm'])</span></span>
                <div class="lh-sm">
                    <span class="small text-body-secondary d-block">App Aktif</span>
                    <span class="fw-semibold text-heading text-truncate d-block" data-starter-current-app-name>{{ $currentAppName }}</span>
                </div>
            </div>
        </div>
        <ul class="navbar-nav flex-row align-items-center ms-md-auto gap-1">
            <li class="nav-item">@includeIf('extensions.starter.header-actions.index', ['compact' => false])</li>
            <li class="nav-item">@include('starter.templates.layouts.app-switcher')</li>
            <li class="nav-item navbar-dropdown dropdown-user dropdown">@include('starter.templates.layouts.account-menu')</li>
        </ul>
    </div>

    @if ($starterLayout === 'horizontal')
        </div>
    @endif
</nav>
