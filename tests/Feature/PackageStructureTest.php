<?php

use Aldhi88\StarterKit\Console\Commands\Starter\AppCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\DeployCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\InstallCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\ResetCommand;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Support\Facades\File;

it('ships only redistributable registered themes', function (): void {
    $config = require StarterPaths::path('config/starter.php');

    expect(array_keys($config['themes']))->toBe(['tabler'])
        ->and($config['themes']['tabler']['layouts'])->toHaveKeys(['vertical', 'horizontal'])
        ->and(is_file(StarterPaths::path('public/themes/tabler/LICENSE')))->toBeTrue();
});

it('maps the Tabler asset payload directly to public assets', function (): void {
    $config = require StarterPaths::path('config/starter.php');

    expect($config['themes']['tabler']['assets'])
        ->toBe('public/themes/tabler/assets/tabler')
        ->and(is_file(StarterPaths::path(
            $config['themes']['tabler']['assets'].'/dist/css/tabler.min.css',
        )))->toBeTrue();
});

it('ships only the Tabler files loaded by starter runtime layouts', function (): void {
    $root = StarterPaths::path('public/themes/tabler/assets/tabler');
    $files = collect(File::allFiles($root))
        ->map(fn (SplFileInfo $file): string => str_replace('\\', '/', $file->getRelativePathname()))
        ->sort()
        ->values()
        ->all();

    expect($files)->toBe([
        'css/starter-theme.css',
        'dist/css/tabler-vendors.min.css',
        'dist/css/tabler.min.css',
        'dist/js/tabler-theme.min.js',
        'dist/js/tabler.min.js',
        'js/starter-theme.js',
        'static/logo-small.svg',
        'static/logo-white.svg',
    ])->and(is_dir(StarterPaths::path('docs/template/tabler/assets')))->toBeFalse();
});

it('keeps the AI contract and Tabler source atlas inside the package', function (): void {
    expect(is_file(StarterPaths::path('AGENTS.md')))->toBeTrue()
        ->and(is_file(StarterPaths::path('docs/template/tabler/template.md')))->toBeTrue()
        ->and(is_file(StarterPaths::path('docs/template/tabler/runtime-map.md')))->toBeTrue();
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
