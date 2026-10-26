<?php

use Aldhi88\StarterKit\Support\Starter\StarterPaths;

it('ships pinned local CMS editing libraries with their licenses and verified contents', function (): void {
    $manifest = json_decode(file_get_contents(StarterPaths::path('docs/cms-dependencies.json')), true, flags: JSON_THROW_ON_ERROR);
    expect($manifest['libraries']['quill'])->toBe(['version' => '2.0.3', 'license' => 'BSD-3-Clause'])
        ->and($manifest['libraries']['sortable'])->toBe(['version' => '1.15.7', 'license' => 'MIT']);
    foreach ($manifest['files'] as $file) {
        expect($file['file'])->toStartWith('public/assets/starter/vendor/')->not->toContain('..');
        $path = StarterPaths::path($file['file']);
        expect(is_file($path))->toBeTrue()
            ->and(hash_file('sha256', $path))->toBe($file['sha256'])
            ->and(filesize($path))->toBe($file['bytes']);
    }
    expect(file_get_contents(StarterPaths::path('public/assets/starter/vendor/quill/LICENSE')))->toContain('Redistribution and use')
        ->and(file_get_contents(StarterPaths::path('public/assets/starter/vendor/sortable/LICENSE')))->toContain('MIT License');
});
