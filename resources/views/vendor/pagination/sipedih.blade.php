@if ($paginator->hasPages())
<nav class="sipedih-pagination">
    <div class="pag-info">
        Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data
    </div>
    <div class="pag-links">
        @if ($paginator->onFirstPage())
            <span class="pag-btn disabled">‹ Sebelumnya</span>
        @else
            <a class="pag-btn" href="{{ $paginator->previousPageUrl() }}">‹ Sebelumnya</a>
        @endif

        <span class="pag-current">
            Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}
        </span>

        @if ($paginator->hasMorePages())
            <a class="pag-btn" href="{{ $paginator->nextPageUrl() }}">Selanjutnya ›</a>
        @else
            <span class="pag-btn disabled">Selanjutnya ›</span>
        @endif
    </div>
</nav>
@endif