@php($position = $position ?? 'bottom')
@php($records = $this->records)

<footer id="pg-footer-{{ $position }}" class="{{ theme_style($theme, 'footer.footer') }} starter-pg-footer-{{ $position }}">
    <div class="{{ theme_style($theme, 'footer.footer_with_pagination') }}">
        <div class="starter-pg-footer-left">
            @if (filled(data_get($setUp, 'footer.perPage')) && count(data_get($setUp, 'footer.perPageValues')) > 1)
                <label class="starter-pg-per-page">
                    <span class="relative">
                        <select wire:model.live="setUp.footer.perPage" class="{{ theme_style($theme, 'footer.select') }}">
                            @foreach (data_get($setUp, 'footer.perPageValues') as $value)
                                <option value="{{ $value }}">{{ $value == 0 ? 'Semua' : $value }}</option>
                            @endforeach
                        </select>
                        <span class="starter-pg-select-icon" aria-hidden="true">
                            <x-livewire-powergrid::icons.down class="w-4 h-4" />
                        </span>
                    </span>
                    <span>Data per halaman</span>
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
                    <div class="starter-pg-record-count">Menampilkan <strong>{{ $records->firstItem() }}</strong> sampai <strong>{{ $records->lastItem() }}</strong> dari <strong>{{ $records->total() }}</strong> data</div>
                @elseif (data_get($setUp, 'footer.recordCount') === 'short')
                    <div class="starter-pg-record-count"><strong>{{ $records->firstItem() }}–{{ $records->lastItem() }}</strong> dari <strong>{{ $records->total() }}</strong></div>
                @elseif (data_get($setUp, 'footer.recordCount') === 'min')
                    <div class="starter-pg-record-count"><strong>{{ $records->firstItem() }}–{{ $records->lastItem() }}</strong></div>
                @endif
            @endif
        </div>
    </div>
</footer>
