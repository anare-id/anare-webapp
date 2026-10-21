@extends('layouts.app')
@section('title', __('site.articles.title'))
@section('body-class', 'articles-page')
@section('content')
@php $photos = ['article.JPG', '045A8509.png', 'IMG_0560.JPG']; @endphp
<section class="container section">
    <div class="filters" aria-label="{{ __('site.articles.category_label') }}">
        <button type="button" class="active" data-filter="all">{{ __('site.articles.all') }}</button>
        <button type="button" data-filter="skin">{{ __('site.articles.skin') }}</button>
        <button type="button" data-filter="beauty">{{ __('site.articles.beauty') }}</button>
    </div>
    <div class="card-grid">
        @for($i = 1; $i <= 3; $i++)
            <article class="service-card" data-category="{{ $i === 2 ? 'beauty' : 'skin' }}">
                <img src="{{ asset('images/'.$photos[$i - 1]) }}" alt="{{ __('site.articles.number', ['number' => $i]) }}" loading="lazy">
                <div class="card-copy">
                    <small>{{ __('site.articles.wellness') }}</small>
                    <h3>{{ __('site.articles.number', ['number' => $i]) }}</h3>
                    <p>{{ __('site.articles.summary') }}</p>
                    <a class="card-btn" href="{{ url('/articles/article-'.$i) }}">{{ __('site.common.read_more') }} ›</a>
                </div>
            </article>
        @endfor
    </div>
</section>
@endsection
