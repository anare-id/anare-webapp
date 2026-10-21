@extends('layouts.app')
@section('title', $treatment['name'])
@section('theme', $serviceKey.' treatment-page '.$serviceKey.'-page')
@section('content')
<section class="container skin-detail-intro">
    <a class="skin-back-link" href="{{ $backUrl }}"><span aria-hidden="true">‹</span> {{ __('site.treatment.back') }}</a>
    <div class="skin-detail-grid">
        <img class="skin-detail-photo" src="{{ asset('images/'.$treatment['image']) }}" alt="{{ __('site.common.at_clinic', ['name' => $treatment['name']]) }}" style="object-position: {{ $treatment['image_position'] ?? 'center' }}" width="4000" height="6000" fetchpriority="high">
        <div class="skin-detail-copy">
            <h1>{{ $treatment['name'] }}@if(!empty($treatment['subtitle']))<span>{{ $treatment['subtitle'] }}</span>@endif</h1>
                <span class="skin-treatment-duration">
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M12 6v6l4 2" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    {{ $treatment['duration'] ?? __('site.treatment.duration_unknown') }}
                </span>
            <p>{{ $treatment['description'] ?? __('site.treatment.contact', ['service' => $serviceName, 'name' => $treatment['name']]) }}</p>
        </div>
    </div>
</section>

<section class="skin-comparison-section" aria-labelledby="comparison-title">
    <div class="container">
        <h2 id="comparison-title">{{ __('site.treatment.comparison') }}</h2>
        <div class="skin-comparison-grid">
            @foreach(['before' => __('site.treatment.before'), 'after' => __('site.treatment.after')] as $key => $label)
                <figure>
                    <img src="{{ asset('images/'.$comparison[$key]) }}" alt="{{ $label }} — {{ !empty($comparison['illustrative']) ? __('site.treatment.illustration_alt') : $treatment['name'] }}" style="object-position: {{ $comparison['image_position'] ?? 'center 40%' }}" width="4000" height="6000" loading="lazy">
                    <figcaption>{{ $label }}</figcaption>
                </figure>
            @endforeach
        </div>
        @if(!empty($comparison['illustrative']))
            <p class="skin-comparison-note">{{ __('site.treatment.illustration') }}</p>
        @endif
    </div>
</section>

<section class="container skin-treatment-information" aria-labelledby="treatment-information-title">
    <h2 id="treatment-information-title">{{ __('site.treatment.more') }}</h2>
    <div class="skin-conditions">
        @foreach($conditions as $label => $content)
            <details name="treatment-conditions">
                <summary>
                    <span>{{ $label }}</span>
                    <svg viewBox="0 0 20 20" width="20" height="20" aria-hidden="true"><path d="m5 8 5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </summary>
                <p>{{ $content }}</p>
            </details>
        @endforeach
    </div>
</section>
@endsection
