<?php

use Aldhi88\StarterKit\Http\Controllers\Starter\BrandStylesheetController;
use Aldhi88\StarterKit\Services\Starter\StarterAssetPublisher;
use Aldhi88\StarterKit\Services\Starter\StarterSecurityValidator;
use Aldhi88\StarterKit\Support\Starter\StarterBrandPalette;
use Aldhi88\StarterKit\Support\Starter\StarterBrandStylesheet;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

function registerBrandErrorViews(string $theme): void
{
    config()->set('starter.theme', $theme);
    config()->set('view.paths', [
        // Use the package theme instead of Testbench's sample host 503 override.
        StarterPaths::path("resources/themes/{$theme}/views/starter"),
        ...config('view.paths', []),
    ]);
    View::addLocation(StarterPaths::path("resources/themes/{$theme}/views"));
    View::addNamespace('errors', StarterPaths::path("resources/themes/{$theme}/views/starter/errors"));
    View::addNamespace('starter-shared', StarterPaths::path('resources/views'));
}

it('renders every error with the active palette without requiring a stylesheet route', function (string $theme, bool $branded): void {
    registerBrandErrorViews($theme);
    config()->set('starter.brand', $branded
        ? ['primary' => '#B4232D', 'secondary' => '#702A35', 'accent' => '#E7A43B']
        : ['primary' => null, 'secondary' => null, 'accent' => null]);

    foreach (['400', '401', '403', '404', '405', '408', '419', '422', '429', '500', '503', '4xx', '5xx'] as $code) {
        $html = view('errors::'.$code, ['exception' => new HttpException($code === '4xx' ? 418 : 502)])->render();
        expect($html)->toContain('noindex, nofollow', 'Kembali ke Beranda')
            ->not->toContain('/starter/brand.css');
        if ($branded) {
            expect($html)->toContain('data-starter-brand', '<style data-starter-brand-stylesheet>', '--starter-brand-primary:#B4232D', 'btn-primary');
        } else {
            expect($html)->not->toContain('data-starter-brand', '--starter-brand-primary:');
            if ($theme === 'dashcode') {
                expect($html)->toContain('btn btn-dark');
            }
        }
    }
})->with(['dashcode', 'tabler', 'vuexy'])->with([true, false]);

it('keeps the branded 503 usable while maintenance blocks application routes', function (string $theme): void {
    registerBrandErrorViews($theme);
    config()->set('app.debug', false);
    config()->set('starter.brand', ['primary' => '#B4232D', 'secondary' => '#702A35', 'accent' => '#E7A43B']);
    $maintenance = Mockery::mock(MaintenanceMode::class);
    $maintenance->shouldReceive('active')->andReturn(true);
    $maintenance->shouldReceive('data')->andReturn(['status' => 503, 'retry' => 60]);
    $this->app->instance(MaintenanceMode::class, $maintenance);
    Route::get('/brand-maintenance-check', fn () => 'must not be reached')->middleware(PreventRequestsDuringMaintenance::class);

    $this->get('/brand-maintenance-check')->assertStatus(503)
        ->assertSee('Aplikasi Sedang Dalam Pemeliharaan')
        ->assertSee('--starter-brand-primary:#B4232D', false)
        ->assertSee('<style data-starter-brand-stylesheet>', false)
        ->assertDontSee('/starter/brand.css', false);
})->with(['dashcode', 'tabler', 'vuexy']);

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

it('maps branded primary and secondary buttons without recoloring other button variants', function (): void {
    $contract = json_decode(
        (string) file_get_contents(StarterPaths::path('docs/rules/theme-package-contract.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    )['native_css_policy'];

    expect($contract['brand_palette_required_per_theme'])->toBeTrue()
        ->and($contract['brand_primary_button_maps_to_primary'])->toBeTrue()
        ->and($contract['brand_secondary_button_maps_to_secondary'])->toBeTrue()
        ->and($contract['other_semantic_button_colors_remain_native'])->toBeTrue();

    foreach (['vuexy', 'dashcode', 'tabler'] as $theme) {
        $adapter = (string) file_get_contents(StarterPaths::path("resources/themes/{$theme}/brand-palette.css"));

        expect($adapter)
            ->toContain('html[data-starter-brand] .btn-primary')
            ->toContain('html[data-starter-brand] .btn-secondary')
            ->toContain('var(--starter-brand-primary)')
            ->toContain('var(--starter-brand-secondary)')
            ->not->toMatch('/\\.btn-(?:danger|warning|light|dark|success|info)\\b/')
            ->not->toContain('html[data-starter-brand] .btn:focus-visible');
    }
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
