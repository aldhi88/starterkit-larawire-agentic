<?php

use Altekno\StarterKit\Support\Starter\StarterPaths;

it('ships only redistributable registered themes', function (): void {
    $config = require StarterPaths::path('config/starter.php');

    expect(array_keys($config['themes']))->toBe(['tabler'])
        ->and($config['themes']['tabler']['layouts'])->toHaveKeys(['vertical', 'horizontal'])
        ->and(is_file(StarterPaths::path('public/themes/tabler/LICENSE')))->toBeTrue()
        ->and(is_dir(StarterPaths::path('docs/template/dashcode')))->toBeFalse()
        ->and(is_dir(StarterPaths::path('public/themes/dashcode')))->toBeFalse()
        ->and(is_dir(StarterPaths::path('resources/themes/dashcode')))->toBeFalse();
});

it('maps the Tabler asset payload directly to public assets', function (): void {
    $config = require StarterPaths::path('config/starter.php');

    expect($config['themes']['tabler']['assets'])
        ->toBe('public/themes/tabler/assets/tabler')
        ->and(is_file(StarterPaths::path(
            $config['themes']['tabler']['assets'].'/dist/css/tabler.min.css',
        )))->toBeTrue();
});

it('keeps the AI contract and Tabler source atlas inside the package', function (): void {
    expect(is_file(StarterPaths::path('AGENTS.md')))->toBeTrue()
        ->and(is_file(StarterPaths::path('docs/template/tabler/template.md')))->toBeTrue()
        ->and(is_file(StarterPaths::path('docs/template/tabler/runtime-map.md')))->toBeTrue();
});
