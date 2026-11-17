<?php

use Aldhi88\StarterKit\Models\Starter\ActivityLog;
use Aldhi88\StarterKit\Support\Starter\ActivityLogPresentation;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;

it('explains historical changes and keeps the original values in optional technical details', function (): void {
    $log = new ActivityLog([
        'action_label' => 'Membuat batch', 'actor_name' => 'Operator', 'created_at' => now(),
        'event' => 'created', 'new_values' => ['batch_no' => 'DEMO-1', 'status' => 'IN_PROGRESS', 'variant' => null],
        'route_name' => 'example.update', 'auditable_type' => 'OperationBatch',
    ]);
    $html = view()->file(StarterPaths::path('resources/views/logs/activity-detail.blade.php'), ['selectedLogs' => collect([$log]), 'sectionTitleClass' => 'starter-modal-section-title'])->render();
    [$details, $technical] = explode('<summary>Informasi teknis</summary>', $html);
    expect($details)->toContain('Yang terjadi (1 langkah)', 'Nomor batch DEMO-1', 'Sedang diproses', 'Lihat 2 isian')
        ->not->toContain('variant', 'example.update', 'IN_PROGRESS', 'OperationBatch')
        ->and($technical)->toContain('variant', 'example.update', 'IN_PROGRESS', 'OperationBatch');
});

it('distinguishes zero, empty and masked changes without reinterpreting an identifier', function (): void {
    $log = new ActivityLog(['event' => 'updated', 'old_values' => ['last_value' => 0, 'note' => 'abc', 'password' => '[REDACTED]', 'flag' => false],
        'new_values' => ['last_value' => 1, 'note' => null, 'password' => '[REDACTED]', 'flag' => false, 'code' => 'IN_PROGRESS']]);
    expect(ActivityLogPresentation::changes($log))->toBe([
        ['label' => 'Nilai terakhir', 'before' => '0', 'after' => '1'],
        ['label' => 'Note', 'before' => 'abc', 'after' => 'Kosong'],
        ['label' => 'Password', 'before' => 'Disembunyikan', 'after' => 'Disembunyikan'],
        ['label' => 'Kode', 'before' => 'Kosong', 'after' => 'IN_PROGRESS'],
    ]);
});
