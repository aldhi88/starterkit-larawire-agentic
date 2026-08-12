<?php

use Aldhi88\StarterKit\Installation\StarterEnvironmentManager;
use Illuminate\Support\Facades\File;

it('moves every managed value into one ordered block at the bottom', function (): void {
    $directory = sys_get_temp_dir().'/starterkit-env-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($directory);
    $path = $directory.'/.env';
    File::put($path, <<<'ENV'
APP_NAME=ERP
APP_URL=https://erp.example.test
STARTER_LAYOUT=horizontal
STARTER_THEME=tabler
DB_CONNECTION=mysql
SESSION_ENCRYPT=false
STARTER_LAYOUT=vertical
ENV);

    (new StarterEnvironmentManager)->apply($path);
    $contents = File::get($path);

    expect(substr_count($contents, '# starterkit-larawire:begin'))->toBe(1)
        ->and(substr_count($contents, 'STARTER_LAYOUT='))->toBe(1)
        ->and($contents)->toContain('APP_DOMAIN=erp.example.test')
        ->and($contents)->toContain('SESSION_DOMAIN=.erp.example.test')
        ->and($contents)->toContain('SESSION_SECURE_COOKIE=true')
        ->and($contents)->toContain('SESSION_ENCRYPT=true')
        ->and($contents)->toContain('SESSION_DRIVER=database')
        ->and($contents)->toContain('CACHE_STORE=database')
        ->and($contents)->toContain('QUEUE_CONNECTION=sync')
        ->and($contents)->toContain('STARTER_THEME=tabler')
        ->and($contents)->toContain('STARTER_LAYOUT=vertical')
        ->and($contents)->not->toContain('STARTER_SUPERUSER')
        ->and($contents)->toEndWith("# starterkit-larawire:end\n");

    File::deleteDirectory($directory);
});

it('writes the presentation selected by the installation wizard', function (): void {
    $directory = sys_get_temp_dir().'/starterkit-presentation-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($directory);
    $path = $directory.'/.env';
    File::put($path, "APP_URL=http://company.test:8123\n");

    (new StarterEnvironmentManager)->apply($path, theme: 'tabler', layout: 'horizontal');
    $contents = File::get($path);

    expect($contents)->toContain('APP_DOMAIN=company.test')
        ->and($contents)->toContain('STARTER_THEME=tabler')
        ->and($contents)->toContain('STARTER_LAYOUT=horizontal');

    File::deleteDirectory($directory);
});

it('updates the application name without exposing credentials', function (): void {
    $directory = sys_get_temp_dir().'/starterkit-app-name-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($directory);
    $path = $directory.'/.env';
    File::put($path, "APP_NAME=Laravel\nAPP_URL=http://localhost\n");

    (new StarterEnvironmentManager)->setApplicationName($path, 'PT Maju "Bersama"');
    $contents = File::get($path);

    expect($contents)->toContain('APP_NAME="PT Maju \\"Bersama\\""')
        ->and($contents)->not->toContain('PASSWORD=');

    File::deleteDirectory($directory);
});

it('keeps production domain and HTTPS cookie values derived from APP_URL in env example', function (): void {
    $directory = sys_get_temp_dir().'/starterkit-env-example-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($directory);
    $path = $directory.'/.env.example';
    File::put($path, "APP_URL=http://localhost\n");

    (new StarterEnvironmentManager)->apply($path, example: true);
    $contents = File::get($path);

    expect($contents)->toContain('APP_DOMAIN=null')
        ->and($contents)->toContain('SESSION_DOMAIN=null')
        ->and($contents)->toContain('SESSION_COOKIE=larawire_session')
        ->and($contents)->toContain('SESSION_SECURE_COOKIE=null');

    File::deleteDirectory($directory);
});
