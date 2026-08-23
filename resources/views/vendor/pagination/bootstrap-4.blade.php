@if ($paginator->hasPages())
<nav>
    <ul class="pagination">
        {{-- Previous --}}
        <li class="{{ $paginator->onFirstPage() ? 'disabled' : '' }}">
            @if ($paginator->onFirstPage())
                <span>&lsaquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo;</a>
            @endif
        </li>

        {{-- Page Numbers --}}
        @foreach ($elements as $element)
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    <li class="{{ $page == $paginator->currentPage() ? 'active' : '' }}">
                        @if ($page == $paginator->currentPage())
                            <span>{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    </li>
                @endforeach
            @endif

            {{-- "..." separator --}}
            @if (is_string($element))
                <li class="disabled"><span>{{ $element }}</span></li>
            @endif
        @endforeach

        {{-- Next --}}
        <li class="{{ !$paginator->hasMorePages() ? 'disabled' : '' }}">
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">&rsaquo;</a>
            @else
                <span>&rsaquo;</span>
            @endif
        </li>
    </ul>
</nav>
@endif
