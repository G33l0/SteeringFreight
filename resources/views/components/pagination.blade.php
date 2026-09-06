@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-4 border-t border-ink-100 pt-4" aria-label="Pagination">
        <div class="text-sm text-ink-500">
            Showing {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </div>

        <div class="flex gap-2">
            @if ($paginator->onFirstPage())
                <span class="btn btn-outline btn-sm opacity-50">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-outline btn-sm">Previous</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-outline btn-sm">Next</a>
            @else
                <span class="btn btn-outline btn-sm opacity-50">Next</span>
            @endif
        </div>
    </nav>
@endif
