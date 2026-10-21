@extends('layouts.app')
@section('title', __('site.nav.home'))
@section('body-class', 'has-hero')
@section('content')
<section class="anare-home-hero viewport-hero" aria-labelledby="home-title" data-hero-carousel data-carousel-interval="{{ config('hero.interval') }}">
    <div class="anare-home-hero-content hero-content">
        <h1 id="home-title" class="sr-only">Anare Clinic</h1>
        <p class="home-hero-copy">Lorem ipsum dolor sit amet, cupidatat mollit aute excepteur pariatur. Aliquip officia enim in velit fugiat dolore ut in irure cupidatat velit. Proident deserunt sunt dolore aliquip nostrud</p>
        <a class="home-book" href="{{ config('social.whatsapp.url') }}" target="_blank" rel="noopener noreferrer">
            <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><use href="#icon-whatsapp"/></svg>
            <span>{{ __('site.common.book') }}</span>
        </a>
    </div>
    @include('partials.hero-slideshow', ['id' => 'home-hero', 'label' => __('site.hero.gallery', ['name' => 'Anare Clinic']), 'slides' => config('hero.slides.home')])
</section>

<section class="home-block home-services" aria-labelledby="services-title">
    <div class="home-inner">
        <h2 id="services-title">{{ __('site.home.services') }}</h2>
        <div class="home-services-grid">
            <article class="home-service home-service-skin">
                <img src="{{ asset('images/anare-skin.png') }}" alt="Anare Skin aesthetic treatment" width="1672" height="941" loading="lazy">
                <div class="home-service-content">
                    <h3>Anare Clinic</h3>
                    <p>{{ __('site.home.skin_description') }}</p>
                    <a class="home-dark-button" href="{{ route('skin') }}">{{ __('site.common.detail') }} <span aria-hidden="true">❯</span></a>
                </div>
            </article>
            <article class="home-service home-service-shape">
                <img src="{{ asset('images/anare-shape.png') }}" alt="Anare Shape body treatment" width="1672" height="941" loading="lazy">
                <div class="home-service-content">
                    <h3>Anare Shape</h3>
                    <p>{{ __('site.home.shape_description') }}</p>
                    <a class="home-dark-button" href="{{ route('shape') }}">{{ __('site.common.detail') }} <span aria-hidden="true">❯</span></a>
                </div>
            </article>
        </div>
    </div>
</section>

<section class="home-doctor-feature" aria-labelledby="doctors-title">
    <div class="home-doctors-heading">
        <h2 id="doctors-title">{{ __('site.home.doctors') }}</h2>
        <p>{{ __('site.home.doctors_intro') }}</p>
        <p class="home-doctors-invitation">{{ __('site.home.doctors_invitation') }}</p>
    </div>
    <div class="home-doctor-team" aria-label="{{ __('site.home.team') }}">
        @foreach(config('doctors.home_order') as $doctorIndex)
            @php $doctor = \App\Support\LocalizedContent::doctor($doctorIndex, config('doctors.profiles.'.$doctorIndex)); @endphp
            <a class="home-doctor-link home-doctor-link-{{ $loop->iteration }}" href="{{ route('doctors.detail', ['slug' => 'doctor-'.$doctorIndex]) }}" aria-label="{{ __('site.home.profile', ['name' => $doctor['name']]) }}">
                <img class="home-doctor-photo" src="{{ asset('images/'.$doctor['home_image']) }}" alt="{{ $doctor['name'] }}" width="{{ config('doctors.home_image_width') }}" height="{{ config('doctors.home_image_height') }}" loading="lazy" decoding="async">
                <span class="doctor-profile-caption" aria-hidden="true"><strong class="doctor-name">{{ $doctor['name'] }}</strong></span>
            </a>
        @endforeach
    </div>
</section>

<section class="home-block home-featured" aria-labelledby="article-title">
    <div class="home-inner home-featured-grid">
        <img src="{{ asset('images/article.JPG') }}" alt="Anare Clinic skincare consultation" width="4000" height="6000" loading="lazy">
        <div>
            <time class="home-date" datetime="2026-09-30">{{ __('site.home.article_date') }}</time>
            <h2 id="article-title">{{ __('site.home.article_title') }}</h2>
            <p>Lorem ipsum dolor sit amet, cupidatat mollit aute excepteur pariatur. Aliquip officia enim in velit fugiat dolore ut in irure cupidatat velit. Proident deserunt sunt dolore aliquip nostrud veniam esse reprehenderit deserunt et labore aliqua. Quis irure...</p>
            <a class="home-dark-button" href="{{ route('articles') }}">{{ __('site.common.read_more') }} <span aria-hidden="true">❯</span></a>
        </div>
    </div>
</section>

<section class="home-block home-social" aria-labelledby="instagram-title" data-instagram-feed data-feed-url="{{ route('instagram.feed') }}" data-instagram-mode="{{ $instagramPosts ? 'feed' : 'empty' }}">
    <div class="home-inner">
        <h2 id="instagram-title">{{ __('site.home.instagram') }}</h2>
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
            <h2>{{ __('site.home.location') }}</h2>
            <div class="home-location-card">
                <iframe title="{{ __('site.home.map_title') }}" src="https://maps.google.com/maps?q=Anare+Clinic+Jl.+Rungkut+Asri+Utara+XIX+No.6+Surabaya&z=17&output=embed&hl={{ app()->getLocale() }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            </div>
        </div>
        <div class="home-contact-column home-reviews-column" data-google-reviews data-reviews-url="{{ route('google.reviews') }}" data-review-source="{{ $reviewFeed['source'] }}" data-loading-label="{{ __('site.reviews.loading') }}" data-selected-label="{{ __('site.reviews.selected') }}" data-saved-label="{{ __('site.reviews.saved') }}">
            <h2>{{ __('site.home.clients') }}</h2>
            <p class="sr-only" data-google-reviews-status role="status" aria-live="polite">{{ __('site.reviews.selected') }}</p>
            <div class="home-reviews-stack" data-google-reviews-content>@include('partials.google-reviews')</div>
        </div>
    </div>
</section>
@endsection
