@extends('layouts.app')
@section('title', $doctor['name'])
@section('content')
<section class="container section">
    <a class="back" href="{{ route('doctors') }}">‹ {{ __('site.doctors.back') }}</a>
    <div class="detail-grid" @if(!$doctor['profile_image']) style="grid-template-columns: minmax(0, 1fr)" @endif>
        @if($doctor['profile_image'])<img class="doctor-profile-photo" src="{{ asset($doctor['profile_image']) }}" alt="{{ $doctor['name'] }}">@endif
        <div>
            <h1>{{ $doctor['name'] }}</h1>
            <p>{{ $doctor['specialty'] }}</p>
            <p class="article-content">{{ $doctor['description'] }}</p>
            <div class="info-panel"><h3>{{ __('site.doctors.schedule') }}</h3><p class="article-content">{{ $doctor['schedule_summary'] }}</p></div>
            @if($whatsappUrl)<a class="button" href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer">{{ __('site.common.book') }}</a>@endif
        </div>
    </div>
</section>
<section class="container section">
    @if($doctor['schedule_image'])<img class="doctor-schedule-image" src="{{ asset($doctor['schedule_image']) }}" alt="{{ __('site.doctors.schedule_alt', ['name' => $doctor['name'], 'schedule' => $doctor['schedule_summary']]) }}" loading="lazy">@endif
    <h2>{{ __('site.doctors.education') }}</h2>
    <p class="article-content">{{ $doctor['education'] }}</p>
    <h2>{{ __('site.doctors.expertise') }}</h2>
    <div class="doctor-expertise-grid">@foreach($doctor['expertise'] as $expertise)<p>{{ $expertise }}</p>@endforeach</div>
</section>
@if($otherDoctors)
<section class="container section">
    <h2>{{ __('site.doctors.others') }}</h2>
    <div class="doctor-grid">
        @foreach($otherDoctors as $otherDoctor)
            <a class="doctor-card" href="{{ route('doctors.detail', ['slug' => $otherDoctor['slug']]) }}">
                @if($otherDoctor['profile_image'])<img src="{{ asset($otherDoctor['profile_image']) }}" alt="{{ $otherDoctor['name'] }}" loading="lazy">@endif
                <div><h3>{{ $otherDoctor['name'] }}</h3></div>
            </a>
        @endforeach
    </div>
</section>
@endif
@endsection
