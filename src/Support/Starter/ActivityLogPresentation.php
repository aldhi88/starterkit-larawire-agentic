<?php

namespace Aldhi88\StarterKit\Support\Starter;

use Aldhi88\StarterKit\Models\Starter\ActivityLog;
use Illuminate\Support\Str;

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

    public static function actor(ActivityLog $log, bool $viewerIsSuperuser): string
    {
        if ($log->actor_is_superuser && ! $viewerIsSuperuser) {
            return 'Sistem';
        }

        return $log->actor_name ?: ($log->actor_username ?: ($log->source === 'automated' ? 'Sistem' : 'Pengguna tidak tercatat'));
    }

    public static function event(string $event): string
    {
        return match ($event) {
            'created' => 'Data dibuat', 'updated' => 'Data diubah', 'deleted' => 'Data dihapus',
            'restored' => 'Data dipulihkan', 'access', 'browse', 'view' => 'Data dilihat',
            'security' => 'Aktivitas keamanan', 'approved' => 'Disetujui',
            default => self::OUTCOMES[$event] ?? (config('starter.audit.event_labels', [])[$event] ?? Str::headline($event)),
        };
    }

    public static function target(ActivityLog $log): string
    {
        $type = config('starter.audit.models', [])[$log->auditable_type]['label'] ?? null;
        $name = $log->auditable_label;
        if (filled($name)) {
            return filled($type) ? $type.' · '.$name : $name;
        }

        $values = ($log->new_values ?? []) + ($log->old_values ?? []);
        foreach (['batch_no', 'code', 'name', 'title'] as $field) {
            if (isset($values[$field]) && is_scalar($values[$field]) && filled($values[$field]) && $values[$field] !== '[REDACTED]') {
                return ($type ?: self::fieldLabel($field)).' '.$values[$field];
            }
        }

        return trim(($type ?: 'Catatan').(filled($log->auditable_id) ? ' #'.$log->auditable_id : ''));
    }

    /** @return list<array{label: string, before: ?string, after: ?string}> */
    public static function changes(ActivityLog $log): array
    {
        $before = $log->old_values ?? [];
        $after = $log->new_values ?? [];
        $changes = [];
        foreach (array_keys($before + $after) as $field) {
            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;
            // Masked values can be identical even when the underlying secret changed.
            if ($old === $new && $old !== '[REDACTED]' && $log->event === 'updated') {
                continue;
            }
            if ($old === null && $new === null) {
                continue;
            }

            $changes[] = [
                'label' => self::fieldLabel((string) $field),
                'before' => $log->event === 'created' ? null : self::value($field, $old),
                'after' => $log->event === 'deleted' ? null : self::value($field, $new),
            ];
        }

        return $changes;
    }

    private static function fieldLabel(string $field): string
    {
        return match ($field) {
            'id' => 'ID data', 'code' => 'Kode', 'name' => 'Nama', 'title' => 'Judul',
            'last_value' => 'Nilai terakhir', 'batch_no' => 'Nomor batch', 'request_type' => 'Jenis permintaan',
            'variant' => 'Varian', 'origin_location_id' => 'ID lokasi asal', 'processing_date' => 'Tanggal proses',
            'created_by' => 'ID pembuat', 'updated_by' => 'ID pengguna yang mengubah',
            default => Str::ucfirst(str_replace('_', ' ', $field)),
        };
    }

    private static function value(string|int $field, mixed $value): string
    {
        if ($field === 'status' && is_string($value)) {
            $value = match (strtolower($value)) {
                'in_progress' => 'Sedang diproses', 'pending' => 'Menunggu',
                'completed' => 'Selesai', 'approved' => 'Disetujui', 'rejected' => 'Ditolak',
                'failed' => 'Gagal', default => $value,
            };
        }

        return match (true) {
            $value === null, $value === '' => 'Kosong',
            $value === '[REDACTED]' => 'Disembunyikan',
            is_bool($value) => $value ? 'Ya' : 'Tidak',
            is_array($value) => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default => (string) $value,
        };
    }

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
