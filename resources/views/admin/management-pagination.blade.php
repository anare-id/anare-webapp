<div class="table-footer management-footer">
    <span>Menampilkan {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} dari {{ $rows->total() }} {{ $noun }}</span>
    <nav class="pagination" aria-label="Paginasi {{ $noun }}">
        @if($rows->previousPageUrl())<a class="icon-button" href="{{ $rows->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" title="Sebelumnya"><svg class="icon" aria-hidden="true" style="transform:rotate(180deg)"><use href="#a-chevron"/></svg></a>@else<button class="icon-button" type="button" disabled aria-label="Halaman sebelumnya"><svg class="icon" aria-hidden="true" style="transform:rotate(180deg)"><use href="#a-chevron"/></svg></button>@endif
        <span aria-current="page">{{ $rows->currentPage() }}</span>
        @if($rows->nextPageUrl())<a class="icon-button" href="{{ $rows->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" title="Berikutnya"><svg class="icon" aria-hidden="true"><use href="#a-chevron"/></svg></a>@else<button class="icon-button" type="button" disabled aria-label="Halaman berikutnya"><svg class="icon" aria-hidden="true"><use href="#a-chevron"/></svg></button>@endif
    </nav>
</div>
