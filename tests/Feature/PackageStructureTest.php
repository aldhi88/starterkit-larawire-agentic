<?php

use Aldhi88\StarterKit\Console\Commands\Starter\AppCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\DeployCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\InstallCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\ResetCommand;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Support\Facades\File;

function packageStructureLocalThemeCss(string $theme): ?string
{
    $path = StarterPaths::path("theme-intake/{$theme}/runtime/css/{$theme}.css");

    return is_file($path) ? file_get_contents($path) : null;
}

it('registers the prepared themes without storing their archives in the package repository', function (): void {
    $config = require StarterPaths::path('config/starter.php');
    $publicThemes = StarterPaths::path('public/themes');
    $publicThemeFiles = is_dir($publicThemes) ? File::allFiles($publicThemes) : [];

    expect(array_keys($config['themes']))->toBe(['tabler', 'dashcode', 'vuexy'])
        ->and($config['themes']['tabler']['layouts'])->toHaveKeys(['vertical', 'horizontal'])
        ->and($config['themes']['dashcode']['layouts'])->toHaveKeys(['vertical', 'horizontal'])
        ->and(is_dir(StarterPaths::path('theme-packages')))->toBeFalse()
        ->and($publicThemeFiles)->toBe([]);
});

it('maps generated host assets without carrying their payloads in Composer', function (): void {
    $config = require StarterPaths::path('config/starter.php');

    expect($config['themes']['tabler']['assets'])->toBe('assets/tabler')
        ->and($config['themes']['dashcode']['assets'])->toBe('assets/dashcode')
        ->and(glob(StarterPaths::path('docs/template/*/*.html')) ?: [])->toBe([]);

    foreach ([
        'tabler' => '834540b17f1f20fa888198de1dfc13ade5187305f02ece63312720dc6cad1898',
        'dashcode' => 'e4b4ee2a270a04c264ad3aa4f0aaa38fe5ae74e8f8c9fb3dd7466e74b2db422a',
    ] as $theme => $checksum) {
        $source = json_decode(
            (string) file_get_contents(StarterPaths::path('docs/template/'.$theme.'/source.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        expect($source['url'])->toBe(
            'https://raw.githubusercontent.com/aldhi88/starterkit-larawire-agentic-template/master/'.$theme.'.zip',
        )->and($source['archive_sha256'])->toBe($checksum);
    }
});

it('keeps one theme-named custom stylesheet and script for every theme', function (): void {
    foreach (['tabler', 'dashcode', 'vuexy'] as $theme) {
        $views = collect(File::allFiles(StarterPaths::path('resources/themes/'.$theme.'/views')))
            ->map(fn (SplFileInfo $file): string => $file->getContents())
            ->implode("\n");
        $manifest = json_decode(
            (string) file_get_contents(StarterPaths::path('docs/template/'.$theme.'/asset-manifest.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $targets = collect($manifest['files'])->pluck('target')->all();

        expect($views)->toContain("assets/{$theme}/css/{$theme}.css")
            ->toContain("assets/{$theme}/js/{$theme}.js")
            ->not->toContain("assets/{$theme}/css/starter-theme.css")
            ->not->toContain("assets/{$theme}/css/custom.css")
            ->not->toContain("assets/{$theme}/js/starter-theme.js")
            ->and($targets)->toContain('css/'.$theme.'.css', 'js/'.$theme.'.js')
            ->not->toContain('css/starter-theme.css', 'css/custom.css', 'js/starter-theme.js');
    }
});

it('keeps active vertical branches open without forcing horizontal dropdowns open', function (): void {
    $runtime = file_get_contents(StarterPaths::path('public/assets/starter/js/starter-runtime.js'));

    expect($runtime)->toContain("! detail.classList.contains('starter-horizontal-details')");

    foreach (['tabler', 'dashcode', 'vuexy'] as $theme) {
        $horizontalMenu = file_get_contents(StarterPaths::path(
            'resources/themes/'.$theme.'/views/starter/templates/layouts/menu-item-horizontal.blade.php',
        ));

        expect($horizontalMenu)->not->toContain('@if ($isExpanded) open @endif');
    }
});

it('keeps Vuexy shells on their native layout hierarchy', function (): void {
    $root = StarterPaths::path('resources/themes/vuexy/views/starter/templates/layouts');
    $app = file_get_contents($root.'/app.blade.php');
    $navbar = file_get_contents($root.'/navbar.blade.php');
    $horizontal = file_get_contents($root.'/navigation/horizontal.blade.php');
    $customCss = packageStructureLocalThemeCss('vuexy');

    expect($app)
        ->toContain("'layout-content-navbar' : 'layout-navbar-full layout-horizontal layout-without-menu'")
        ->toContain("@else\n                @include('starter.templates.layouts.navbar')")
        ->toContain("@if (\$starterLayout === 'vertical')\n                    @include('starter.templates.layouts.navbar')")
        ->and($navbar)
        ->toContain("'container-xxl navbar-detached bg-navbar-theme' : ''")
        ->and($horizontal)
        ->toContain('class="layout-menu-horizontal menu-horizontal menu flex-grow-0 bg-menu-theme"')
        ->toContain('class="menu-inner py-1"');

    if ($customCss !== null) {
        expect($customCss)->toContain('.layout-horizontal .content-wrapper > .menu-horizontal + main.container-xxl');
    }
});

it('keeps Vuexy page composition identical to the verified Tabler baseline', function (): void {
    $files = [
        'templates/landing.blade.php',
        'templates/app-dashboard.blade.php',
        'auth/login.blade.php',
        'auth/confirm-password.blade.php',
        'auth/lock-screen.blade.php',
        'profile/edit-my-profile.blade.php',
        'settings/settings-index.blade.php',
        'settings/client-profile.blade.php',
        'settings/security-settings.blade.php',
        'user-management/roles.blade.php',
        'user-management/users.blade.php',
        'user-management/role-form.blade.php',
        'user-management/user-form.blade.php',
        'user-management/powergrid/roles-toolbar.blade.php',
        'user-management/powergrid/users-toolbar.blade.php',
        'logs/activity-log-index.blade.php',
        'logs/powergrid/activity-logs-toolbar.blade.php',
    ];
    $normalize = static function (string $contents): string {
        $contents = preg_replace('/<style>.*?<\/style>/s', '', $contents) ?? $contents;
        $contents = preg_replace('/\s+(?:class|style)="[^"]*"/', '', $contents) ?? $contents;
        $contents = preg_replace("/'class'\s*=>\s*'[^']*'/", "'class' => 'theme-cosmetic'", $contents) ?? $contents;
        $contents = preg_replace('/\s+x-cloak\b/', '', $contents) ?? $contents;
        $contents = str_replace(
            ['text-azure', 'text-purple', 'text-green'],
            ['text-info', 'text-primary', 'text-success'],
            $contents,
        );

        return preg_replace('/\s+/', ' ', trim($contents)) ?? trim($contents);
    };

    foreach ($files as $file) {
        $tabler = file_get_contents(StarterPaths::path('resources/themes/tabler/views/starter/'.$file));
        $vuexy = file_get_contents(StarterPaths::path('resources/themes/vuexy/views/starter/'.$file));

        expect($normalize($vuexy))->toBe($normalize($tabler), $file.' changed the shared page composition.');
    }
});

it('keeps Vuexy cosmetics native, legible, centered, and proportionate', function (): void {
    $root = StarterPaths::path('resources/themes/vuexy/views/starter');
    $views = collect(File::allFiles($root))
        ->map(fn (SplFileInfo $file): string => $file->getContents())
        ->implode("\n");
    $css = packageStructureLocalThemeCss('vuexy');
    $menu = file_get_contents($root.'/templates/layouts/menu-item.blade.php');
    $profile = file_get_contents($root.'/profile/edit-my-profile.blade.php');
    $activityLog = file_get_contents($root.'/logs/activity-log-index.blade.php');
    $theme = file_get_contents(StarterPaths::path('src/Themes/Starter/VuexyPowerGridTheme.php'));
    $contract = json_decode(
        (string) file_get_contents(StarterPaths::path('docs/rules/theme-package-contract.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($views)
        ->toContain('bg-label-primary', 'bg-label-success', 'bg-label-info', 'bg-label-warning', 'bg-label-danger', 'bg-label-secondary')
        ->not->toMatch('/bg-(?:primary|blue|purple|green|success|azure|warning|danger|secondary)-lt/')
        ->not->toContain('btn-ghost-primary', 'btn-ghost-danger', 'status-lite')
        ->and($menu)
        ->toContain('menu-link menu-toggle starter-menu-toggle')
        ->not->toContain('bg-transparent w-100')
        ->and($profile)
        ->toContain('tab-content starter-profile-tab-content')
        ->toContain('alert alert-info mb-0 starter-password-guidance')
        ->toContain('btn btn-label-secondary')
        ->not->toContain('border rounded p-3 mb-4')
        ->and($activityLog)
        ->toContain('vuexy-activity-summary')
        ->toContain('vuexy-activity-stat-value')
        ->not->toContain('<div class="h2 mb-0">')
        ->and($theme)
        ->toContain("'footer' => 'starter-pg-footer vuexy-grid-footer'");

    if ($css !== null) {
        expect($css)
            ->toContain('.avatar { align-items: center;')
            ->toContain('.starter-profile-tab-content { padding: 0 !important; }')
            ->toContain('.starter-password-guidance .alert-icon { align-items: center;')
            ->toContain('[data-starter-region="section-navigation"] + [data-starter-region="section-header"] { padding-block: 1.5rem !important; }')
            ->toContain('.starter-client-logo-preview { align-items: center; background: var(--bs-secondary-bg); border: 1px dashed var(--bs-border-color);')
            ->toContain('block-size: 5rem; inline-size: 10rem;')
            ->toContain('.starter-client-logo-preview-image { block-size: 100%; inline-size: 100%; object-fit: contain; }')
            ->toContain('.vuexy-activity-stat-icon { --bs-avatar-size: 3rem; border-radius: .5rem; }')
            ->toContain('.vuexy-activity-stat-value { color: var(--bs-heading-color); font-size: 1.625rem; font-weight: 600; line-height: 1.1; }')
            ->toContain('.menu-vertical .menu-inner > .menu-item > .starter-menu-toggle { inline-size: calc(100% - 1.5rem); }')
            ->toContain('.starter-pg-footer { border-block-start: 1px solid var(--bs-border-color); min-block-size: 3.5rem; padding: .75rem 1rem; }')
            ->toContain('grid-template-columns: minmax(12rem, 1fr) auto minmax(16rem, 1fr);')
            ->toContain('.card-header:has(.card-header-tabs) { overflow-x: auto;');
    }

    expect($contract['cosmetic_selection_policy'])
        ->toMatchArray([
            'indexed_variant_shortlist_minimum' => 3,
            'indexed_variant_shortlist_maximum' => 5,
            'semantic_color_map_required' => true,
            'decorative_color_assignment_forbidden' => true,
            'tinted_surface_requires_explicit_matching_foreground' => true,
            'minimum_normal_text_contrast' => '4.5:1',
            'minimum_meaningful_graphic_contrast' => '3:1',
            'measured_spacing_and_proportion_required' => true,
        ]);
});

it('stretches two-column profile navigation consistently across every theme', function (): void {
    $contract = json_decode(
        (string) file_get_contents(StarterPaths::path('docs/rules/theme-package-contract.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    foreach (['tabler', 'dashcode', 'vuexy'] as $theme) {
        $profile = file_get_contents(StarterPaths::path("resources/themes/{$theme}/views/starter/profile/edit-my-profile.blade.php"));
        $css = packageStructureLocalThemeCss($theme);

        expect($profile)
            ->toContain('starter-profile-layout')
            ->toContain('starter-profile-nav-column')
            ->not->toContain('border rounded p-3 mb-4');

        if ($css !== null) {
            expect($css)
                ->toContain('starter-profile-nav-column')
                ->toMatch('/starter-profile-nav-column\s*>?\s*\[data-starter-region="section-navigation"\]\s*\{[^}]*(?:block-size|height)\s*:\s*100%/s');
        }
    }

    expect($contract['comparison_policy'])
        ->toMatchArray([
            'desktop_two_column_navigation_matches_content_height' => true,
            'stacked_navigation_uses_natural_height' => true,
            'account_summary_single_surface_desktop_row' => true,
            'password_guidance_full_row_before_new_credentials' => true,
        ]);
});

it('places password guidance before the paired new credentials across every theme', function (): void {
    foreach (['tabler', 'dashcode', 'vuexy'] as $theme) {
        $profile = file_get_contents(StarterPaths::path("resources/themes/{$theme}/views/starter/profile/edit-my-profile.blade.php"));
        $currentPassword = strpos($profile, 'id="profile-current-password"');
        $guidance = strpos($profile, 'data-starter-password-guidance');
        $newPassword = strpos($profile, 'id="profile-new-password"');
        $confirmation = strpos($profile, 'id="profile-password-confirmation"');

        expect($currentPassword)->toBeInt()
            ->and($guidance)->toBeInt()
            ->and($newPassword)->toBeInt()
            ->and($confirmation)->toBeInt()
            ->and($currentPassword)->toBeLessThan($guidance)
            ->and($guidance)->toBeLessThan($newPassword)
            ->and($newPassword)->toBeLessThan($confirmation);
    }

    $tabler = file_get_contents(StarterPaths::path('resources/themes/tabler/views/starter/profile/edit-my-profile.blade.php'));
    $vuexy = file_get_contents(StarterPaths::path('resources/themes/vuexy/views/starter/profile/edit-my-profile.blade.php'));
    $dashcode = file_get_contents(StarterPaths::path('resources/themes/dashcode/views/starter/profile/edit-my-profile.blade.php'));
    $dashcodeCss = packageStructureLocalThemeCss('dashcode');

    expect($tabler)->toContain('class="col-12" data-starter-password-guidance')
        ->and($vuexy)->toContain('class="col-12" data-starter-password-guidance')
        ->and($dashcode)->toContain('class="dashcode-profile-security-guide-row" data-starter-password-guidance');

    if ($dashcodeCss !== null) {
        expect($dashcodeCss)->toContain('.dashcode-profile-security-guide-row { grid-column:1/-1; }');
    }
});

it('keeps spacing between the vertical navigation heading and menu items', function (): void {
    $navigation = file_get_contents(StarterPaths::path(
        'resources/themes/tabler/views/starter/templates/layouts/navigation/vertical.blade.php',
    ));

    expect($navigation)
        ->toContain('class="nav-item px-0 px-lg-3 pt-3 pb-1 mb-1"')
        ->toContain('<span class="subheader">Menu Utama</span>');
});

it('keeps generated entry pages theme-owned and Dashcode presentation isolated', function (): void {
    $scaffolder = file_get_contents(StarterPaths::path('src/Services/Starter/StarterAppScaffolder.php'));
    $deploy = file_get_contents(StarterPaths::path('src/Console/Commands/Starter/DeployCommand.php'));
    $dashcodeRoot = StarterPaths::path('resources/themes/dashcode/views/starter');
    $dashcodeViews = collect(File::allFiles($dashcodeRoot))
        ->filter(fn (SplFileInfo $file): bool => $file->getExtension() === 'php')
        ->map(fn (SplFileInfo $file): string => $file->getContents())
        ->implode("\n");

    expect($scaffolder)
        ->toContain("@include('starter.templates.app-dashboard'")
        ->not->toContain('<div class="container-xl">')
        ->and($deploy)
        ->toContain("@include('starter.templates.landing')")
        ->not->toContain('<div class="container-xl">')
        ->and(is_file($dashcodeRoot.'/templates/app-dashboard.blade.php'))->toBeTrue()
        ->and(is_file($dashcodeRoot.'/templates/landing.blade.php'))->toBeTrue();

    foreach (['d-flex', 'd-block', 'fw-bold', 'ms-auto', 'modal-backdrop', 'data-bs-', 'container-xl'] as $forbidden) {
        expect($dashcodeViews)->not->toContain($forbidden);
    }

    foreach (['style="top:', 'style="max-height:', 'style="font-size:', 'style="width:', 'position-xl-sticky'] as $forcedStyle) {
        expect($dashcodeViews)->not->toContain($forcedStyle);
    }
});

it('uses native Dashcode form groups and toolbar composition', function (): void {
    $dashcodeRoot = StarterPaths::path('resources/themes/dashcode/views/starter');
    $formViews = collect([
        $dashcodeRoot.'/auth/confirm-password.blade.php',
        $dashcodeRoot.'/auth/lock-screen.blade.php',
        $dashcodeRoot.'/profile/edit-my-profile.blade.php',
        $dashcodeRoot.'/settings/security-settings.blade.php',
        $dashcodeRoot.'/user-management/user-form.blade.php',
    ])->map(fn (string $path): string => file_get_contents($path))->implode("\n");
    $toolbarViews = collect([
        $dashcodeRoot.'/logs/powergrid/activity-logs-toolbar.blade.php',
        $dashcodeRoot.'/user-management/powergrid/roles-toolbar.blade.php',
        $dashcodeRoot.'/user-management/powergrid/users-toolbar.blade.php',
    ])->map(fn (string $path): string => file_get_contents($path))->implode("\n");

    expect($formViews)
        ->not->toContain('input-group')
        ->not->toContain('form-select')
        ->toContain('class="relative"')
        ->toContain('form-control !pr-12')
        ->toContain('form-control appearance-none !pr-14')
        ->and($toolbarViews)
        ->not->toContain('dashcode-pg-toolbar')
        ->not->toContain('dashcode-pg-search-field')
        ->toContain('class="relative flex-1 max-w-md"')
        ->toContain('form-control margin-0 !pl-12')
        ->and(substr_count($toolbarViews, 'form-control margin-0 !pl-12'))->toBe(3);
});

it('contains wide PowerGrid content inside the Dashcode table frame', function (): void {
    $theme = file_get_contents(StarterPaths::path('src/Themes/Starter/DashcodePowerGridTheme.php'));
    $roleActions = file_get_contents(StarterPaths::path(
        'resources/themes/dashcode/views/starter/user-management/powergrid/roles-row-actions.blade.php',
    ));
    $activityLog = file_get_contents(StarterPaths::path(
        'resources/themes/dashcode/views/starter/logs/activity-log-index.blade.php',
    ));
    $roleForm = file_get_contents(StarterPaths::path(
        'resources/themes/dashcode/views/starter/user-management/role-form.blade.php',
    ));
    $footer = file_get_contents(StarterPaths::path(
        'resources/themes/dashcode/views/starter/powergrid/footer.blade.php',
    ));
    $filterViews = collect([
        StarterPaths::path('resources/themes/dashcode/views/starter/powergrid/filters/input-text.blade.php'),
        StarterPaths::path('resources/themes/dashcode/views/starter/powergrid/filters/select.blade.php'),
        StarterPaths::path('resources/themes/dashcode/views/starter/powergrid/filters/boolean.blade.php'),
    ])->map(fn (string $path): string => file_get_contents($path))->implode("\n");
    $themeCss = packageStructureLocalThemeCss('dashcode');

    expect(substr_count($theme, "'select' => 'form-control h-8 w-full !py-1'"))->toBe(4)
        ->and(substr_count($theme, "'input' => 'form-control h-8 w-full !py-1"))->toBe(2)
        ->and($theme)->toContain("'input' => 'flatpickr flatpickr-input form-control h-8 w-full !py-1'");

    expect($theme)
        ->toContain("'container' => 'starter-pg-container dashcode-data-table'")
        ->toContain("'div' => 'starter-pg-frame'")
        ->toContain("'thead' => 'border-t border-slate-100 bg-slate-100'")
        ->toContain("'th' => 'px-3 py-3 text-xs font-semibold uppercase text-slate-600 align-middle'")
        ->toContain("'td' => 'px-3 py-3 text-sm font-normal text-slate-600 align-middle break-words normal-case'")
        ->toContain("'table' => 'w-max min-w-full divide-y divide-slate-100 border-collapse'")
        ->toContain("'th' => 'text-center align-middle w-[50px] min-w-[48px] max-w-[50px]'")
        ->toContain("'view' => 'starter.powergrid.filters.select'")
        ->toContain("'view' => 'starter.powergrid.filters.boolean'")
        ->toContain("'view' => 'starter.powergrid.filters.input-text'")
        ->toContain("'select' => 'form-control !py-1'")
        ->toContain("'input' => 'table-checkbox block mx-auto'")
        ->and($footer)
        ->toContain('<span class="w-20">')
        ->not->toContain('starter-pg-select-icon')
        ->not->toContain('icons.down')
        ->and($filterViews)
        ->toContain('<select class="{{ $filterClasses }}"')
        ->toContain('<select class="{{ $selectClasses }}"')
        ->toContain("'flex flex-col space-y-2' => \$inline && \$showSelectOptions")
        ->not->toContain('icons.down')
        ->not->toContain('inset-y-0')
        ->not->toContain('style=')
        ->not->toContain('whitespace-nowrap normal-case')
        ->and($roleActions)->toContain('dashcode-table-dropdown dashcode-row-dropdown')
        ->and($activityLog)
        ->toContain('class="dashcode-activity-stats"')
        ->toContain('<table class="min-w-full divide-y divide-slate-100">')
        ->not->toContain('table-fixed')
        ->and($roleForm)->toContain('class="dashcode-access-accordion-panel"');

    if ($themeCss !== null) {
        expect($themeCss)
            ->toContain('.starter-pg-filter-cell { box-sizing:border-box; }')
            ->toContain('.dashcode-data-table .starter-pg-filter-cell input { margin-left:0; }')
            ->toContain('.starter-pg-filter { box-sizing:border-box;display:inline-block;max-width:none;width:fit-content; }')
            ->toContain('.starter-pg-filter input { box-sizing:border-box;field-sizing:content;min-width:6.5rem;width:8rem; }')
            ->toContain('.starter-pg-filter select { box-sizing:border-box;field-sizing:content;min-width:0;width:auto; }')
            ->toContain('.starter-pg-filter-cell .starter-pg-filter-number { box-sizing:border-box;field-sizing:content;max-width:9rem;min-width:6.5rem;width:8rem; }')
            ->not->toContain('.starter-pg-filter,.starter-pg-filter-cell')
            ->not->toContain('.starter-pg-table table')
            ->not->toContain('.starter-pg-select-icon')
            ->not->toContain('.starter-pg-page-size')
            ->not->toContain('.table-th {')
            ->not->toContain('.table-td {')
            ->not->toContain('.table-checkbox {')
            ->not->toContain('.form-control,');
    }
});

it('keeps Dashcode company sections and numeric suffix controls in native component structure', function (): void {
    $company = file_get_contents(StarterPaths::path(
        'resources/themes/dashcode/views/starter/settings/client-profile.blade.php',
    ));
    $security = file_get_contents(StarterPaths::path(
        'resources/themes/dashcode/views/starter/settings/security-settings.blade.php',
    ));
    $themeCss = packageStructureLocalThemeCss('dashcode');

    expect($company)
        ->toContain('class="card-body space-y-6"')
        ->and(substr_count($company, '<section class="space-y-4"'))->toBe(3)
        ->and($company)->not->toContain('starter-form-section-title mt-4')
        ->and(substr_count($security, 'form-control appearance-none !pr-14'))->toBe(3)
        ->and(substr_count($security, 'border-none px-3 flex items-center justify-center'))->toBe(3)
        ->and($security)
        ->toContain('class="dashcode-security-settings {{ $embedded ? \'\' : \'card\' }}"')
        ->toContain('dashcode-form-grid dashcode-form-grid-2 dashcode-section-gap mt-4')
        ->toContain('dashcode-form-grid dashcode-form-grid-2 mt-4')
        ->not->toContain('starter-switch-row mb-4')
        ->not->toContain('style=');

    if ($themeCss !== null) {
        expect($themeCss)
            ->toContain('.dashcode-security-settings .dashcode-stack { gap:1.5rem; }')
            ->toContain('.dashcode-security-settings .starter-switch-label { line-height:1.25; }')
            ->toContain('.dashcode-security-settings .dashcode-help-text { line-height:1.4;margin-top:0; }');
    }
});

it('keeps Dashcode account controls responsive and its app switcher compact', function (): void {
    $dashcodeRoot = StarterPaths::path('resources/themes/dashcode/views/starter');
    $profile = file_get_contents($dashcodeRoot.'/profile/edit-my-profile.blade.php');
    $header = file_get_contents($dashcodeRoot.'/templates/layouts/navigation/header.blade.php');
    $sidebar = file_get_contents($dashcodeRoot.'/templates/layouts/navigation/sidebar.blade.php');
    $appSwitcher = file_get_contents($dashcodeRoot.'/templates/layouts/app-switcher.blade.php');
    $accountMenu = file_get_contents($dashcodeRoot.'/templates/layouts/account-menu.blade.php');
    $themeCss = packageStructureLocalThemeCss('dashcode');

    expect($profile)
        ->toContain('<section class="card dashcode-account-summary mb-4" aria-label="Ringkasan akun"')
        ->toContain('dashcode-account-summary-row')
        ->toContain('dashcode-account-identity')
        ->toContain('dashcode-account-metadata')
        ->toContain('dashcode-account-meta-icon-info')
        ->toContain('dashcode-account-meta-icon-primary')
        ->toContain('dashcode-account-meta-icon-success')
        ->not->toContain('grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4')
        ->not->toContain('border border-slate-600 bg-slate-900 p-4 text-white')
        ->not->toContain('border border-info-500 bg-[#E5F9FF] p-4')
        ->not->toContain('border border-primary-500 bg-[#EAE5FF] p-4')
        ->not->toContain('border border-success-500 bg-[#EDFFE5] p-4')
        ->not->toContain('max-w-[516px]')
        ->and(substr_count($header, '<div class="xl:hidden">'))->toBe(2)
        ->and(substr_count($header, 'h-[28px] w-[28px]'))->toBe(2)
        ->and(substr_count($header, 'lg:h-8 lg:w-8'))->toBe(2)
        ->and($header)->toContain('uppercase leading-none tracking-wide')
        ->and($header)->toContain('font-semibold leading-tight')
        ->and($header)->toContain('nav-tools flex items-center space-x-3 leading-0 rtl:space-x-reverse lg:space-x-5')
        ->and($header)->not->toContain('class="flex items-center gap-2"')
        ->and($sidebar)->toContain('<div class="sidebarCloseIcon">')
        ->and($appSwitcher)
        ->toContain('h-[28px] w-[28px]')
        ->toContain('lg:h-8 lg:w-8')
        ->toContain('class="divide-y divide-slate-100" role="menu"')
        ->toContain('flex w-full items-center gap-3 px-4 py-3')
        ->not->toContain('starter-app-grid')
        ->not->toContain('starter-icon-button')
        ->and($accountMenu)
        ->toContain('class="inline-flex cursor-pointer items-center rounded-lg text-center text-sm font-medium text-slate-800"')
        ->toContain('h-7 w-7 flex-none rounded-full bg-slate-200 bg-cover bg-center ltr:mr-[10px] rtl:ml-[10px] lg:h-8 lg:w-8')
        ->toContain('hidden max-w-[160px] flex-none items-center overflow-hidden text-ellipsis whitespace-nowrap text-sm font-normal text-slate-600 lg:flex')
        ->toContain("'class' => 'ml-[10px] hidden h-[16px] w-[16px] lg:inline-block'")
        ->not->toContain('class="starter-account-summary"')
        ->not->toContain('class="starter-avatar')
        ->not->toContain('class="starter-account-name"')
        ->and(substr_count($profile, 'data-starter-region="account-summary"'))->toBe(1);

    if ($themeCss !== null) {
        expect($themeCss)
            ->toContain('.starter-shell-header,')
            ->toContain('.starter-shell-footer {')
            ->toContain('padding-inline: 0;')
            ->toContain('.sidebar-wrapper .starter-sidebar-details > summary,')
            ->toContain('.sidebar-wrapper .starter-submenu-link:not(.is-disabled),')
            ->toContain('.sidebar-wrapper a.navItem {')
            ->toContain('transition: color 0.2s ease;')
            ->toContain('.sidebar-wrapper a.navItem:hover {')
            ->toContain('background: transparent;')
            ->toContain('color: var(--starter-slate-700);')
            ->not->toContain('color: var(--starter-primary);')
            ->not->toContain('cursor: pointer;')
            ->toContain('.dashcode-account-summary { background:#fff;overflow:hidden; }')
            ->toContain('.dashcode-account-summary-row { align-items:center;display:grid;')
            ->toContain('.dashcode-account-metadata { display:grid;gap:1.25rem;grid-template-columns:repeat(3,minmax(0,1fr)); }')
            ->toContain('.dashcode-account-meta-icon-primary { background:var(--starter-primary-soft);color:var(--starter-primary); }')
            ->toContain(':where(.dashcode-app,.dashcode-auth) svg')
            ->not->toContain('.dashcode-app svg,.dashcode-auth svg')
            ->not->toContain('.starter-account-summary {')
            ->not->toContain('.starter-avatar {')
            ->not->toContain('.starter-account-name {')
            ->not->toContain('.starter-app-grid')
            ->not->toContain('.starter-app-option {');
    }
});

it('keeps theme cosmetics native while preserving separate custom extension points', function (): void {
    $dashcodeRoot = StarterPaths::path('resources/themes/dashcode/views/starter');
    $tablerRoot = StarterPaths::path('resources/themes/tabler/views/starter');
    $dashcodeSettings = file_get_contents($dashcodeRoot.'/settings/settings-index.blade.php');
    $dashcodeActivity = file_get_contents($dashcodeRoot.'/logs/activity-log-index.blade.php');
    $dashcodeDashboard = file_get_contents($dashcodeRoot.'/templates/app-dashboard.blade.php');
    $tablerSettings = file_get_contents($tablerRoot.'/settings/settings-index.blade.php');
    $tablerAuthLayout = file_get_contents($tablerRoot.'/templates/layouts/auth.blade.php');

    expect($dashcodeSettings)
        ->toContain('bg-[#E5F9FF]')
        ->toContain('bg-[#EDFFE5]')
        ->toContain('bg-[#FFEDE5]')
        ->toContain('bg-[#EAE5FF]')
        ->toContain('border border-info-500 bg-[#E5F9FF]')
        ->toContain('border border-success-500 bg-[#EDFFE5]')
        ->toContain('border border-warning-500 bg-[#FFEDE5]')
        ->toContain('border border-primary-500 bg-[#EAE5FF]')
        ->toContain('rounded-full bg-info-700 text-white')
        ->toContain('rounded-full bg-success-700 text-white')
        ->toContain('rounded-full bg-warning-700 text-white')
        ->toContain('rounded-full bg-primary-700 text-white')
        ->not->toContain('rounded-full bg-white bg-opacity-50')
        ->not->toContain('starter-settings-stat')
        ->and($dashcodeActivity)
        ->toContain('bg-[#EAE5FF]')
        ->toContain('bg-[#E5F9FF]')
        ->toContain('bg-[#EDFFE5]')
        ->toContain('border border-primary-500 bg-[#EAE5FF]')
        ->toContain('border border-info-500 bg-[#E5F9FF]')
        ->toContain('border border-success-500 bg-[#EDFFE5]')
        ->toContain('rounded-full bg-primary-700 text-white')
        ->toContain('rounded-full bg-info-700 text-white')
        ->toContain('rounded-full bg-success-700 text-white')
        ->not->toContain('rounded-full bg-white bg-opacity-50')
        ->not->toContain('starter-activity-stat')
        ->and($dashcodeDashboard)
        ->toContain('<section class="card" data-starter-region="primary-content">')
        ->toContain('<div class="card-body">{{ config($appConfigKey.\'.desc\') }}</div>')
        ->not->toContain('bg-gradient-to-r')
        ->not->toContain('Aplikasi aktif')
        ->and($tablerSettings)
        ->toContain('class="card card-sm"')
        ->not->toContain('bg-[#E5F9FF]')
        ->and($tablerAuthLayout)
        ->toContain("asset('assets/tabler/css/tabler.css')")
        ->not->toContain('<style>');

    foreach (['app', 'auth', 'landing'] as $layout) {
        expect(file_get_contents($dashcodeRoot.'/templates/layouts/'.$layout.'.blade.php'))
            ->toContain("asset('assets/dashcode/css/dashcode.css')");
        expect(file_get_contents($tablerRoot.'/templates/layouts/'.$layout.'.blade.php'))
            ->toContain("asset('assets/tabler/css/tabler.css')");
    }
});

it('caps Dashcode shell regions at the Tabler-compatible desktop width', function (): void {
    $appLayout = file_get_contents(StarterPaths::path(
        'resources/themes/dashcode/views/starter/templates/layouts/app.blade.php',
    ));
    $header = file_get_contents(StarterPaths::path(
        'resources/themes/dashcode/views/starter/templates/layouts/navigation/header.blade.php',
    ));
    $themeCss = packageStructureLocalThemeCss('dashcode');

    expect($appLayout)
        ->toContain('class="starter-content-container page-content px-[15px] pb-8 pt-6 md:px-6"')
        ->toContain('class="site-footer starter-shell-footer bg-white py-4 text-sm text-slate-500 ltr:ml-[248px] rtl:mr-[248px]"')
        ->toContain('class="starter-content-container grid grid-cols-1 px-[15px] md:grid-cols-2 md:gap-5 md:px-6"')
        ->toContain('class="text-center text-sm ltr:md:text-start rtl:md:text-right"')
        ->toContain('class="text-center text-sm ltr:md:text-right rtl:md:text-end"')
        ->and($header)
        ->toContain('class="app-header starter-shell-header bg-white shadow-sm ltr:ml-[248px] rtl:mr-[248px]"')
        ->toContain('class="starter-content-container flex h-full items-center justify-between gap-4 px-[15px] md:px-6"');

    if ($themeCss !== null) {
        expect($themeCss)
            ->toContain('.starter-content-container { margin-inline:auto;max-width:1680px;width:100%; }')
            ->toContain('.horizontalMenu .page-content { margin-inline:auto;max-width:1680px; }');
    }
});

it('keeps the complete AI contract and indexed theme atlases', function (): void {
    $contract = json_decode(
        (string) file_get_contents(StarterPaths::path('docs/rules/theme-package-contract.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    expect(is_file(StarterPaths::path('AGENTS.md')))->toBeTrue()
        ->and(is_file(StarterPaths::path('docs/rules/theme-package-contract.json')))->toBeTrue()
        ->and(is_file(StarterPaths::path('tools/build-theme-source-index.php')))->toBeTrue()
        ->and(is_dir(StarterPaths::path('docs/template/tabler/tabler-components')))->toBeFalse();

    foreach (['tabler' => 395, 'dashcode' => 74] as $theme => $htmlCount) {
        $root = StarterPaths::path('docs/template/'.$theme);
        $sourceIndex = json_decode((string) file_get_contents($root.'/source-index.json'), true, flags: JSON_THROW_ON_ERROR);
        $manifest = json_decode((string) file_get_contents($root.'/component-manifest.json'), true, flags: JSON_THROW_ON_ERROR);
        $source = json_decode((string) file_get_contents($root.'/source.json'), true, flags: JSON_THROW_ON_ERROR);

        expect(is_file($root.'/template.md'))->toBeTrue()
            ->and(is_file($root.'/runtime-map.md'))->toBeTrue()
            ->and(is_file($root.'/asset-manifest.json'))->toBeTrue()
            ->and($sourceIndex['html_files'])->toBe(count($sourceIndex['files']))
            ->and($sourceIndex['html_files'])->toBe($htmlCount)
            ->and(array_diff($contract['required_components'], collect($manifest['components'])->pluck('id')->all()))->toBe([])
            ->and($source['provider'])->toBe('github')
            ->and($source['url'])->toContain('raw.githubusercontent.com/')
            ->and($source['archive_sha256'])->toMatch('/^[a-f0-9]{64}$/')
            ->and(File::size($root.'/source-index.json'))->toBeLessThan(250_000);
    }

    $vuexyRoot = StarterPaths::path('docs/template/vuexy');
    $vuexyIndex = json_decode((string) file_get_contents($vuexyRoot.'/source-index.json'), true, flags: JSON_THROW_ON_ERROR);
    $vuexyManifest = json_decode((string) file_get_contents($vuexyRoot.'/component-manifest.json'), true, flags: JSON_THROW_ON_ERROR);
    $vuexySource = json_decode((string) file_get_contents($vuexyRoot.'/source.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($vuexyIndex['html_files'])->toBe(598)
        ->and($vuexyIndex['html_files'])->toBe(count($vuexyIndex['files']))
        ->and(array_diff($contract['required_components'], collect($vuexyManifest['components'])->pluck('id')->all()))->toBe([])
        ->and($vuexySource['provider'])->toBe('local')
        ->and($vuexySource['url'])->toBeNull()
        ->and($vuexySource['distribution'])->toBe('private')
        ->and($vuexySource['archive_sha256'])->toMatch('/^[a-f0-9]{64}$/');
});

it('keeps component selection and region order aligned across themes', function (): void {
    $contract = json_decode(
        (string) file_get_contents(StarterPaths::path('docs/rules/theme-package-contract.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    foreach ($contract['shared_layout_signatures'] as $view => $expectedRegions) {
        foreach (['tabler', 'dashcode', 'vuexy'] as $theme) {
            $contents = file_get_contents(StarterPaths::path('resources/themes/'.$theme.'/views/'.$view));
            preg_match_all('/data-starter-region="([^"]+)"/', $contents, $matches);

            expect($matches[1])
                ->toBe($expectedRegions, $theme.' must preserve the shared component layout for '.$view);
        }
    }

});

it('keeps issue specifications chronologically sortable throughout their lifecycle', function (): void {
    $agents = file_get_contents(StarterPaths::path('AGENTS.md'));
    $workflow = file_get_contents(StarterPaths::path('docs/rules/feature-development.md'));

    expect($agents)
        ->toContain('issues/<YYYY_MM_DD_HHMMSS>_feature_<name>.md')
        ->toContain('issues/archives/<YYYY_MM_DD_HHMMSS>_done_feature_<name>.md')
        ->and($workflow)
        ->toContain('issues/<YYYY_MM_DD_HHMMSS>_feature_<slug>.md')
        ->toContain('issues/<YYYY_MM_DD_HHMMSS>_bug_<slug>.md')
        ->toContain('issues/archives/<YYYY_MM_DD_HHMMSS>_done_feature_<slug>.md')
        ->toContain('issues/archives/<YYYY_MM_DD_HHMMSS>_done_bug_<slug>.md')
        ->toContain('insert only `done_` between the timestamp and type')
        ->not->toContain('issues/feature_<slug>_<YYYY_MM_DD_HHMMSS>.md')
        ->not->toContain('issues/archives/done_<original-name>.md');
});

it('keeps planning deterministic for LLM execution and table navigation semantics explicit', function (): void {
    $agents = file_get_contents(StarterPaths::path('AGENTS.md'));
    $workflow = file_get_contents(StarterPaths::path('docs/rules/feature-development.md'));
    $access = file_get_contents(StarterPaths::path('docs/rules/access-control.md'));
    $ui = file_get_contents(StarterPaths::path('docs/rules/ui-ux.md'));

    expect($agents)
        ->toContain('planning artifact')
        ->toContain('lower-cost coding LLM')
        ->toContain('complete request-receipt checklist')
        ->toContain('including small or already-clear items')
        ->toContain('do not create the issue specification or implement code before that confirmation')
        ->toContain('filter control fills 100%')
        ->toContain('icon whose meaning matches its label and destination')
        ->and($workflow)
        ->toContain('every requested outcome, user-visible behavior, constraint, explicit exclusion')
        ->toContain('Give each independently verifiable request its own numbered checklist item')
        ->toContain('Do not create a planning/issue file from the initial request alone')
        ->toContain('Preserve one-to-one traceability')
        ->toContain('follows the developer\'s language')
        ->toContain('## Artifact language gate')
        ->toContain('The developer\'s chat language never changes the artifact language')
        ->toContain('Any Indonesian or mixed-language narrative outside the allowed literals makes the artifact invalid')
        ->toContain('rewrite it before reporting that the file is ready')
        ->toContain('exact Indonesian UI copy')
        ->toContain('deterministic execution contract for a lower-cost coding LLM')
        ->toContain('no `TBD`')
        ->toContain('exact approved icon key')
        ->and($access)
        ->toContain('active-theme meaning matches the label and destination')
        ->toContain('Do not ship scaffold placeholders')
        ->and($ui)
        ->toContain('must use `width: 100%` and `box-sizing: border-box`')
        ->toContain('maximum practical requirement of its intrinsic filter wrapper, header, and representative displayed content')
        ->toContain('Determine a no-filter column from its header and representative content only')
        ->toContain('long fields such as descriptions may wrap across multiple readable lines')
        ->toContain('`table-responsive` horizontal scrolling over compressing controls or content');
});

it('publishes the official website and documentation entry points', function (): void {
    $composer = json_decode(
        (string) file_get_contents(StarterPaths::path('composer.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $readme = file_get_contents(StarterPaths::path('README.md'));
    $englishReadme = file_get_contents(StarterPaths::path('README.en.md'));

    expect($composer['homepage'])->toBe('https://starterkit-larawire.altekno.id/')
        ->and($composer['support']['docs'])->toBe('https://starterkit-larawire.altekno.id/docs')
        ->and($readme)
        ->toContain('[Website resmi](https://starterkit-larawire.altekno.id/)')
        ->toContain('[Dokumentasi](https://starterkit-larawire.altekno.id/docs)')
        ->and($englishReadme)
        ->toContain('[Official website](https://starterkit-larawire.altekno.id/en)')
        ->toContain('[Documentation](https://starterkit-larawire.altekno.id/en/docs)')
        ->and($readme)->not->toContain('Website dokumentasi resmi direncanakan')
        ->and($englishReadme)->not->toContain('official documentation website is planned');
});

it('defines a complete theme from one intake instruction without vendor visual locks', function (): void {
    $agents = file_get_contents(StarterPaths::path('AGENTS.md'));
    $integration = file_get_contents(StarterPaths::path('docs/rules/theme-integration.md'));
    $connector = file_get_contents(StarterPaths::path('installer/templates/agents-connector.md'));
    $ui = file_get_contents(StarterPaths::path('docs/rules/ui-ux.md'));
    $testing = file_get_contents(StarterPaths::path('docs/rules/testing.md'));
    $registry = file_get_contents(StarterPaths::path('src/Support/Starter/StarterThemeRegistry.php'));
    $contract = json_decode(
        (string) file_get_contents(StarterPaths::path('docs/rules/theme-package-contract.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $evidenceSchema = json_decode(
        (string) file_get_contents(StarterPaths::path('docs/rules/theme-verification-evidence.schema.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $ignore = file_get_contents(StarterPaths::path('.gitignore'));

    expect($agents)
        ->toContain('## One-shot theme integration contract')
        ->toContain('fail-closed state machine')
        ->toContain('.starter-theme-run/verification-evidence.json')
        ->toContain('A phase may become `pass` only from current-run command or browser evidence')
        ->toContain('Any change to source intake, baseline structure, contract, runtime Blade/CSS/JS, manifest, or ZIP invalidates')
        ->toContain('php tools/validate-theme-evidence.php <theme-key>')
        ->toContain('both a screenshot and recorded DOM/computed metrics at `1280x768` and `390x844`')
        ->toContain('theme-intake/<theme-key>-lama')
        ->toContain('PowerGrid width is content-driven')
        ->toContain('runtime/css/<theme-key>.css')
        ->toContain('executable only while working in the canonical')
        ->and($integration)->toContain('Integrasikan theme <theme-key> dari theme-intake/<theme-key> sampai siap dipilih installer.')
        ->and($integration)->toContain('Pipeline ini hanya boleh dijalankan pada repository package canonical')
        ->and($connector)->toContain('one-shot theme-integration pipeline is canonical package maintenance only')
        ->and($connector)->toContain('Do not execute it in this Laravel host')
        ->and($integration)->toContain('Ownership output one-shot')
        ->and($integration)->toContain('Kerjakan parity dalam dua tahap')
        ->and($integration)->toContain('class yang tampak valid')
        ->and($integration)->toContain('Dependency closure')
        ->and($integration)->toContain('source-index.json')
        ->and($integration)->toContain('component-manifest.json')
        ->and($integration)->toContain('asset-manifest.json')
        ->and($integration)->toContain('table-layout: fixed')
        ->and($integration)->toContain('Host pembanding dan audit side-by-side')
        ->and($integration)->toContain('starterkit-larawire-laravel-<theme-key>')
        ->and($integration)->toContain('Pada viewport lebar, ukur bounding rectangle shell secara langsung')
        ->and($integration)->toContain('dan content cap. Responsive padding berada pada satu constrained container')
        ->and($integration)->toContain('spacing, sizing, serta visibility breakpoint dari')
        ->and($integration)->toContain('Widget berwarna wajib memiliki hierarchy kontras yang jelas')
        ->and($integration)->toContain('minimal kontras grafis 3:1')
        ->and($integration)->toContain('Compound control dari dependency wajib diaudit dari DOM hasil render')
        ->and($integration)->toContain('Native select arrow tidak boleh dirender bersamaan dengan')
        ->and($integration)->toContain('hasil akhirnya seragam: ukur computed')
        ->and($integration)->toContain('height setiap input dan select')
        ->and($integration)->toContain('Packaging atomik')
        ->and($integration)->toContain('## Fail-closed execution protocol for lower-cost LLMs')
        ->and($integration)->toContain('Execute the following state machine in order')
        ->and($integration)->toContain('Sampling is forbidden')
        ->and($integration)->toContain('Objective geometry and visibility acceptance')
        ->and($integration)->toContain('document root horizontal overflow is exactly `0px`')
        ->and($integration)->toContain('tabs have at least `16px` clear separation')
        ->and($integration)->toContain('Any edit to intake, baseline structural views, contract, target')
        ->and($integration)->toContain('The last local command before a completion handoff')
        ->and($integration)->toContain('1280x768')
        ->and($ignore)->toContain('/theme-intake')
        ->and($ui)->toContain('practical intrinsic-width filter wrapper')
        ->and($ui)->toContain('Determine a no-filter column from its header and representative content only')
        ->and($ui)->not->toContain('Tabler PowerGrid follows')
        ->and($ui)->not->toContain('Always wrap tables inside a white background card')
        ->and($ui)->not->toContain('outermost `.modal`')
        ->and($testing)->toContain('fail-closed current-run ledger')
        ->toContain('CSS/media-query inspection is not rendered responsive evidence')
        ->and($contract['schema_version'])->toBe(2)
        ->and($registry)->toContain('private const PACKAGE_CONTRACT_SCHEMA_VERSION = 2;')
        ->and($contract['one_instruction']['authorized_outputs'])->toBe([
            'canonical-package-integration',
            'template-runtime-archive',
            'documentation-site-catalog',
            'local-theme-comparison-host',
        ])
        ->and($contract['execution_scope'])->toBe([
            'repository' => 'canonical-starterkit-larawire-agentic-package',
            'owner_managed_intake_required' => true,
            'derived_laravel_host_execution_forbidden' => true,
            'vendor_mutation_forbidden' => true,
        ])
        ->and($contract['one_instruction']['ordered_phases'])->toBe([
            'source-evidence-license-and-runtime-closure',
            'component-region-and-behavior-parity',
            'vendor-native-cosmetic-composition',
            'atomic-packaging-and-browser-verification',
        ])
        ->and($contract['deterministic_execution'])->toMatchArray([
            'mode' => 'fail-closed',
            'rules_must_be_read_in_full_before_editing' => true,
            'run_ledger_path' => 'theme-intake/<theme-key>/.starter-theme-run/verification-evidence.json',
            'run_ledger_schema' => 'docs/rules/theme-verification-evidence.schema.json',
            'completion_validator' => 'php tools/validate-theme-evidence.php <theme-key>',
            'pass_requires_current_run_evidence' => true,
            'sampling_forbidden' => true,
            'phase_order_enforced' => true,
            'invalidate_changed_stage_and_all_downstream' => true,
            'resume_from_first_non_passing_gate' => true,
        ])
        ->and($contract['deterministic_execution']['stage_order'])->toBe([
            '0-preflight',
            '1-source',
            '2-structure',
            '3-cosmetics',
            '4-browser',
            '5-package-docs',
            '6-final',
        ])
        ->and($contract['comparison_policy']['existing_theme_is_structural_target'])->toBeTrue()
        ->and($contract['comparison_policy']['existing_theme_is_cosmetic_target'])->toBeFalse()
        ->and($contract['comparison_policy']['structural_parity_precedes_cosmetics'])->toBeTrue()
        ->and($contract['native_css_policy']['compiled_class_existence_required'])->toBeTrue()
        ->and($contract['native_css_policy']['custom_stylesheet_target_pattern'])->toBe('runtime/css/<theme-key>.css')
        ->and($contract['native_css_policy']['custom_script_target_pattern'])->toBe('runtime/js/<theme-key>.js')
        ->and($contract['native_css_policy']['named_custom_assets_required_per_theme'])->toBeTrue()
        ->and($contract['powergrid_width_policy']['layout'])->toBe('content-driven-auto')
        ->and($contract['powergrid_width_policy']['fixed_table_layout_forbidden'])->toBeTrue()
        ->and($contract['powergrid_width_policy']['overflow_owner'])->toBe('inner-table-frame')
        ->and($contract['comparison_host_policy']['layout_order'])->toBe(['vertical', 'horizontal'])
        ->and($contract['browser_matrix']['desktop_safe_area'])->toBe('1280x768')
        ->and($contract['browser_matrix']['mobile_safe_area'])->toBe('390x844')
        ->and($contract['browser_matrix']['required_viewports'])->toBe(['1280x768', '390x844'])
        ->and($contract['browser_matrix']['screenshot_and_computed_metrics_required_per_row'])->toBeTrue()
        ->and($contract['browser_matrix']['computed_layout_inspection_always_required'])->toBeTrue()
        ->and($contract['browser_matrix']['css_source_inspection_cannot_replace_rendered_mobile_evidence'])->toBeTrue()
        ->and($contract['geometry_acceptance'])->toMatchArray([
            'root_horizontal_overflow_px' => 0,
            'structural_measurement_max_delta_px' => 4,
            'structural_measurement_max_delta_percent' => 2,
            'repeated_family_height_max_delta_px' => 2,
            'aligned_edge_max_delta_px' => 2,
            'optical_center_max_delta_px' => 1,
            'tab_to_section_heading_min_gap_px' => 16,
            'action_to_divider_or_edge_min_gap_px' => 16,
            'media_preview_visible_boundary_required' => true,
        ])
        ->and($contract['change_invalidation'])->toMatchArray([
            'global_selector_or_token_change_retests_all_consumers' => true,
            'component_change_retests_all_owner_pages_and_states' => true,
            'javascript_change_retests_initial_load_livewire_navigation_and_morph' => true,
            'archive_change_requires_reinstall_and_browser_smoke' => true,
        ])
        ->and($contract['completion_gates'])->toContain(
            'valid-current-run-verification-ledger',
            'verification-ledger-validator-passes',
            'current-input-fingerprints',
            'side-by-side-structural-parity',
            'full-page-state-matrix-without-sampling-or-skips',
            'screenshot-and-computed-metrics-per-browser-row',
            'objective-geometry-tolerances',
            'archive-manifest-checksum-consistency',
            'fresh-runtime-post-package-browser-smoke',
            'no-unexplained-todo-or-known-core-ui-bug',
        )
        ->and($evidenceSchema['$schema'])->toBe('https://json-schema.org/draft/2020-12/schema')
        ->and($evidenceSchema['required'])->toContain('fingerprints', 'stages', 'page_matrix', 'automated_checks', 'completion')
        ->and($evidenceSchema['properties']['page_matrix']['items']['required'])->toContain(
            'page_group',
            'baseline_screenshot',
            'target_screenshot',
            'vendor_references',
            'computed_metrics',
            'console_errors',
            'network_errors',
        )
        ->and($evidenceSchema['allOf'])->not->toBeEmpty()
        ->and(is_file(StarterPaths::path('tools/validate-theme-evidence.php')))->toBeTrue();
});

it('uses a required interactive installation wizard without public identity shortcuts', function (): void {
    $install = new InstallCommand;
    $app = new AppCommand;
    $deploy = new DeployCommand;
    $reset = new ResetCommand;

    expect($install->getDefinition()->hasOption('company'))->toBeFalse()
        ->and($install->getDefinition()->hasOption('email'))->toBeFalse()
        ->and($install->getDefinition()->hasOption('app'))->toBeFalse()
        ->and($install->getDefinition()->hasOption('app-name'))->toBeFalse()
        ->and($install->getDefinition()->hasOption('skip-default-app'))->toBeFalse()
        ->and($install->getName())->toBe('starter:install')
        ->and($app->getName())->toBe('starter:app')
        ->and($deploy->getName())->toBe('starter:deploy')
        ->and($reset->getName())->toBe('starter:reset');
});

it('does not store initial superuser credentials in package configuration', function (): void {
    $config = file_get_contents(StarterPaths::path('config/starter.php'));
    $environment = file_get_contents(StarterPaths::path('src/Installation/StarterEnvironmentManager.php'));

    expect($config)->not->toContain('STARTER_SUPERUSER')
        ->and($environment)->not->toContain('STARTER_SUPERUSER');
});

it('generates app tests in a PSR-4 namespace matching their directory', function (): void {
    $scaffolder = file_get_contents(StarterPaths::path('src/Services/Starter/StarterAppScaffolder.php'));

    expect($scaffolder)->toContain('namespace Tests\\Feature\\Apps\\\\{$className};');
});
