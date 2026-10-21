@extends('layouts.app')
@section('title', __('site.nav.home'))
@section('body-class', 'has-hero')
@section('content')
<section class="anare-home-hero viewport-hero" aria-labelledby="home-title" data-hero-carousel data-carousel-interval="{{ $heroInterval }}">
    <h1 id="home-title" class="sr-only">{{ $website->text('contact', 'name') }}</h1>
    @include('partials.hero-slideshow', ['id' => 'home-hero', 'label' => __('site.hero.gallery', ['name' => $website->text('contact', 'name')]), 'slides' => $heroSlides])
</section>

<section class="home-block home-services" aria-labelledby="services-title">
    <div class="home-inner">
        <h2 id="services-title">{{ $website->text('home', 'services_title') }}</h2>
        <div class="home-services-grid">
            @foreach($services as $service)
                <article class="home-service home-service-{{ $loop->first ? 'skin' : 'shape' }}" @if(!$service['image']) style="min-height: 0" @endif>
                    @if($service['image'])<img src="{{ asset($service['image']) }}" alt="{{ $service['title'] }}" width="1672" height="941" loading="lazy">@endif
                    <div class="home-service-content" @if(!$service['image']) style="width: 100%; padding: 24px" @endif>
                        <h3>{{ $service['title'] }}</h3>
                        <p>{{ $service['description'] }}</p>
                        @if($service['url'])<a class="home-dark-button" href="{{ $service['url'] }}">{{ __('site.common.detail') }} <span aria-hidden="true">❯</span></a>@endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="home-doctor-feature" aria-labelledby="doctors-title">
    <div class="home-doctors-heading">
        <h2 id="doctors-title">{{ $website->text('home', 'doctors_title') }}</h2>
        <p>{{ $website->text('home', 'doctors_description') }}</p>
        <p class="home-doctors-invitation">{{ $website->text('home', 'doctors_invitation') }}</p>
    </div>
    <div class="home-doctor-team" style="--featured-doctor-count: {{ max(1, min(6, count($featuredDoctors))) }}; --featured-doctor-mobile-count: {{ max(1, min(3, count($featuredDoctors))) }}; @if(!collect($featuredDoctors)->contains(fn ($doctor) => !empty($doctor['home_image']))) grid-auto-rows: auto; @endif" aria-label="{{ __('site.home.team') }}">
        @foreach($featuredDoctors as $doctor)
            <a class="home-doctor-link home-doctor-link-{{ $loop->iteration }}" @if(!$doctor['home_image']) style="height: auto; align-self: end; padding: 24px 8px" @endif href="{{ route('doctors.detail', ['slug' => $doctor['slug']]) }}" aria-label="{{ __('site.home.profile', ['name' => $doctor['name']]) }}">
                @if($doctor['home_image'])<img class="home-doctor-photo" src="{{ asset($doctor['home_image']) }}" alt="{{ $doctor['name'] }}" @if($doctor['home_image_width']) width="{{ $doctor['home_image_width'] }}" @endif @if($doctor['home_image_height']) height="{{ $doctor['home_image_height'] }}" @endif loading="lazy" decoding="async">@endif
                <span class="doctor-profile-caption" @if(!$doctor['home_image']) style="position: static; width: auto; transform: none; opacity: 1; visibility: visible" @endif aria-hidden="true"><strong class="doctor-name">{{ $doctor['name'] }}</strong></span>
            </a>
        @endforeach
    </div>
</section>

@foreach($featuredArticles as $article)
<section class="home-block home-featured" aria-labelledby="article-title-{{ $article['id'] }}">
    <div class="home-inner home-featured-grid" @if(!$article['image']) style="grid-template-columns: minmax(0, 1fr)" @endif>
        @if($article['image'])<img src="{{ asset($article['image']) }}" alt="{{ $article['title'] }}" width="4000" height="6000" loading="lazy">@endif
        <div>
            @if($article['date'])<time class="home-date" datetime="{{ $article['date'] }}">{{ \Carbon\Carbon::parse($article['date'])->locale(app()->getLocale())->translatedFormat('d F Y') }}</time>@endif
            <h2 id="article-title-{{ $article['id'] }}">{{ $article['title'] }}</h2>
            <p>{{ $article['description'] }}</p>
            <a class="home-dark-button" href="{{ route('articles.detail', ['slug' => $article['slug']]) }}">{{ __('site.common.read_more') }} <span aria-hidden="true">❯</span></a>
        </div>
    </div>
</section>
@endforeach

<section class="home-block home-social" aria-labelledby="instagram-title" data-instagram-feed data-feed-url="{{ route('instagram.feed') }}" data-instagram-mode="{{ $instagramPosts ? 'feed' : 'empty' }}">
    <div class="home-inner">
        <h2 id="instagram-title">{{ $website->text('home', 'instagram_title') }}</h2>
        <p class="sr-only" data-instagram-status role="status" aria-live="polite">{{ __('site.js.instagram_gallery') }}</p>
        <div class="instagram-feed-content" data-instagram-content>
            @if($instagramPosts)
                <div class="home-instagram-grid instagram-post-grid">
                    @foreach($instagramPosts as $post)
                        <a class="instagram-post" href="{{ $post['permalink'] }}" target="_blank" rel="noopener noreferrer" data-instagram-account="{{ $post['username'] }}" aria-label="{{ __('site.js.instagram_post', ['username' => $post['username']]) }}" title="{{ $post['caption'] }}">
                            <img src="{{ $post['image_url'] }}" alt="{{ $post['caption'] ?: __('site.js.instagram_alt', ['username' => $post['username']]) }}" width="512" height="512" loading="lazy" decoding="async">
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="instagram-profile-links {{ $instagramPosts ? 'sr-only' : '' }}" data-instagram-fallback>
            @foreach($instagramAccounts as $account)
                <a href="{{ $account['profile_url'] }}" target="_blank" rel="noopener noreferrer">Instagram &#64;{{ $account['username'] }}</a>
            @endforeach
        </div>
    </div>
</section>

<section id="contact" class="home-block home-contact">
    <div class="home-inner home-contact-grid">
        <div class="home-contact-column home-location-column">
            <h2>{{ $website->text('home', 'location_title') }}</h2>
            @if($website->mapEmbedUrl())
            <div class="home-location-card">
                <iframe title="{{ __('site.home.map_title') }}" src="{{ $website->mapEmbedUrl() }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            </div>
            @endif
            @if($website->setting('contact', 'maps'))<a href="{{ $website->setting('contact', 'maps') }}" target="_blank" rel="noopener noreferrer">{{ $website->text('contact', 'address') }}</a>@endif
        </div>
        <div class="home-contact-column home-reviews-column" data-google-reviews data-reviews-url="{{ route('google.reviews') }}" data-review-source="{{ $reviewFeed['source'] }}" data-loading-label="{{ __('site.reviews.loading') }}" data-selected-label="{{ __('site.reviews.selected') }}" data-saved-label="{{ __('site.reviews.saved') }}">
            <h2>{{ $website->text('home', 'reviews_title') }}</h2>
            <p class="sr-only" data-google-reviews-status role="status" aria-live="polite">{{ __('site.reviews.selected') }}</p>
            <div class="home-reviews-stack" data-google-reviews-content>@include('partials.google-reviews')</div>
        </div>
    </div>
</section>
@endsection
