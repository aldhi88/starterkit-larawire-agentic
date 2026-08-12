<?php

use Illuminate\Support\Facades\File;

function starterPhpFiles(string $directory): array
{
    if (! File::isDirectory($directory)) {
        return [];
    }

    return collect(File::allFiles($directory))
        ->filter(fn (SplFileInfo $file): bool => $file->getExtension() === 'php')
        ->map(fn (SplFileInfo $file): string => $file->getPathname())
        ->values()
        ->all();
}

it('keeps persistence and service location outside Livewire and controllers', function (): void {
    $files = [
        ...starterPhpFiles(dirname(__DIR__, 2).'/src/Livewire'),
        ...starterPhpFiles(dirname(__DIR__, 2).'/src/Http/Controllers'),
    ];
    $violations = [];

    foreach ($files as $file) {
        $source = File::get($file);

        foreach (['::query(', 'DB::', 'app(', 'resolve('] as $forbidden) {
            if (str_contains($source, $forbidden)) {
                $violations[] = basename($file)." uses {$forbidden}";
            }
        }
    }

    expect($violations)->toBe([]);
});

it('keeps direct model queries limited to documented infrastructure services', function (): void {
    $allowed = [
        'StarterIdentityService.php' => 'Installer bootstrap owns the first company, role, and login transaction.',
    ];
    $violations = [];

    foreach (starterPhpFiles(dirname(__DIR__, 2).'/src/Services') as $file) {
        $source = File::get($file);

        if (str_contains($source, '::query(') && ! array_key_exists(basename($file), $allowed)) {
            $violations[] = basename($file);
        }
    }

    expect($violations)->toBe([])
        ->and($allowed)->each->toBeString()->not->toBeEmpty();
});
