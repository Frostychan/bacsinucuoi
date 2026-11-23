@if ($paginator->hasPages())
    <nav class="pagination-compact" role="navigation" aria-label="Điều hướng phân trang">
        @if ($paginator->onFirstPage())
            <span class="pagination-compact-disabled" aria-disabled="true" aria-label="Trang trước">‹</span>
        @else
            <a class="pagination-compact-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Trang trước">‹</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination-compact-dots" aria-hidden="true">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination-compact-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pagination-compact-link" href="{{ $url }}" aria-label="Trang {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="pagination-compact-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Trang sau">›</a>
        @else
            <span class="pagination-compact-disabled" aria-disabled="true" aria-label="Trang sau">›</span>
        @endif
    </nav>
@endif
