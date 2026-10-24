<?php

use Aldhi88\StarterKit\Support\Starter\StarterBrandColor;
use Aldhi88\StarterKit\Support\Starter\StarterBrandPalette;
use Aldhi88\StarterKit\Support\Starter\StarterBrandStylesheet;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Support\Facades\View;

function harmonyTokens(string $css): array
{
    preg_match_all('/--starter-brand-([a-z-]+):(#[0-9A-F]{6})[;}]/', $css, $matches, PREG_SET_ORDER);

    return array_column($matches, 2, 1);
}

it('preserves brand seeds and produces readable balanced tones for thirty varied colors', function (): void {
    $colors = ['#FFFFFF', '#000000', '#FFFF00', '#00FFFF', '#FF00FF', '#FF0000', '#00FF00', '#0000FF', '#111827', '#F8FAFC',
        '#B4232D', '#702A35', '#E7A43B', '#0066CC', '#0F766E', '#7C3AED', '#EC4899', '#F97316', '#84CC16', '#92400E',
        '#475569', '#94A3B8', '#FFD700', '#800000', '#008080', '#1E3A8A', '#FFB6C1', '#C4B5FD', '#D1FAE5', '#777777'];
    foreach ($colors as $seed) {
        config(['starter.brand' => array_fill_keys(['primary', 'secondary', 'accent'], $seed)]);
        $tokens = harmonyTokens(StarterBrandPalette::configured()->css());
        foreach (['primary', 'secondary', 'accent'] as $role) {
            expect($tokens[$role])->toBe($seed)
                ->and(StarterBrandColor::contrast($tokens[$role], $tokens[$role.'-contrast']))->toBeGreaterThanOrEqual(4.5)
                ->and(StarterBrandColor::contrast($tokens[$role.'-hover'], $tokens[$role.'-hover-contrast']))->toBeGreaterThanOrEqual(4.5)
                ->and(StarterBrandColor::contrast($tokens[$role.'-soft'], $tokens[$role.'-text']))->toBeGreaterThanOrEqual(4.5)
                ->and(StarterBrandColor::contrast($tokens[$role.'-soft-dark'], $tokens[$role.'-on-dark']))->toBeGreaterThanOrEqual(4.5)
                ->and(StarterBrandColor::lightness($tokens[$role.'-soft']))->toBeBetween(.965, .975)
                ->and(StarterBrandColor::lightness($tokens[$role.'-soft-dark']))->toBeBetween(.255, .265);
        }
    }
});

it('matches Oklab reference lightness and keeps neutral seeds neutral', function (): void {
    expect(StarterBrandColor::lightness('#FF0000'))->toBeBetween(.6279, .6281)
        ->and(StarterBrandColor::lightness('#00FF00'))->toBeBetween(.8663, .8666)
        ->and(StarterBrandColor::lightness('#0000FF'))->toBeBetween(.4519, .4522)
        ->and(StarterBrandColor::tone('#FFFFFF', .5))->toBe('#636363')
        ->and(StarterBrandColor::tone('#000000', 1))->toBe('#FFFFFF')
        ->and(StarterBrandColor::contrast('#000000', '#FFFFFF'))->toBe(21.0);
});

it('uses the same feedback contract with each adapter native status hues', function (string $theme): void {
    config(['starter.theme' => $theme, 'starter.brand' => ['primary' => '#FFFF00', 'secondary' => '#000000', 'accent' => '#00FF00']]);
    $css = StarterBrandStylesheet::configured()->css();
    $tokens = harmonyTokens($css);
    $adapter = file_get_contents(StarterPaths::path("resources/themes/{$theme}/brand-palette.css"));
    foreach (['success', 'info', 'warning', 'danger'] as $status) {
        preg_match('/--starter-native-'.$status.':(#[0-9A-F]{6});/', $adapter, $seed);
        expect($tokens[$status])->toBe($seed[1])
            ->and(StarterBrandColor::contrast($tokens[$status.'-text'], $tokens[$status.'-soft']))->toBeGreaterThanOrEqual(4.5)
            ->and(StarterBrandColor::contrast($tokens[$status.'-on-dark'], $tokens[$status.'-soft-dark']))->toBeGreaterThanOrEqual(4.5);
    }
    View::addLocation(StarterPaths::path("resources/themes/{$theme}/views"));
    foreach (['primary', 'secondary', 'success', 'info', 'warning', 'danger'] as $type) {
        $html = view('starter.templates.components.alert', ['type' => $type, 'message' => 'Pemeriksaan warna', 'dismissible' => false])->render();
        expect($html)->toContain('data-starter-feedback="'.$type.'"', 'Pemeriksaan warna');
    }
    $html = view('starter.templates.components.alert', ['type' => 'invalid', 'message' => '<script>bad</script>', 'dismissible' => false])->render();
    expect($html)->toContain('data-starter-feedback="info"', '&lt;script&gt;bad&lt;/script&gt;');
})->with(['tabler', 'vuexy', 'dashcode']);
