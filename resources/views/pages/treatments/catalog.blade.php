@extends('layouts.app')
@section('title', $serviceName)
@section('theme', $serviceKey.' treatment-page '.$serviceKey.'-page')
@section('body-class', 'treatment-catalog-page')
@section('content')
<section class="skin-hero treatment-catalog-hero" aria-labelledby="skin-title" data-hero-carousel data-carousel-interval="{{ $heroInterval }}">
    <div class="skin-hero-content hero-content">
        <h1 id="skin-title">{{ $heroTitle }}</h1>
        <p>{{ $heroDescription }}</p>
    </div>
    @include('partials.hero-slideshow', ['id' => $serviceKey.'-hero', 'label' => __('site.hero.gallery', ['name' => $serviceName]), 'slides' => $heroSlides])
</section>

<section id="treatments" class="skin-catalog container" aria-label="{{ __('site.catalog.list', ['name' => $serviceName]) }}">
    <nav class="skin-categories" aria-label="{{ __('site.catalog.categories') }}">
        @foreach($categories as $key => $label)
            <a class="skin-category-link {{ $key === $activeCategory ? 'active' : '' }}" href="{{ route($serviceKey, ['category' => $key]) }}#treatments" @if($key === $activeCategory) aria-current="true" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    @if($treatments->isNotEmpty())
        <div class="skin-treatment-grid">
            @foreach($treatments as $treatment)
                <a class="skin-treatment-card" href="{{ route($serviceKey.'.detail', ['slug' => $treatment['slug'], 'category' => $activeCategory, 'page' => $treatments->currentPage()]) }}">
                    @if($treatment['catalog_image'])<img src="{{ asset($treatment['catalog_image']) }}" alt="{{ __('site.common.at_clinic', ['name' => $treatment['name']]) }}" style="object-position: {{ $treatment['catalog_image_position'] ?: $treatment['image_position'] ?: 'center' }}" width="4000" height="6000" loading="lazy">@endif
                    <h2>{{ $treatment['name'] }}@if(!empty($treatment['subtitle']))<span>{{ $treatment['subtitle'] }}</span>@endif</h2>
                </a>
            @endforeach
        </div>
    @else
        <div class="skin-empty-state" role="status">
            @if($activeCategory !== null)<h2>{{ $categories[$activeCategory] }}</h2>@endif
            <p>{{ __('site.catalog.empty') }}</p>
            @if($defaultCategory !== null)<a class="button" href="{{ route($serviceKey) }}#treatments">{{ __('site.catalog.show_category', ['category' => $categories[$defaultCategory]]) }}</a>@endif
        </div>
    @endif

    @if($showPagination)
    <nav class="skin-pagination" aria-label="{{ __('site.catalog.pages') }}">
        @if($treatments->onFirstPage())
            <button class="pagination-number pagination-arrow" type="button" disabled aria-label="{{ __('site.catalog.previous') }}">‹</button>
        @else
            <a class="pagination-number pagination-arrow" href="{{ $treatments->previousPageUrl() }}" aria-label="{{ __('site.catalog.previous') }}">‹</a>
        @endif
        @foreach($pageNumbers as $number)
            @if($number === $treatments->currentPage())
                <span class="pagination-number active" aria-current="page" aria-label="{{ __('site.catalog.page', ['number' => $number]) }}">{{ $number }}</span>
            @elseif($number <= $treatments->lastPage())
                <a class="pagination-number" href="{{ $treatments->url($number) }}" aria-label="{{ __('site.catalog.go_page', ['number' => $number]) }}">{{ $number }}</a>
            @else
                <button class="pagination-number" type="button" disabled aria-label="{{ __('site.catalog.missing_page', ['number' => $number]) }}" title="{{ __('site.catalog.missing_page', ['number' => $number]) }}">{{ $number }}</button>
            @endif
        @endforeach
        @if($treatments->hasMorePages())
            <a class="pagination-number pagination-arrow" href="{{ $treatments->nextPageUrl() }}" aria-label="{{ __('site.catalog.next') }}">›</a>
        @else
            <button class="pagination-number pagination-arrow" type="button" disabled aria-label="{{ __('site.catalog.next') }}">›</button>
        @endif
    </nav>
    @endif
</section>
@endsection
