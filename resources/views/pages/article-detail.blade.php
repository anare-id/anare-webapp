@extends('layouts.app')
@section('title', $article['title'])
@section('body-class', 'articles-page')
@section('content')
<article class="container section article-body">
    <a class="back" href="{{ route('articles') }}">‹ {{ __('site.articles.back') }}</a>
    @if($article['image'])<img class="article-banner" src="{{ asset($article['image']) }}" alt="{{ $article['title'] }}">@endif
    <p class="eyebrow">{{ mb_strtoupper($article['category_title']) }}</p>
    <h1>{{ $article['title'] }}</h1>
    @if($article['date'])<time datetime="{{ $article['date'] }}">{{ \Carbon\Carbon::parse($article['date'])->locale(app()->getLocale())->translatedFormat('d F Y') }}</time>@endif
    @if($article['author'])<p>{{ $article['author'] }}</p>@endif
    <p class="article-content">{{ $article['description'] }}</p>
    <div class="article-content">{{ $article['body'] }}</div>
</article>
<section class="section muted center">
    <div class="container">
        <h2>{{ __('site.articles.care') }}</h2>
        <p>{{ __('site.articles.care_body') }}</p>
        <a class="button" href="{{ route('doctors') }}">{{ __('site.articles.meet') }}</a>
    </div>
</section>
@endsection
