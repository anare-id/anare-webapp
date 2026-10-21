@extends('layouts.app')
@section('title', $doctor['name'])
@section('content')
<section class="container section">
    <a class="back" href="{{ route('doctors') }}">‹ {{ __('site.doctors.back') }}</a>
    <div class="detail-grid">
        <img class="doctor-profile-photo" src="{{ asset('images/'.$doctor['profile_image']) }}" alt="{{ $doctor['name'] }}">
        <div>
            <h1>{{ $doctor['name'] }}</h1>
            <p>{{ __('site.doctors.profile_intro') }}</p>
            <div class="info-panel"><h3>{{ __('site.doctors.experience') }}</h3><p>{{ __('site.doctors.experience_body') }}</p></div>
            <a class="button" href="{{ config('social.whatsapp.url') }}" target="_blank" rel="noopener noreferrer">{{ __('site.common.book') }}</a>
        </div>
    </div>
</section>
<section class="section muted"><div class="container center"><h2>{{ __('site.doctors.consult') }}</h2><p>{{ __('site.doctors.consult_body') }}</p></div></section>
<section class="container section">
    <h2>{{ __('site.doctors.others') }}</h2>
    <div class="doctor-grid">
        @foreach(config('doctors.profiles') as $index => $otherDoctor)
            @continue($index === $doctorIndex)
            @php $otherDoctor = \App\Support\LocalizedContent::doctor($index, $otherDoctor); @endphp
            <a class="doctor-card" href="{{ route('doctors.detail', ['slug' => 'doctor-'.$index]) }}">
                <img src="{{ asset('images/'.$otherDoctor['profile_image']) }}" alt="{{ $otherDoctor['name'] }}" loading="lazy">
                <div><h3>{{ $otherDoctor['name'] }}</h3></div>
            </a>
        @endforeach
    </div>
</section>
@endsection
