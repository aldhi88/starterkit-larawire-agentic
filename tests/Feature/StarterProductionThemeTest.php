<?php

use Aldhi88\StarterKit\Services\Starter\StarterAssetPublisher;
use Aldhi88\StarterKit\Services\Starter\StarterSecurityValidator;
use Illuminate\Support\Facades\File;

it('requires explicit matching theme values and committed runtime in production', function (): void {
    $host = sys_get_temp_dir().'/starter-production-theme-'.bin2hex(random_bytes(6));
    $originalBasePath = base_path();
    File::ensureDirectoryExists($host);
    File::put($host.'/.env', "STARTER_THEME=tabler\nSTARTER_LAYOUT=horizontal\n");
    app()->setBasePath($host);
    config()->set('starter.theme', 'tabler');
    config()->set('starter.layout', 'horizontal');

    $assets = Mockery::mock(StarterAssetPublisher::class);
    $assets->shouldReceive('themeAssetsReady')->with('tabler')->twice()->andReturn(true);
    $validator = new StarterSecurityValidator($assets);

    try {
        $valid = collect($validator->checks(production: true))->keyBy('label');

        expect($valid['Explicit production UI theme']['passed'])->toBeTrue()
            ->and($valid['Explicit production UI layout']['passed'])->toBeTrue()
            ->and($valid['Committed theme runtime assets']['passed'])->toBeTrue();

        File::put($host.'/.env', "STARTER_LAYOUT=vertical\n");
        $invalid = collect($validator->checks(production: true))->keyBy('label');

        expect($invalid['Explicit production UI theme']['passed'])->toBeFalse()
            ->and($invalid['Explicit production UI layout']['passed'])->toBeFalse()
            ->and($invalid['Committed theme runtime assets']['passed'])->toBeTrue();
    } finally {
        app()->setBasePath($originalBasePath);
        File::deleteDirectory($host);
    }
});

it('fails production validation when selected theme runtime is unavailable', function (): void {
    $host = sys_get_temp_dir().'/starter-production-assets-'.bin2hex(random_bytes(6));
    $originalBasePath = base_path();
    File::ensureDirectoryExists($host);
    File::put($host.'/.env', "STARTER_THEME=tabler\nSTARTER_LAYOUT=vertical\n");
    app()->setBasePath($host);
    config()->set('starter.theme', 'tabler');
    config()->set('starter.layout', 'vertical');

    $assets = Mockery::mock(StarterAssetPublisher::class);
    $assets->shouldReceive('themeAssetsReady')->with('tabler')->once()->andReturn(false);

    try {
        $checks = collect((new StarterSecurityValidator($assets))->checks(production: true))->keyBy('label');

        expect($checks['Explicit production UI theme']['passed'])->toBeTrue()
            ->and($checks['Explicit production UI layout']['passed'])->toBeTrue()
            ->and($checks['Committed theme runtime assets']['passed'])->toBeFalse();
    } finally {
        app()->setBasePath($originalBasePath);
        File::deleteDirectory($host);
    }
});
