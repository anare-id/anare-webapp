@extends('layouts.app')
@section('title', $type === 'skin' ? 'Anare Skin' : 'Anare Shape')
@section('theme', $type)
@section('body-class', 'has-hero')
@section('content')
<section class="hero catalog-hero viewport-hero" aria-labelledby="catalog-title" data-hero-carousel data-carousel-interval="{{ config('hero.interval') }}">
    <div class="hero-overlay hero-content">
        <h1 id="catalog-title">{{ $type === 'skin' ? 'Treatment Reguler' : 'Endolipo, Slimming, Tightening' }}</h1>
        <p>Discover thoughtful care tailored to your needs.</p>
    </div>
    @include('partials.hero-slideshow', ['id' => 'catalog-hero', 'label' => 'Foto Anare '.$type, 'slides' => config('hero.slides.'.$type)])
</section>
<section class="container section">
    <div class="filters" aria-label="Treatment categories">
        <button type="button" class="active" data-filter="all">All Categories</button>
        @foreach(($type === 'skin' ? ['Acne', 'Scar', 'Pigmentation'] : ['Endolipo', 'Slimming', 'Tightening']) as $category)
            <button type="button" data-filter="{{ strtolower($category) }}">{{ $category }}</button>
        @endforeach
    </div>
    @include('partials.cards', ['type' => $type])
</section>
@endsection
