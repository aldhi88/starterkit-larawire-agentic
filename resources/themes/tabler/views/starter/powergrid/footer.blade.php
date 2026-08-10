@php($position = $position ?? 'bottom')
@php($records = $this->records)

<footer id="pg-footer-{{ $position }}" class="{{ theme_style($theme, 'footer.footer') }} starter-pg-footer-{{ $position }}">
    <div class="starter-pg-footer-left">
        @if (filled(data_get($setUp, 'footer.perPage')) && count(data_get($setUp, 'footer.perPageValues')) > 1)
            <label class="starter-pg-per-page d-flex align-items-center gap-2">
                <select wire:model.live="setUp.footer.perPage" class="form-select {{ theme_style($theme, 'footer.select') }}">
                    @foreach (data_get($setUp, 'footer.perPageValues') as $value)
                        <option value="{{ $value }}">{{ $value == 0 ? 'Semua' : $value }}</option>
                    @endforeach
                </select>
                <small class="text-muted text-nowrap">Data per halaman</small>
            </label>
        @endif
    </div>

    <div class="starter-pg-footer-center">
        @if (method_exists($records, 'links'))
            {!! $records->links('starter.powergrid.pagination', ['position' => $position]) !!}
        @endif
    </div>

    <div class="starter-pg-footer-right">
        @if ($records->count() > 0)
            @if (data_get($setUp, 'footer.recordCount') === 'full')
                <small class="starter-pg-record-count text-muted text-nowrap">Menampilkan <strong>{{ $records->firstItem() }}</strong> sampai <strong>{{ $records->lastItem() }}</strong> dari <strong>{{ $records->total() }}</strong> data</small>
            @elseif (data_get($setUp, 'footer.recordCount') === 'short')
                <small class="starter-pg-record-count text-muted text-nowrap"><strong>{{ $records->firstItem() }}–{{ $records->lastItem() }}</strong> dari <strong>{{ $records->total() }}</strong></small>
            @elseif (data_get($setUp, 'footer.recordCount') === 'min')
                <small class="starter-pg-record-count text-muted text-nowrap"><strong>{{ $records->firstItem() }}–{{ $records->lastItem() }}</strong></small>
            @endif
        @endif
    </div>
</footer>
