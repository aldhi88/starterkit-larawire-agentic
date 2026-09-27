<?php

// Render the real shell in a local demo host without authentication or database writes.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

[$script, $host, $layout, $count, $length, $origin] = $argv;
require $host.'/vendor/autoload.php';
$app = require $host.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config()->set('starter.layout', $layout);
config()->set('app.asset_url', $origin);
URL::forceRootUrl($origin);
URL::forceScheme('http');

$leaf = static fn (string $label): array => [
    'label' => $label, 'url' => '/demo/'.rawurlencode($label), 'icon' => 'folder',
    'active' => false, 'expanded' => false, 'hasChildren' => false, 'children' => [],
];
$menus = [];
for ($i = 0; $i < (int) $count; $i++) {
    $menu = $leaf(['Dashboard', 'Master', 'Pelanggan', 'Request'][$i % 4].' '.($i + 1));
    $nested = $leaf('Submenu bertingkat');
    $nested['hasChildren'] = true;
    $nested['children'] = [$leaf('Halaman detail')];
    $menu['hasChildren'] = true;
    $menu['children'] = [$leaf('Ringkasan'), $nested];
    $menus[] = $menu;
}

$data = [
    'login' => null, 'loginName' => 'Pengguna Demo', 'loginAvatarUrl' => $origin.'/assets/starter/images/avatar.png',
    'clientLogoUrl' => null, 'clientName' => 'Demo', 'currentAppName' => 'Back Office',
    'currentAppKey' => 'hr', 'currentDashboardUrl' => '#content', 'currentProfileUrl' => '#content',
    'accountPersistBase' => 'navigation-regression', 'appOptions' => collect(),
    'sidebarMods' => collect($menus === [] ? [] : [['menus' => $menus]]),
    'lockScreenEnabled' => false, 'lockScreenTimeoutSeconds' => 900,
    'lockScreenUrl' => '#content', 'sessionActivityUrl' => '', 'sessionActivityScope' => '',
];
request()->attributes->set('starter.context.data', $data);
$content = '<section id="content"><h1>Ringkasan Request</h1><p>Konten setelah navigasi.</p>';
if ($length === 'long') {
    $content .= str_repeat('<p>Data operasional untuk verifikasi halaman panjang.</p>', 80);
}
$content .= '</section>';
echo view('starter.templates.layouts.app', ['slot' => new HtmlString($content)])->render();
