<?php

use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if ($app->isProduction()) {
    throw new RuntimeException('Use a local comparison host.');
}
config(['app.debug' => true]);
$actor = ClientLogin::whereHas('role', fn ($q) => $q->where('code', 'superuser'))->where('status', 'active')->firstOrFail();
Auth::login($actor);
$resource = $argv[1] ?? 'cards';
if (! in_array($resource, ['posts', 'pages', 'cards'], true)) {
    throw new InvalidArgumentException('Unsupported editorial fixture.');
}
echo Blade::render('@livewireStyles');
echo Livewire\Livewire::mount('cms.form', ['resource' => $resource]);
