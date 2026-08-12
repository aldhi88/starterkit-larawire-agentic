<?php

use Aldhi88\StarterKit\Installation\StarterHostSnapshot;
use Illuminate\Support\Facades\File;

it('keeps env secrets only in memory while preserving rollback', function (): void {
    $originalBasePath = base_path();
    $fixture = sys_get_temp_dir().'/starter-host-snapshot-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($fixture);
    File::put($fixture.'/.env', "APP_KEY=base64:secret-that-must-not-reach-disk\n");
    chmod($fixture.'/.env', 0600);
    app()->setBasePath($fixture);
    $snapshot = null;

    try {
        $snapshot = StarterHostSnapshot::capture();
        $reflection = new ReflectionProperty($snapshot, 'directory');
        $snapshotDirectory = $reflection->getValue($snapshot);

        expect($snapshotDirectory)->toBeString()
            ->and(is_file($snapshotDirectory.'/.env'))->toBeFalse()
            ->and(File::get($snapshotDirectory.'/.manifest.json'))
            ->not->toContain('secret-that-must-not-reach-disk');

        File::put($fixture.'/.env', "APP_KEY=changed\n");
        $snapshot->restore();

        expect(File::get($fixture.'/.env'))->toContain('secret-that-must-not-reach-disk')
            ->and(fileperms($fixture.'/.env') & 0777)->toBe(0600);
    } finally {
        $snapshot?->discard();
        app()->setBasePath($originalBasePath);
        File::deleteDirectory($fixture);
    }
});
