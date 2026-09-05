<?php

use Aldhi88\StarterKit\Console\Commands\Starter\AppCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\DeployCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\InstallCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\ResetCommand;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Support\Facades\File;

it('registers the prepared themes without storing their archives in the package repository', function (): void {
    $config = require StarterPaths::path('config/starter.php');
    $publicThemes = StarterPaths::path('public/themes');
    $publicThemeFiles = is_dir($publicThemes) ? File::allFiles($publicThemes) : [];

    expect(array_keys($config['themes']))->toBe(['tabler', 'dashcode'])
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
        'tabler' => '285b006c61c5b945cb57692ca767eda9851b377f002094d951c4872e93a83846',
        'dashcode' => '43a6970ad1a84e0bb1830cbd219f1a392cae80f551d731462b4334aafb1b55a4',
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

it('keeps active vertical branches open without forcing horizontal dropdowns open', function (): void {
    $runtime = file_get_contents(StarterPaths::path('public/assets/starter/js/starter-runtime.js'));

    expect($runtime)->toContain("! detail.classList.contains('starter-horizontal-details')");

    foreach (['tabler', 'dashcode'] as $theme) {
        $horizontalMenu = file_get_contents(StarterPaths::path(
            'resources/themes/'.$theme.'/views/starter/templates/layouts/menu-item-horizontal.blade.php',
        ));

        expect($horizontalMenu)->not->toContain('@if ($isExpanded) open @endif');
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
        ->toContain('form-control !pl-12');
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
        StarterPaths::path('resources/themes/dashcode/views/starter/powergrid/filters/select.blade.php'),
        StarterPaths::path('resources/themes/dashcode/views/starter/powergrid/filters/boolean.blade.php'),
    ])->map(fn (string $path): string => file_get_contents($path))->implode("\n");
    $themeCss = file_get_contents(StarterPaths::path('theme-intake/dashcode/runtime/css/starter-theme.css'));

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
        ->toContain("'select' => 'form-control !py-1'")
        ->toContain("'input' => 'table-checkbox block mx-auto'")
        ->and($footer)
        ->toContain('<span class="w-20">')
        ->not->toContain('starter-pg-select-icon')
        ->not->toContain('icons.down')
        ->and($filterViews)
        ->toContain('<select class="{{ $filterClasses }}"')
        ->toContain('<select class="{{ $selectClasses }}"')
        ->not->toContain('icons.down')
        ->not->toContain('inset-y-0')
        ->not->toContain('style=')
        ->and($themeCss)
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
        ->not->toContain('.form-control,')
        ->not->toContain('whitespace-nowrap normal-case')
        ->and($roleActions)->toContain('dashcode-table-dropdown dashcode-row-dropdown')
        ->and($activityLog)
        ->toContain('class="dashcode-activity-stats"')
        ->toContain('<table class="min-w-full divide-y divide-slate-100">')
        ->not->toContain('table-fixed')
        ->and($roleForm)->toContain('class="dashcode-access-accordion-panel"');
});

it('keeps Dashcode company sections and numeric suffix controls in native component structure', function (): void {
    $company = file_get_contents(StarterPaths::path(
        'resources/themes/dashcode/views/starter/settings/client-profile.blade.php',
    ));
    $security = file_get_contents(StarterPaths::path(
        'resources/themes/dashcode/views/starter/settings/security-settings.blade.php',
    ));
    $themeCss = file_get_contents(StarterPaths::path('theme-intake/dashcode/runtime/css/starter-theme.css'));

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
        ->not->toContain('style=')
        ->and($themeCss)
        ->toContain('.dashcode-security-settings .dashcode-stack { gap:1.5rem; }')
        ->toContain('.dashcode-security-settings .starter-switch-label { line-height:1.25; }')
        ->toContain('.dashcode-security-settings .dashcode-help-text { line-height:1.4;margin-top:0; }');
});

it('keeps Dashcode account controls responsive and its app switcher compact', function (): void {
    $dashcodeRoot = StarterPaths::path('resources/themes/dashcode/views/starter');
    $profile = file_get_contents($dashcodeRoot.'/profile/edit-my-profile.blade.php');
    $header = file_get_contents($dashcodeRoot.'/templates/layouts/navigation/header.blade.php');
    $sidebar = file_get_contents($dashcodeRoot.'/templates/layouts/navigation/sidebar.blade.php');
    $appSwitcher = file_get_contents($dashcodeRoot.'/templates/layouts/app-switcher.blade.php');
    $themeCss = file_get_contents(StarterPaths::path('theme-intake/dashcode/runtime/css/starter-theme.css'));

    expect($profile)
        ->toContain('<section class="mb-4" aria-label="Ringkasan akun"')
        ->not->toContain('<section class="card mb-4 p-4"')
        ->toContain('grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4')
        ->toContain('border border-slate-600 bg-slate-900 p-4 text-white')
        ->toContain('border border-info-500 bg-[#E5F9FF] p-4')
        ->toContain('border border-primary-500 bg-[#EAE5FF] p-4')
        ->toContain('border border-success-500 bg-[#EDFFE5] p-4')
        ->not->toContain('max-w-[516px]')
        ->and(substr_count($header, '<div class="xl:hidden">'))->toBe(2)
        ->and($sidebar)->toContain('<div class="sidebarCloseIcon">')
        ->and($appSwitcher)
        ->toContain('class="divide-y divide-slate-100" role="menu"')
        ->toContain('flex w-full items-center gap-3 px-4 py-3')
        ->not->toContain('starter-app-grid')
        ->and($themeCss)
        ->not->toContain('.starter-app-grid')
        ->not->toContain('.starter-app-option {');
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
        ->not->toContain('starter-settings-stat')
        ->and($dashcodeActivity)
        ->toContain('bg-[#EAE5FF]')
        ->toContain('bg-[#E5F9FF]')
        ->toContain('bg-[#EDFFE5]')
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
        ->toContain("asset('assets/tabler/css/custom.css')")
        ->not->toContain('<style>')
        ->and(is_file(StarterPaths::path('theme-intake/dashcode/runtime/css/custom.css')))->toBeTrue()
        ->and(is_file(StarterPaths::path('theme-intake/tabler/runtime/css/custom.css')))->toBeTrue();

    foreach (['app', 'auth', 'landing'] as $layout) {
        expect(file_get_contents($dashcodeRoot.'/templates/layouts/'.$layout.'.blade.php'))
            ->toContain("asset('assets/dashcode/css/custom.css')");
        expect(file_get_contents($tablerRoot.'/templates/layouts/'.$layout.'.blade.php'))
            ->toContain("asset('assets/tabler/css/custom.css')");
    }
});

it('caps Dashcode shell regions at the Tabler-compatible desktop width', function (): void {
    $appLayout = file_get_contents(StarterPaths::path(
        'resources/themes/dashcode/views/starter/templates/layouts/app.blade.php',
    ));
    $header = file_get_contents(StarterPaths::path(
        'resources/themes/dashcode/views/starter/templates/layouts/navigation/header.blade.php',
    ));
    $themeCss = file_get_contents(StarterPaths::path('theme-intake/dashcode/runtime/css/starter-theme.css'));

    expect($appLayout)
        ->toContain('class="starter-content-container page-content px-[15px] pb-8 pt-6 md:px-6"')
        ->toContain('class="starter-content-container flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"')
        ->and($header)
        ->toContain('class="starter-content-container flex h-full items-center justify-between gap-4"')
        ->and($themeCss)
        ->toContain('.starter-content-container { margin-inline:auto;max-width:1680px;width:100%; }')
        ->toContain('.horizontalMenu .page-content { margin-inline:auto;max-width:1680px; }');
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
});

it('keeps component selection and region order aligned across themes', function (): void {
    $contract = json_decode(
        (string) file_get_contents(StarterPaths::path('docs/rules/theme-package-contract.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    foreach ($contract['shared_layout_signatures'] as $view => $expectedRegions) {
        foreach (['tabler', 'dashcode'] as $theme) {
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
    $contract = json_decode(
        (string) file_get_contents(StarterPaths::path('docs/rules/theme-package-contract.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $ignore = file_get_contents(StarterPaths::path('.gitignore'));

    expect($agents)
        ->toContain('## One-shot theme integration contract')
        ->toContain('theme-intake/<theme-key>-lama')
        ->toContain('PowerGrid width is content-driven')
        ->toContain('css/custom.css')
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
        ->and($integration)->toContain('Packaging atomik')
        ->and($integration)->toContain('1280x768')
        ->and($ignore)->toContain('/theme-intake')
        ->and($ui)->toContain('practical intrinsic-width filter wrapper')
        ->and($ui)->toContain('Determine a no-filter column from its header and representative content only')
        ->and($ui)->not->toContain('Tabler PowerGrid follows')
        ->and($ui)->not->toContain('Always wrap tables inside a white background card')
        ->and($ui)->not->toContain('outermost `.modal`')
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
        ->and($contract['comparison_policy']['existing_theme_is_visual_target'])->toBeFalse()
        ->and($contract['comparison_policy']['structural_parity_precedes_cosmetics'])->toBeTrue()
        ->and($contract['native_css_policy']['compiled_class_existence_required'])->toBeTrue()
        ->and($contract['native_css_policy']['custom_stylesheet_target'])->toBe('runtime/css/custom.css')
        ->and($contract['powergrid_width_policy']['layout'])->toBe('content-driven-auto')
        ->and($contract['powergrid_width_policy']['fixed_table_layout_forbidden'])->toBeTrue()
        ->and($contract['powergrid_width_policy']['overflow_owner'])->toBe('inner-table-frame')
        ->and($contract['comparison_host_policy']['layout_order'])->toBe(['vertical', 'horizontal'])
        ->and($contract['browser_matrix']['desktop_safe_area'])->toBe('1280x768')
        ->and($contract['completion_gates'])->toContain(
            'side-by-side-structural-parity',
            'archive-manifest-checksum-consistency',
            'no-unexplained-todo-or-known-core-ui-bug',
        );
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
