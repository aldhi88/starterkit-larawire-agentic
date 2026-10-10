<?php

use Aldhi88\StarterKit\Http\Controllers\Starter\BrandStylesheetController;
use Aldhi88\StarterKit\Services\Starter\StarterAssetPublisher;
use Aldhi88\StarterKit\Services\Starter\StarterSecurityValidator;
use Aldhi88\StarterKit\Support\Starter\StarterBrandPalette;
use Aldhi88\StarterKit\Support\Starter\StarterBrandStylesheet;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;

it('keeps native template colors when no brand is configured', function (): void {
    config()->set('starter.brand', ['primary' => null, 'secondary' => null, 'accent' => null]);

    expect(StarterBrandPalette::configured())->toBeNull();
    expect(StarterBrandStylesheet::configured())->toBeNull();
    expect(StarterBrandPalette::configurationValid())->toBeTrue();
});

it('builds readable palette tokens for all three configured colors', function (): void {
    config()->set('starter.brand', [
        'primary' => '#B4232D',
        'secondary' => '#702A35',
        'accent' => '#E7A43B',
    ]);

    $palette = StarterBrandPalette::configured();
    $css = $palette?->css();

    expect($css)->toContain('--starter-brand-primary:#B4232D')
        ->toContain('--starter-brand-primary-rgb:180,35,45')
        ->toContain('--starter-brand-primary-contrast:#FFFFFF')
        ->toContain('--starter-brand-accent-contrast:#111111')
        ->toContain('--starter-brand-secondary-soft:')
        ->toContain('--starter-brand-primary-check-svg:')
        ->toContain('--starter-brand-primary-on-dark:')
        ->and((new BrandStylesheetController)()->headers->get('Content-Type'))->toContain('text/css');
});

it('serves only the active template palette from a separate tracked CSS file', function (): void {
    config()->set('starter.brand', [
        'primary' => '#B4232D',
        'secondary' => '#702A35',
        'accent' => '#E7A43B',
    ]);

    $fingerprints = [];

    foreach (['vuexy', 'dashcode', 'tabler'] as $theme) {
        config()->set('starter.theme', $theme);
        $stylesheet = StarterBrandStylesheet::configured();
        $adapter = file_get_contents(StarterPaths::path("resources/themes/{$theme}/brand-palette.css"));

        expect($stylesheet)->not->toBeNull()
            ->and($stylesheet?->css())->toContain('--starter-brand-primary:#B4232D')
            ->toContain($adapter)
            ->and((new BrandStylesheetController)()->getContent())->toContain($adapter);

        $fingerprints[$theme] = $stylesheet?->fingerprint();
    }

    expect(array_unique($fingerprints))->toHaveCount(3);
});

it('keeps pages usable and fails synchronization when brand values are incomplete or invalid', function (): void {
    $assets = Mockery::mock(StarterAssetPublisher::class);
    $validator = new StarterSecurityValidator($assets);

    config()->set('starter.brand', ['primary' => '#B4232D', 'secondary' => '', 'accent' => '#E7A43B']);
    expect(StarterBrandPalette::configured())->toBeNull()
        ->and(collect($validator->checks())->keyBy('label')['Starter brand palette']['passed'])->toBeFalse();

    config()->set('starter.brand', ['primary' => '#BAD', 'secondary' => '#702A35', 'accent' => '#E7A43B']);
    expect(StarterBrandPalette::configured())->toBeNull()
        ->and(collect($validator->checks())->keyBy('label')['Starter brand palette']['passed'])->toBeFalse();
});

it('shows a landing return link in every theme login layout', function (): void {
    foreach (['vuexy', 'dashcode', 'tabler'] as $theme) {
        $source = file_get_contents(StarterPaths::path("resources/themes/{$theme}/views/starter/templates/layouts/auth.blade.php"));

        expect($source)->toContain("route('landing')")
            ->toContain('Kembali ke landing page');
    }
});
