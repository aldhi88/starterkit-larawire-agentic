<?php

use Aldhi88\StarterKit\Services\Starter\StarterAssetPublisher;
use Aldhi88\StarterKit\Support\Starter\StarterThemeRegistry;
use Aldhi88\StarterKit\Tests\Fixtures\FixturePowerGridTheme;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

function createCompleteFixtureTheme(string $root): void
{
    $contract = json_decode(
        (string) file_get_contents(dirname(__DIR__, 2).'/docs/rules/theme-package-contract.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    mkdir($root.'/views', 0700, true);
    mkdir($root.'/docs', 0700, true);

    foreach ($contract['required_runtime_files'] as $file) {
        $path = $root.'/views/'.$file;
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }

        file_put_contents($path, '<div>'.$file.'</div>');
    }

    file_put_contents($root.'/docs/source.json', json_encode([
        'schema_version' => 1,
        'theme' => 'fixture',
        'provider' => 'google-drive',
        'url' => 'https://drive.google.com/fixture',
        'required_local_path' => 'theme-intake/fixture',
        'license' => 'Fixture license',
        'distribution' => 'private',
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    file_put_contents($root.'/docs/template.md', '# Fixture');
    file_put_contents($root.'/docs/runtime-map.md', '# Runtime map');
    file_put_contents($root.'/docs/source-index.json', json_encode([
        'schema_version' => 1,
        'theme' => 'fixture',
        'source_root' => 'theme-intake/fixture',
        'html_files' => 1,
        'files' => [[
            'path' => 'index.html',
            'sha256' => hash('sha256', '<button>Fixture</button>'),
            'signals' => ['button-action'],
            'decision' => 'curated',
        ]],
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    file_put_contents($root.'/docs/component-manifest.json', json_encode([
        'schema_version' => 1,
        'theme' => 'fixture',
        'source' => [
            'name' => 'Fixture',
            'license' => 'Fixture license',
            'distribution' => 'private',
        ],
        'components' => array_map(fn (string $id): array => [
            'id' => $id,
            'references' => ['index.html'],
            'runtime' => ['views/starter/templates/layouts/navigation/vertical.blade.php'],
            'states' => ['default'],
        ], $contract['required_components']),
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    file_put_contents($root.'/docs/asset-manifest.json', json_encode([
        'schema_version' => 1,
        'theme' => 'fixture',
        'files' => [[
            'source' => 'runtime/theme.css',
            'target' => 'theme.css',
            'sha256' => hash('sha256', 'body{}'),
            'referenced_by' => ['views/starter/templates/layouts/navigation/vertical.blade.php'],
        ]],
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
}

afterEach(function (): void {
    StarterThemeRegistry::flushRegistered();
});

it('validates and resolves both bundled theme integrations', function (): void {
    $themes = StarterThemeRegistry::all();

    expect($themes)->toHaveKeys(['tabler', 'dashcode'])
        ->and(StarterThemeRegistry::path('tabler', 'views'))->toBeDirectory();
});

it('registers a complete owner-prepared theme integration', function (): void {
    $root = sys_get_temp_dir().'/starter-theme-test-'.bin2hex(random_bytes(5));
    createCompleteFixtureTheme($root);

    try {
        StarterThemeRegistry::register('fixture', [
            'label' => 'Fixture',
            'root' => $root,
            'views' => 'views',
            'assets' => 'assets',
            'docs' => 'docs',
            'powergrid' => FixturePowerGridTheme::class,
            'layouts' => [
                'vertical' => 'starter.templates.layouts.navigation.vertical',
                'horizontal' => 'starter.templates.layouts.navigation.horizontal',
            ],
        ]);

        expect(StarterThemeRegistry::all())->toHaveKeys(['tabler', 'dashcode', 'fixture'])
            ->and(StarterThemeRegistry::path('fixture', 'assets'))->toBe($root.'/assets');
    } finally {
        File::deleteDirectory($root);
    }
});

it('rejects a theme with incomplete component evidence', function (): void {
    $root = sys_get_temp_dir().'/starter-theme-test-'.bin2hex(random_bytes(5));
    createCompleteFixtureTheme($root);
    file_put_contents($root.'/docs/component-manifest.json', json_encode([
        'schema_version' => 1,
        'theme' => 'fixture',
        'source' => [
            'name' => 'Fixture',
            'license' => 'Fixture license',
            'distribution' => 'private',
        ],
        'components' => [],
    ], JSON_THROW_ON_ERROR));

    try {
        expect(fn () => StarterThemeRegistry::register('fixture', [
            'label' => 'Fixture',
            'root' => $root,
            'views' => 'views',
            'assets' => 'assets',
            'docs' => 'docs',
            'powergrid' => FixturePowerGridTheme::class,
            'layouts' => [
                'vertical' => 'starter.templates.layouts.navigation.vertical',
                'horizontal' => 'starter.templates.layouts.navigation.horizontal',
            ],
        ]))->toThrow(RuntimeException::class, 'component manifest is empty');
    } finally {
        File::deleteDirectory($root);
    }
});

it('rejects an incomplete source index and an invalid runtime asset recipe', function (): void {
    $sourceRoot = sys_get_temp_dir().'/starter-theme-test-'.bin2hex(random_bytes(5));
    createCompleteFixtureTheme($sourceRoot);
    $sourceIndex = json_decode(
        (string) file_get_contents($sourceRoot.'/docs/source-index.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $sourceIndex['html_files'] = 2;
    file_put_contents($sourceRoot.'/docs/source-index.json', json_encode($sourceIndex, JSON_THROW_ON_ERROR));

    try {
        expect(fn () => StarterThemeRegistry::register('fixture', [
            'label' => 'Fixture',
            'root' => $sourceRoot,
            'views' => 'views',
            'assets' => 'assets',
            'docs' => 'docs',
            'powergrid' => FixturePowerGridTheme::class,
            'layouts' => [
                'vertical' => 'starter.templates.layouts.navigation.vertical',
                'horizontal' => 'starter.templates.layouts.navigation.horizontal',
            ],
        ]))->toThrow(RuntimeException::class, 'source index must account for every source HTML file');
    } finally {
        File::deleteDirectory($sourceRoot);
    }

    StarterThemeRegistry::flushRegistered();
    $assetRoot = sys_get_temp_dir().'/starter-theme-test-'.bin2hex(random_bytes(5));
    createCompleteFixtureTheme($assetRoot);
    $assetManifest = json_decode((string) file_get_contents($assetRoot.'/docs/asset-manifest.json'), true, flags: JSON_THROW_ON_ERROR);
    $assetManifest['files'][0]['sha256'] = 'invalid';
    file_put_contents($assetRoot.'/docs/asset-manifest.json', json_encode($assetManifest, JSON_THROW_ON_ERROR));

    try {
        expect(fn () => StarterThemeRegistry::register('fixture', [
            'label' => 'Fixture',
            'root' => $assetRoot,
            'views' => 'views',
            'assets' => 'assets',
            'docs' => 'docs',
            'powergrid' => FixturePowerGridTheme::class,
            'layouts' => [
                'vertical' => 'starter.templates.layouts.navigation.vertical',
                'horizontal' => 'starter.templates.layouts.navigation.horizontal',
            ],
        ]))->toThrow(RuntimeException::class, 'invalid SHA-256');
    } finally {
        File::deleteDirectory($assetRoot);
    }
});

it('rejects incomplete themes and path traversal', function (): void {
    expect(fn () => StarterThemeRegistry::register('unsafe', [
        'label' => 'Unsafe',
        'root' => dirname(__DIR__, 2),
        'views' => '../views',
    ]))->toThrow(RuntimeException::class)
        ->and(fn () => StarterThemeRegistry::path('tabler', 'assets', '../secret'))
        ->toThrow(RuntimeException::class);
});

it('publishes an exact theme recipe and reuses committed production assets', function (): void {
    $root = sys_get_temp_dir().'/starter-theme-package-'.bin2hex(random_bytes(5));
    $host = sys_get_temp_dir().'/starter-theme-host-'.bin2hex(random_bytes(5));
    $originalBasePath = base_path();
    createCompleteFixtureTheme($root);
    mkdir($host.'/theme-intake/fixture/runtime', 0700, true);
    file_put_contents($host.'/theme-intake/fixture/runtime/theme.css', 'body{}');
    app()->setBasePath($host);

    try {
        StarterThemeRegistry::register('fixture', [
            'label' => 'Fixture',
            'root' => $root,
            'views' => 'views',
            'assets' => 'assets/fixture',
            'docs' => 'docs',
            'powergrid' => FixturePowerGridTheme::class,
            'layouts' => [
                'vertical' => 'starter.templates.layouts.navigation.vertical',
                'horizontal' => 'starter.templates.layouts.navigation.horizontal',
            ],
        ]);
        config()->set('starter.theme', 'fixture');
        $command = Mockery::mock(Command::class);
        $command->shouldReceive('info')->zeroOrMoreTimes();
        $command->shouldReceive('line')->zeroOrMoreTimes();
        $command->shouldReceive('error')->zeroOrMoreTimes();
        $publisher = new StarterAssetPublisher;

        expect($publisher->themeSourceReady('fixture'))->toBeTrue()
            ->and($publisher->publishSelectedTheme($command))->toBeTrue()
            ->and(file_get_contents($host.'/public/assets/fixture/theme.css'))->toBe('body{}');

        File::deleteDirectory($host.'/theme-intake');

        expect($publisher->themeAssetsReady('fixture'))->toBeTrue()
            ->and($publisher->publishSelectedTheme($command))->toBeTrue();

        file_put_contents($host.'/public/assets/fixture/theme.css', 'tampered');

        expect($publisher->themeAssetsReady('fixture'))->toBeFalse()
            ->and($publisher->publishSelectedTheme($command))->toBeFalse();
    } finally {
        app()->setBasePath($originalBasePath);
        config()->set('starter.theme', 'tabler');
        File::deleteDirectory($root);
        File::deleteDirectory($host);
    }
});
