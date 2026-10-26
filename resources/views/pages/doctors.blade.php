@extends('layouts.app')
@section('title', __('site.doctors.title'))
@section('body-class', 'has-hero doctors-page')
@section('content')
<section class="hero catalog-hero viewport-hero doctors-hero" aria-labelledby="doctors-page-title" data-hero-carousel data-carousel-interval="{{ $heroInterval }}">
    <div class="hero-overlay hero-content"><h1 id="doctors-page-title">{{ $website->text('doctors', 'title') }}</h1></div>
    @include('partials.hero-slideshow', ['id' => 'doctors-hero', 'label' => __('site.hero.gallery', ['name' => $website->text('contact', 'name')]), 'slides' => $heroSlides, 'showSlideContent' => false])
</section>
<section id="doctor-list" class="container section" aria-label="{{ __('site.doctors.directory') }}">
    <div class="doctor-grid">
        @foreach($doctors as $doctor)
            <a class="doctor-card doctor-modal-trigger" href="{{ route('doctors.detail', ['slug' => $doctor['slug']]) }}" data-doctor-dialog="doctor-modal-{{ $doctor['id'] }}" aria-haspopup="dialog" aria-controls="doctor-modal-{{ $doctor['id'] }}">
                @if($doctor['profile_image'])<span class="doctor-card-media"><img class="doctor-card-image" src="{{ asset($doctor['profile_image']) }}" alt="{{ $doctor['name'] }}" loading="lazy"></span>@endif
                <div><h3>{{ $doctor['name'] }}</h3></div>
            </a>
        @endforeach
    </div>
</section>
@foreach($doctors as $doctor)
    @include('partials.doctor-modal', ['doctorIndex' => $doctor['id'], 'doctor' => $doctor])
@endforeach
@endsection
