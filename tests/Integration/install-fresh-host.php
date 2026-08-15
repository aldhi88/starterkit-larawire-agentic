<?php

declare(strict_types=1);

use Illuminate\Console\Application;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Tester\CommandTester;

$host = $argv[1] ?? '';

if ($host === '' || ! is_file($host.'/artisan')) {
    fwrite(STDERR, "Usage: php install-fresh-host.php /absolute/path/to/laravel\n");
    exit(2);
}

chdir($host);
require $host.'/vendor/autoload.php';

$app = require $host.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
$artisan = (new ReflectionMethod($kernel, 'getArtisan'))->invoke($kernel);

if (! $artisan instanceof Application) {
    fwrite(STDERR, "Laravel Artisan application is unavailable.\n");
    exit(2);
}

$tester = new CommandTester($artisan->find('starter:install'));
$tester->setInputs([
    'yes',
    'yes',
    '0',
    '0',
    'Starterkit Test',
    'developer@example.test',
    'local-password',
    'local-password',
    'Human Resources',
    'hr',
    'yes',
    'yes',
]);

$status = $tester->execute([], [
    'interactive' => true,
    'decorated' => false,
]);

fwrite($status === 0 ? STDOUT : STDERR, $tester->getDisplay());
exit($status);
