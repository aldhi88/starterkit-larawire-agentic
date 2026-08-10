@php($position = $position ?? 'bottom')
@php($currentPage = $paginator->currentPage())
@php($lastPage = $paginator->lastPage())
@php($windowStart = max(1, min($currentPage - 2, $lastPage - 4)))
@php($windowEnd = min($lastPage, $windowStart + 4))

@if ($paginator->count() > 0)
    <nav class="starter-pg-pagination" aria-label="Navigasi halaman">
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                <button type="button" class="page-link" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" @disabled($paginator->onFirstPage()) aria-label="Halaman sebelumnya">&lsaquo;</button>
            </li>
            @for ($page = $windowStart; $page <= $windowEnd; $page++)
                <li class="page-item {{ $page === $currentPage ? 'active' : '' }}" wire:key="starter-pg-{{ $position }}-page-{{ $page }}">
                    @if ($page === $currentPage)
                        <span class="page-link" aria-current="page">{{ $page }}</span>
                    @else
                        <button type="button" class="page-link" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')">{{ $page }}</button>
                    @endif
                </li>
            @endfor
            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                <button type="button" class="page-link" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" @disabled(! $paginator->hasMorePages()) aria-label="Halaman berikutnya">&rsaquo;</button>
            </li>
        </ul>
    </nav>
@endif
