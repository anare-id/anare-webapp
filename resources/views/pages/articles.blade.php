@extends('layouts.app')
@section('title', __('site.articles.title'))
@section('body-class', 'articles-page')
@section('content')
<section class="container section">
    <nav class="filters" aria-label="{{ __('site.articles.category_label') }}">
        <a href="{{ route('articles') }}" @class(['active' => $activeCategory === 'all']) @if($activeCategory === 'all') aria-current="page" @endif>{{ __('site.articles.all') }}</a>
        @foreach($categories as $slug => $title)
            <a href="{{ route('articles', ['category' => $slug]) }}" @class(['active' => $activeCategory === $slug]) @if($activeCategory === $slug) aria-current="page" @endif>{{ $title }}</a>
        @endforeach
    </nav>
    <div class="card-grid">
        @forelse($articles as $article)
            <article class="service-card" data-category="{{ $article['category_slug'] }}">
                @if($article['image'])<img src="{{ asset($article['image']) }}" alt="{{ $article['title'] }}" loading="lazy">@endif
                <div class="card-copy">
                    <small>{{ $article['category_title'] }}</small>
                    @if($article['date'])<time datetime="{{ $article['date'] }}">{{ \Carbon\Carbon::parse($article['date'])->locale(app()->getLocale())->translatedFormat('d F Y') }}</time>@endif
                    <h3>{{ $article['title'] }}</h3>
                    <p>{{ $article['description'] }}</p>
                    <a class="card-btn" href="{{ route('articles.detail', ['slug' => $article['slug']]) }}">{{ __('site.common.read_more') }} ›</a>
                </div>
            </article>
        @empty
            <p>{{ __('site.catalog.empty') }}</p>
        @endforelse
    </div>
    @if($articles->hasPages())
    <nav class="skin-pagination" aria-label="{{ __('site.catalog.pages') }}">
        @if($articles->previousPageUrl())<a class="pagination-number pagination-arrow" href="{{ $articles->previousPageUrl() }}" aria-label="{{ __('site.catalog.previous') }}">‹</a>@endif
        @for($page = max(1, $articles->currentPage() - 2); $page <= min($articles->lastPage(), $articles->currentPage() + 2); $page++)
            <a href="{{ $articles->url($page) }}" @class(['pagination-number', 'active' => $page === $articles->currentPage()]) @if($page === $articles->currentPage()) aria-current="page" @endif>{{ $page }}</a>
        @endfor
        @if($articles->nextPageUrl())<a class="pagination-number pagination-arrow" href="{{ $articles->nextPageUrl() }}" aria-label="{{ __('site.catalog.next') }}">›</a>@endif
    </nav>
    @endif
</section>
@endsection
