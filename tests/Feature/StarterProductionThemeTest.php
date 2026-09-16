<?php

use Aldhi88\StarterKit\Services\Starter\StarterAssetPublisher;
use Aldhi88\StarterKit\Services\Starter\StarterSecurityValidator;
use Illuminate\Support\Facades\File;

it('does not restrict the queue driver outside production preflight', function (): void {
    config()->set('queue.default', 'database');

    $assets = Mockery::mock(StarterAssetPublisher::class);
    $checks = collect((new StarterSecurityValidator($assets))->checks())->keyBy('label');

    expect($checks)->not->toHaveKey('Synchronous queue driver');
});

it('accepts explicit sync or asynchronous queues and rejects null queues in production preflight', function (): void {
    $host = sys_get_temp_dir().'/starter-production-queue-'.bin2hex(random_bytes(6));
    $originalBasePath = base_path();
    File::ensureDirectoryExists($host);
    File::put($host.'/.env', "QUEUE_CONNECTION=sync\n");
    app()->setBasePath($host);
    config()->set('queue.default', 'sync');
    config()->set('queue.connections.sync', ['driver' => 'sync']);
    config()->set('queue.connections.database', ['driver' => 'database']);
    config()->set('queue.connections.null', ['driver' => 'null']);

    $assets = Mockery::mock(StarterAssetPublisher::class);
    $assets->shouldReceive('themeAssetsReady')->times(3)->andReturn(true);
    $validator = new StarterSecurityValidator($assets);

    try {
        $sync = collect($validator->checks(production: true))->keyBy('label');
        expect($sync['Production queue connection']['passed'])->toBeTrue();

        File::put($host.'/.env', "QUEUE_CONNECTION=database\n");
        config()->set('queue.default', 'database');
        $database = collect($validator->checks(production: true))->keyBy('label');
        expect($database['Production queue connection']['passed'])->toBeTrue();

        File::put($host.'/.env', "QUEUE_CONNECTION=null\n");
        config()->set('queue.default', 'null');
        $null = collect($validator->checks(production: true))->keyBy('label');
        expect($null['Production queue connection']['passed'])->toBeFalse();
    } finally {
        app()->setBasePath($originalBasePath);
        File::deleteDirectory($host);
    }
});

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

it('rejects simulated mail transports in production preflight', function (): void {
    $host = sys_get_temp_dir().'/starter-production-mail-'.bin2hex(random_bytes(6));
    $originalBasePath = base_path();
    File::ensureDirectoryExists($host);
    File::put($host.'/.env', "MAIL_MAILER=log\n");
    app()->setBasePath($host);
    config()->set('mail.default', 'log');
    config()->set('mail.mailers.log', ['transport' => 'log']);
    config()->set('mail.mailers.array', ['transport' => 'array']);
    config()->set('mail.mailers.smtp', ['transport' => 'smtp']);

    $assets = Mockery::mock(StarterAssetPublisher::class);
    $assets->shouldReceive('themeAssetsReady')->twice()->andReturn(true);
    $validator = new StarterSecurityValidator($assets);

    try {
        $invalid = collect($validator->checks(production: true))->keyBy('label');
        expect($invalid['Production mail delivery']['passed'])->toBeFalse();

        File::put($host.'/.env', "MAIL_MAILER=smtp\n");
        config()->set('mail.default', 'smtp');
        $valid = collect($validator->checks(production: true))->keyBy('label');

        expect($valid['Production mail delivery']['passed'])->toBeTrue();
    } finally {
        app()->setBasePath($originalBasePath);
        File::deleteDirectory($host);
    }
});
