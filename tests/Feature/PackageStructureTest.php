<?php

use Aldhi88\StarterKit\Console\Commands\Starter\AppCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\DeployCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\InstallCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\ResetCommand;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Support\Facades\File;

it('registers only the prepared Tabler theme without storing its archive in the package repository', function (): void {
    $config = require StarterPaths::path('config/starter.php');

    expect(array_keys($config['themes']))->toBe(['tabler'])
        ->and($config['themes']['tabler']['layouts'])->toHaveKeys(['vertical', 'horizontal'])
        ->and(is_dir(StarterPaths::path('theme-packages')))->toBeFalse()
        ->and(File::allFiles(StarterPaths::path('public/themes')))->toBe([]);
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
