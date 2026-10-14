@if ($paginator->hasPages())
<div class="pagination">
    @if ($paginator->onFirstPage())
    <span class="disabled">&laquo; Prev</span>
    @else
    <a href="{{ $paginator->previousPageUrl() }}">&laquo; Prev</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="disabled">{{ $element }}</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
    <a href="{{ $paginator->nextPageUrl() }}">Next &raquo;</a>
    @else
    <span class="disabled">Next &raquo;</span>
    @endif
</div>
@endif
