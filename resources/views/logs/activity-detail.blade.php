@use(Aldhi88\StarterKit\Support\Starter\ActivityLogPresentation)
@php
    $firstLog = $selectedLogs->first();
    $viewerIsSuperuser = (bool) auth()->user()?->role?->isSuperuser();
    $firstActor = $firstLog ? ActivityLogPresentation::actor($firstLog, $viewerIsSuperuser) : null;
@endphp

@if ($firstLog)
    <div class="starter-log-intro">
        <strong>{{ $firstLog->action_label ?: 'Aktivitas tercatat' }}</strong>
        <p>{{ $firstActor }} · {{ $firstLog->created_at->translatedFormat('d F Y, H:i:s') }}</p>
    </div>

    <h4 class="{{ $sectionTitleClass }}">Yang terjadi ({{ $selectedLogs->count() }} langkah)</h4>
    <ol class="starter-log-steps">
        @foreach ($selectedLogs as $log)
            @php
                $actor = ActivityLogPresentation::actor($log, $viewerIsSuperuser);
                $changes = ActivityLogPresentation::changes($log);
                $recordedOutcome = $log->metadata['outcome'] ?? null;
                $outcome = is_string($recordedOutcome) ? (ActivityLogPresentation::OUTCOMES[$recordedOutcome] ?? $recordedOutcome) : null;
            @endphp
            <li wire:key="activity-log-entry-{{ $log->id }}">
                <div><strong>{{ ActivityLogPresentation::event($log->event) }}</strong> · {{ ActivityLogPresentation::target($log) }}</div>
                @if ($actor !== $firstActor || ! $firstLog->created_at->equalTo($log->created_at))
                    <div class="small text-secondary">{{ $actor }} · {{ $log->created_at->translatedFormat('d F Y, H:i:s') }}</div>
                @endif
                @if ($outcome)
                    <div>Hasil: {{ $outcome }}</div>
                @endif
                @if ($log->access_count !== null)
                    <div>Dibuka {{ $log->access_count }} kali. Pertama pada {{ $log->created_at->translatedFormat('d M Y, H:i:s') }}@if ($log->last_access_at), terakhir pada {{ $log->last_access_at->translatedFormat('d M Y, H:i:s') }}@endif.</div>
                @endif
                @if ($changes)
                    @if (count($changes) > 1)
                        <details>
                            <summary>Lihat {{ count($changes) }} isian</summary>
                    @endif
                    <dl class="starter-log-values">
                        @foreach ($changes as $change)
                            <dt>{{ $change['label'] }}</dt>
                            <dd>
                                @if ($change['before'] !== null && $change['after'] !== null)
                                    @if ($change['before'] === 'Disembunyikan' && $change['after'] === 'Disembunyikan')
                                        Nilainya berubah dan disembunyikan.
                                    @else
                                        Dari <strong>{{ $change['before'] }}</strong> menjadi <strong>{{ $change['after'] }}</strong>
                                    @endif
                                @else
                                    {{ $change['after'] ?? $change['before'] }}
                                @endif
                            </dd>
                        @endforeach
                    </dl>
                    @if (count($changes) > 1)
                        </details>
                    @endif
                @endif
            </li>
        @endforeach
    </ol>

    <details class="starter-log-reference">
        <summary>Informasi teknis</summary>
        <div>ID aktivitas: {{ $firstLog->action_id }}</div>
        @foreach ($selectedLogs as $log)
            <details>
                <summary>Referensi langkah {{ $loop->iteration }}</summary>
                <dl class="starter-log-values">
                    @foreach (['action_key' => 'Kode tindakan', 'sequence' => 'Urutan pencatatan', 'app_key' => 'Aplikasi', 'source' => 'Sumber', 'actor_username' => 'Username', 'actor_role' => 'Role', 'ip_address' => 'Alamat IP', 'route_name' => 'Rute', 'request_id' => 'ID permintaan', 'request_method' => 'Metode', 'request_path' => 'Alamat permintaan', 'user_agent' => 'Perangkat', 'auditable_type' => 'Model', 'auditable_id' => 'ID data', 'table_name' => 'Tabel'] as $key => $label)
                        @if (filled($log->$key) && (! in_array($key, ['actor_username', 'actor_role'], true) || ! $log->actor_is_superuser || $viewerIsSuperuser))
                            <dt>{{ $label }}</dt><dd>{{ $log->$key }}</dd>
                        @endif
                    @endforeach
                    @foreach (['old_values' => 'Data sebelum', 'new_values' => 'Data sesudah', 'metadata' => 'Metadata'] as $key => $label)
                        @if (filled($log->$key))
                            <dt>{{ $label }}</dt><dd><pre>{{ json_encode($log->$key, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre></dd>
                        @endif
                    @endforeach
                </dl>
            </details>
        @endforeach
    </details>
@else
    <p>Detail aktivitas tidak ditemukan.</p>
@endif
