<?php

use Aldhi88\StarterKit\Console\Commands\Starter\AppCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\DeployCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\InstallCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\ResetCommand;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Support\Facades\File;

it('registers only the prepared Tabler theme without storing its archive in the package repository', function (): void {
    $config = require StarterPaths::path('config/starter.php');
    $publicThemes = StarterPaths::path('public/themes');
    $publicThemeFiles = is_dir($publicThemes) ? File::allFiles($publicThemes) : [];

    expect(array_keys($config['themes']))->toBe(['tabler'])
        ->and($config['themes']['tabler']['layouts'])->toHaveKeys(['vertical', 'horizontal'])
        ->and(is_dir(StarterPaths::path('theme-packages')))->toBeFalse()
        ->and($publicThemeFiles)->toBe([]);
});

it('maps generated host assets without carrying the payload in Composer', function (): void {
    $config = require StarterPaths::path('config/starter.php');
    $source = json_decode(
        (string) file_get_contents(StarterPaths::path('docs/template/tabler/source.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($config['themes']['tabler']['assets'])->toBe('assets/tabler')
        ->and($source['url'])->toBe('https://raw.githubusercontent.com/aldhi88/starterkit-larawire-agentic-template/main/tabler.zip')
        ->and($source['archive_sha256'])->toBe('473ccd5f40f99e3ad7c5965946705007feafaf2a411450f3263b1561ead864a9')
        ->and(glob(StarterPaths::path('docs/template/*/*.html')) ?: [])->toBe([]);
});

it('keeps active vertical branches open without forcing horizontal dropdowns open', function (): void {
    $runtime = file_get_contents(StarterPaths::path('public/assets/starter/js/starter-runtime.js'));

    expect($runtime)->toContain("! detail.classList.contains('starter-horizontal-details')");

    foreach (['tabler'] as $theme) {
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

it('keeps the complete AI contract and indexed Tabler atlas', function (): void {
    $contract = json_decode(
        (string) file_get_contents(StarterPaths::path('docs/rules/theme-package-contract.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    expect(is_file(StarterPaths::path('AGENTS.md')))->toBeTrue()
        ->and(is_file(StarterPaths::path('docs/rules/theme-package-contract.json')))->toBeTrue()
        ->and(is_file(StarterPaths::path('tools/build-theme-source-index.php')))->toBeTrue()
        ->and(is_dir(StarterPaths::path('docs/template/tabler/tabler-components')))->toBeFalse();

    foreach (['tabler' => 395] as $theme => $htmlCount) {
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
        ->toContain('maximum practical requirement of its header, its filter control, and representative displayed content')
        ->toContain('long fields such as descriptions may wrap across multiple readable lines')
        ->toContain('horizontal scrolling over compressing filters');
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
    $ui = file_get_contents(StarterPaths::path('docs/rules/ui-ux.md'));
    $ignore = file_get_contents(StarterPaths::path('.gitignore'));

    expect($agents)->toContain('theme-intake/<theme-key>/')
        ->and($integration)->toContain('Integrasikan theme <theme-key> dari theme-intake/<theme-key> sampai siap dipilih installer.')
        ->and($integration)->toContain('Dependency closure')
        ->and($integration)->toContain('source-index.json')
        ->and($integration)->toContain('component-manifest.json')
        ->and($integration)->toContain('asset-manifest.json')
        ->and($ignore)->toContain('/theme-intake')
        ->and($ui)->not->toContain('Tabler PowerGrid follows')
        ->and($ui)->not->toContain('Always wrap tables inside a white background card')
        ->and($ui)->not->toContain('outermost `.modal`');
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
