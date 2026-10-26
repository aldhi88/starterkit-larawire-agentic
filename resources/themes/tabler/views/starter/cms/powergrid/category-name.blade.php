@php($depth = min(5, max(0, (int) $category->getAttribute('hierarchy_depth'))))
<div class="d-flex align-items-start gap-2" data-cms-category-row data-id="{{ $category->getKey() }}" data-parent="{{ $category->parent_id ?? 0 }}" data-depth="{{ $depth }}" data-version="{{ $category->getAttribute('category_order_version') }}" data-reorderable="{{ $reorderable ? 'true' : 'false' }}">
    @if ($reorderable)
        <button type="button" class="btn btn-sm cms-category-row-handle" data-cms-category-handle aria-label="Geser {{ $category->name }}" aria-describedby="cms-category-drag-help" title="Seret untuk mengubah urutan. Gunakan Alt + panah atas/bawah dengan keyboard.">
            <svg class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" aria-hidden="true"><path d="M9 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0 M9 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0 M9 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0 M15 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0 M15 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0 M15 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg>
        </button>
    @endif
    <div class="cms-category-name cms-category-depth-{{ $depth }}">
    <div class="d-flex align-items-start gap-2">
        @if ($depth > 0)<span class="text-secondary" aria-hidden="true">↳</span>@endif
        <span class="fw-semibold">{{ $category->name }}</span>
    </div>
    @if ($depth > 0)<div class="text-secondary small mt-1">{{ $category->getAttribute('hierarchy_path') }}</div>@endif
    </div>
</div>
