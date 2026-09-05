@php($position = $position ?? 'bottom')
@php($currentPage = $paginator->currentPage())
@php($lastPage = $paginator->lastPage())
@php($windowStart = max(1, min($currentPage - 2, $lastPage - 4)))
@php($windowEnd = min($lastPage, $windowStart + 4))

@if ($paginator->count() > 0)
    <nav class="starter-pg-pagination" aria-label="Navigasi halaman" wire:loading.class="opacity-50">
        <div class="starter-pg-pages">
            <button type="button" class="starter-pg-page" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" @disabled($paginator->onFirstPage()) aria-label="Halaman sebelumnya">&lsaquo;</button>
            @for ($page = $windowStart; $page <= $windowEnd; $page++)
                @if ($page === $currentPage)
                    <span class="starter-pg-page is-active" wire:key="starter-pg-{{ $position }}-page-{{ $page }}" aria-current="page">{{ $page }}</span>
                @else
                    <button type="button" class="starter-pg-page" wire:key="starter-pg-{{ $position }}-page-{{ $page }}" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')">{{ $page }}</button>
                @endif
            @endfor
            <button type="button" class="starter-pg-page" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" @disabled(! $paginator->hasMorePages()) aria-label="Halaman berikutnya">&rsaquo;</button>
        </div>
    </nav>
@endif
