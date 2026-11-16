<?php

namespace Aldhi88\StarterKit\Support\Starter;

use Aldhi88\StarterKit\Models\Starter\ActivityLog;

final class ActivityLogPresentation
{
    public const CATEGORIES = [
        'business' => 'Bisnis / pengguna',
        'access' => 'Akses data',
        'security' => 'Keamanan',
        'automated' => 'Proses otomatis',
        'mixed' => 'Gabungan kategori',
    ];

    public const OUTCOMES = [
        'success' => 'Berhasil', 'failed' => 'Gagal', 'denied' => 'Ditolak',
        'rejected' => 'Ditolak', 'queued' => 'Diantrekan', 'skipped' => 'Dilewati',
        'validation_failed' => 'Validasi gagal', 'mixed' => 'Hasil beragam', 'partial' => 'Sebagian berhasil',
    ];

    public static function summary(ActivityLog $log): string
    {
        $label = $log->action_label ?: 'Aktivitas';
        $outcome = $log->outcome;
        if ($outcome === null && (int) $log->event_count === 1) {
            $outcome = in_array($log->event, ['created', 'updated', 'deleted', 'restored', 'approved'], true)
                ? 'success' : $log->event;
        }
        if (is_string($outcome) && isset(self::OUTCOMES[$outcome])) {
            $label .= ' · '.self::OUTCOMES[$outcome];
        }
        if ((int) $log->occurrences > (int) $log->changes_count) {
            $label .= ' · '.$log->occurrences.' akses (dirangkum)';
        }

        return $label;
    }
}
