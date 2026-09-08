<?php

use Aldhi88\StarterKit\Support\Starter\StarterPaths;

it('shares the single company brand with public starter surfaces', function (): void {
    $context = file_get_contents(StarterPaths::path('src/Services/Starter/StarterContextService.php'));
    $provider = file_get_contents(StarterPaths::path('src/Providers/Starter/StarterServiceProvider.php'));

    expect($context)
        ->toContain('public function brandData(): array')
        ->toContain("'clientName' => \$client->name")
        ->toContain("'clientLogoUrl' => \$this->clientLogoUrl(\$client)")
        ->and($provider)
        ->toContain("'layouts::auth'")
        ->toContain("'layouts::landing'")
        ->toContain("'starter.templates.landing'")
        ->toContain('->brandData()');
});

it('uses the company logo throughout app auth and landing branding in every theme', function (): void {
    foreach (['tabler', 'dashcode', 'vuexy'] as $theme) {
        $root = StarterPaths::path("resources/themes/{$theme}/views/starter/templates");
        $auth = file_get_contents($root.'/layouts/auth.blade.php');
        $landing = file_get_contents($root.'/landing.blade.php');
        $companyBrand = file_get_contents($root.'/components/company-brand.blade.php');

        expect($auth)
            ->toContain('$clientLogoUrl')
            ->toContain('starter-auth-company-logo')
            ->and($landing)
            ->toContain("@include('starter.templates.components.company-brand')")
            ->and($companyBrand)
            ->toContain('$clientLogoUrl');

        if ($theme === 'vuexy') {
            $brand = file_get_contents($root.'/layouts/brand.blade.php');

            expect($auth)->toContain("@include('starter.templates.layouts.brand'")
                ->and($companyBrand)->toContain("@include('starter.templates.layouts.brand'")
                ->and($brand)->toContain('data-starter-brand-logo');
        } else {
            expect($auth)->toContain('data-starter-brand-logo')
                ->and($companyBrand)->toContain('data-starter-brand-logo');
        }
    }

    foreach (['tabler', 'dashcode'] as $theme) {
        $root = StarterPaths::path("resources/themes/{$theme}/views/starter/templates/layouts");

        expect(file_get_contents($root.'/app.blade.php'))->toContain('$clientLogoUrl ?:')
            ->and(file_get_contents($root.'/landing.blade.php'))->toContain('$clientLogoUrl ?:');
    }

    $vuexyHead = file_get_contents(StarterPaths::path(
        'resources/themes/vuexy/views/starter/templates/layouts/head.blade.php',
    ));

    $vuexyAuth = file_get_contents(StarterPaths::path(
        'resources/themes/vuexy/views/starter/templates/layouts/auth.blade.php',
    ));

    expect($vuexyHead)->toContain('$clientLogoUrl ?:')
        ->and($vuexyAuth)->not->toContain("'clientLogoUrl' => null");
});

it('sizes auth company logos against each native theme brand footprint', function (): void {
    $expectations = [
        'tabler' => ['height: 3.25rem', 'margin-inline: auto', 'max-width: 12rem'],
        'dashcode' => ['max-height:3.5rem', 'width:8.6875rem'],
        'vuexy' => ['inline-size:10rem', 'max-block-size:4rem'],
    ];

    foreach ($expectations as $theme => $declarations) {
        $path = StarterPaths::path("theme-intake/{$theme}/runtime/css/{$theme}.css");

        if (! is_file($path)) {
            continue;
        }

        expect(file_get_contents($path))
            ->toContain('.starter-auth-company-logo')
            ->toContain(...$declarations);
    }
});

it('keeps dashcode and vuexy auth branding above the form at every breakpoint', function (): void {
    foreach (['dashcode', 'vuexy'] as $theme) {
        $auth = file_get_contents(StarterPaths::path(
            "resources/themes/{$theme}/views/starter/templates/layouts/auth.blade.php",
        ));

        expect(substr_count($auth, 'starter-auth-company-logo'))->toBe(1)
            ->and($auth)->toContain('starter-auth-form-brand')
            ->and(strpos($auth, 'starter-auth-form-brand'))
            ->toBeGreaterThan(strpos($auth, 'data-starter-region="primary-content"'));
    }
});
