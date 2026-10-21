@php
    $treatmentFooter = request()->routeIs('skin', 'skin.*', 'shape', 'shape.*');
    $skinContact = collect(config('social.profiles'))->firstWhere('handle', 'anareskin.id');
@endphp
<div class="home-footer-grid">
    <div class="home-footer-about">
        <a class="footer-brand" href="{{ route('home') }}" aria-label="{{ __('site.nav.home_label') }}">
            <img src="{{ asset('images/logo-putih.png') }}" alt="Anare Clinic" width="1254" height="1254" loading="lazy">
        </a>
        <p class="footer-address">{{ __('site.footer.address') }}</p>
        <small class="footer-copyright">{{ __('site.copyright') }} © 2026 Anare Clinic. {{ __('site.footer.rights') }}</small>
    </div>
    <div>
        <h4>{{ __('site.footer.hours') }}</h4>
        <p>{{ __('site.footer.days') }}<br>09.00 - 19.00 WIB</p>
    </div>
    <div>
        <h4>{{ __('site.footer.follow') }}</h4>
        @foreach(config('social.profiles') as $account)
            @continue($treatmentFooter && $account['handle'] === 'anareskin.id')
            <a class="footer-contact-link" href="{{ $account['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram {{ $account['label'] }} &#64;{{ $account['handle'] }}">
                <span class="footer-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-instagram"/></svg></span>
                <span>{{ $account['handle'] }}</span>
            </a>
        @endforeach
    </div>
    <div>
        <h4>{{ __('site.footer.contact') }}</h4>
        <a class="footer-contact-link" href="{{ config('social.whatsapp.url') }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('site.footer.whatsapp', ['number' => config('social.whatsapp.number')]) }}">
            <span class="footer-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-whatsapp"/></svg></span>
            <span><span class="footer-contact-platform">WhatsApp</span><span>{{ config('social.whatsapp.number') }}</span></span>
        </a>
        @if($treatmentFooter && $skinContact)
            <a class="footer-contact-link" href="{{ $skinContact['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram {{ $skinContact['label'] }}">
                <span class="footer-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-instagram"/></svg></span>
                <span>{{ $skinContact['handle'] }}</span>
            </a>
        @endif
    </div>
</div>
