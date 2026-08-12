<?php

use Aldhi88\StarterKit\Support\Starter\StarterThemeRegistry;
use Aldhi88\StarterKit\Tests\Fixtures\FixturePowerGridTheme;
use Illuminate\Support\Facades\File;

afterEach(function (): void {
    StarterThemeRegistry::flushRegistered();
});

it('validates and resolves the bundled Tabler theme', function (): void {
    $themes = StarterThemeRegistry::all();

    expect($themes)->toHaveKey('tabler')
        ->and(StarterThemeRegistry::path('tabler', 'assets'))->toBeDirectory()
        ->and(StarterThemeRegistry::path('tabler', 'views'))->toBeDirectory();
});

it('registers a complete optional Composer theme package', function (): void {
    $root = sys_get_temp_dir().'/starter-theme-test-'.bin2hex(random_bytes(5));
    mkdir($root.'/views/starter/layouts', 0700, true);
    mkdir($root.'/assets', 0700, true);
    mkdir($root.'/docs', 0700, true);
    file_put_contents($root.'/views/starter/layouts/vertical.blade.php', '<main>vertical</main>');
    file_put_contents($root.'/views/starter/layouts/horizontal.blade.php', '<main>horizontal</main>');

    try {
        StarterThemeRegistry::register('fixture', [
            'label' => 'Fixture',
            'root' => $root,
            'views' => 'views',
            'assets' => 'assets',
            'docs' => 'docs',
            'powergrid' => FixturePowerGridTheme::class,
            'layouts' => [
                'vertical' => 'starter.layouts.vertical',
                'horizontal' => 'starter.layouts.horizontal',
            ],
        ]);

        expect(StarterThemeRegistry::all())->toHaveKeys(['tabler', 'fixture'])
            ->and(StarterThemeRegistry::path('fixture', 'assets'))->toBe($root.'/assets');
    } finally {
        File::deleteDirectory($root);
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
