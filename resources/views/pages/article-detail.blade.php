@extends('layouts.app')
@section('title', __('site.articles.article'))
@section('body-class', 'articles-page')
@section('content')
<article class="container section article-body">
    <a class="back" href="{{ route('articles') }}">‹ {{ __('site.articles.back') }}</a>
    <img class="article-banner" src="{{ asset('images/article.JPG') }}" alt="{{ __('site.articles.article') }}">
    <p class="eyebrow">{{ mb_strtoupper(__('site.articles.wellness')) }}</p>
    <h1>{{ __('site.articles.headline') }}</h1>
    <p>{{ __('site.articles.intro') }}</p>
    <p>{{ __('site.articles.body') }}</p>
</article>
<section class="section muted center">
    <div class="container">
        <h2>{{ __('site.articles.care') }}</h2>
        <p>{{ __('site.articles.care_body') }}</p>
        <a class="button" href="{{ route('doctors') }}">{{ __('site.articles.meet') }}</a>
    </div>
</section>
@endsection
