<footer class="distributor-pagination">
    <span>{{ $paginator->total() }} {{ $unit ?? 'registros' }}@if($paginator->lastPage() > 1) · Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}@endif</span>
    @if($paginator->hasPages())<nav aria-label="Paginación">
        @if($paginator->previousPageUrl())<a class="distributor-button secondary" href="{{ $paginator->previousPageUrl() }}">Anterior</a>@endif
        @if($paginator->nextPageUrl())<a class="distributor-button secondary" href="{{ $paginator->nextPageUrl() }}">Siguiente</a>@endif
    </nav>@endif
</footer>