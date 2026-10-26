<div class="hero-slider" aria-label="{{ $label }}" aria-roledescription="carousel" role="group">
    <div id="{{ $id }}-track" class="hero-slider-track">
        @foreach($slides as $slide)
            <div class="hero-slide {{ !empty($slide['team']) ? 'hero-slide-team' : '' }}" data-hero-slide role="group" aria-roledescription="slide" aria-label="{{ __('site.js.photo_status', ['number' => $loop->iteration, 'total' => count($slides)]) }}" aria-hidden="{{ $loop->first ? 'false' : 'true' }}" @if(!$loop->first) inert @endif style="--slide-position: {{ $slide['position'] ?: 'center' }}; --slide-mobile-position: {{ $slide['mobile_position'] ?: $slide['position'] ?: 'center' }}; --slide-fit: {{ $slide['fit'] ?: 'cover' }}; --slide-background: {{ $slide['background'] ?: '#747b80' }}">
                @if($slide['image'] || $slide['mobile_image'])
                <picture>
                    @if(!empty($slide['mobile_image']))
                        <source media="(max-width: 600px), (max-width: 1024px) and (orientation: portrait)" srcset="{{ asset($slide['mobile_image']) }}">
                    @endif
                    <img src="{{ asset($slide['image'] ?: $slide['mobile_image']) }}" alt="{{ $slide['alt'] }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}" fetchpriority="{{ $loop->first ? 'high' : 'low' }}" decoding="async">
                </picture>
                @endif
                @if(($showSlideContent ?? true) && (!empty($slide['title']) || !empty($slide['description']) || !empty($slide['button'])))
                    <div class="hero-slide-content">
                        @if(!empty($slide['title']))<h2>{{ $slide['title'] }}</h2>@endif
                        @if(!empty($slide['description']))<p class="home-hero-copy">{{ $slide['description'] }}</p>@endif
                        @if(!empty($slide['button']) && !empty($slide['url']))
                            <a class="home-book" href="{{ $slide['url'] }}">{{ $slide['button'] }}</a>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
<div class="hero-carousel-controls" data-controls-style="plain" aria-label="{{ __('site.js.slideshow_controls') }}">
    <button class="carousel-arrow" type="button" data-carousel-prev aria-controls="{{ $id }}-track" aria-label="{{ __('site.js.previous_photo') }}">
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="m14 6-6 6 6 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
    <div class="carousel-dots">
        @foreach($slides as $slide)
            <button class="carousel-dot {{ $loop->first ? 'active' : '' }}" type="button" data-carousel-dot="{{ $loop->index }}" aria-controls="{{ $id }}-track" aria-label="{{ __('site.js.show_photo', ['number' => $loop->iteration]) }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"><span></span></button>
        @endforeach
    </div>
    <button class="carousel-arrow" type="button" data-carousel-next aria-controls="{{ $id }}-track" aria-label="{{ __('site.js.next_photo') }}">
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="m10 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
    <button class="carousel-playback" type="button" data-carousel-pause aria-label="{{ __('site.js.pause_slideshow') }}" aria-controls="{{ $id }}-track" aria-pressed="false">
        <svg class="carousel-pause-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M8 5v14M16 5v14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
        <svg class="carousel-play-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="m9 5 11 7-11 7Z" fill="currentColor"/></svg>
    </button>
</div>
<span class="sr-only" data-carousel-status aria-live="off" aria-atomic="true">{{ __('site.js.photo_status', ['number' => 1, 'total' => count($slides)]) }}</span>
