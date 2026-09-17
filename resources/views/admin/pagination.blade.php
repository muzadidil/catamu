@if ($paginator->hasPages())
<nav class="ad-pagination" aria-label="Paginasi">
  <span>Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ \App\Support\Format::number($paginator->total()) }}</span>
  <div class="ad-pagination-links">
    @if ($paginator->onFirstPage())
    <span class="ad-btn ad-btn--neutral ad-btn--sm is-disabled" aria-disabled="true">@include('admin.partials.icon', ['name' => 'arrow-left'])<span>Sebelumnya</span></span>
    @else
    <a class="ad-btn ad-btn--neutral ad-btn--sm" href="{{ $paginator->previousPageUrl() }}">@include('admin.partials.icon', ['name' => 'arrow-left'])<span>Sebelumnya</span></a>
    @endif
    <span class="ad-pagination-page">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
    @if ($paginator->hasMorePages())
    <a class="ad-btn ad-btn--neutral ad-btn--sm" href="{{ $paginator->nextPageUrl() }}"><span>Berikutnya</span>@include('admin.partials.icon', ['name' => 'arrow-right'])</a>
    @else
    <span class="ad-btn ad-btn--neutral ad-btn--sm is-disabled" aria-disabled="true"><span>Berikutnya</span>@include('admin.partials.icon', ['name' => 'arrow-right'])</span>
    @endif
  </div>
</nav>
@endif
