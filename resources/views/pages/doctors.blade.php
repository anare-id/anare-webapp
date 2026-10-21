@extends('layouts.app')
@section('title', __('site.doctors.title'))
@section('body-class', 'has-hero doctors-page')
@section('content')
<section class="hero catalog-hero viewport-hero doctors-hero" aria-labelledby="doctors-page-title" data-hero-carousel data-carousel-interval="{{ config('hero.interval') }}">
    <div class="hero-overlay hero-content"><h1 id="doctors-page-title">{{ __('site.doctors.hero') }}</h1><p>{{ __('site.doctors.intro') }}</p></div>
    @include('partials.hero-slideshow', ['id' => 'doctors-hero', 'label' => __('site.hero.gallery', ['name' => 'Anare Clinic']), 'slides' => config('hero.slides.doctors')])
</section>
<section id="doctor-list" class="container section" aria-label="{{ __('site.doctors.directory') }}">
    <div class="doctor-grid">
        @foreach(config('doctors.profiles') as $index => $doctor)
            @php $doctor = \App\Support\LocalizedContent::doctor($index, $doctor); @endphp
            <a class="doctor-card doctor-modal-trigger" href="{{ route('doctors.detail', ['slug' => 'doctor-'.$index]) }}" data-doctor-dialog="doctor-modal-{{ $index }}" aria-haspopup="dialog" aria-controls="doctor-modal-{{ $index }}">
                <span class="doctor-card-media"><img class="doctor-card-image" src="{{ asset('images/'.$doctor['profile_image']) }}" alt="{{ $doctor['name'] }}" loading="lazy"></span>
                <div><h3>{{ $doctor['name'] }}</h3><p>{{ !empty($doctor['is_dummy']) }}</p></div>
            </a>
        @endforeach
    </div>
</section>
@foreach(config('doctors.profiles') as $index => $doctor)
    @php $doctor = \App\Support\LocalizedContent::doctor($index, $doctor); @endphp
    @include('partials.doctor-modal', ['doctorIndex' => $index, 'doctor' => $doctor])
@endforeach
@endsection
