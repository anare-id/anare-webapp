<div class="home-footer-grid">
    <div class="home-footer-about">
        <a class="footer-brand {{ request()->routeIs('shape', 'shape.*') ? 'footer-brand-shape' : '' }}" href="{{ route('home') }}" aria-label="{{ __('site.nav.home_label') }}">
            @if(request()->routeIs('shape', 'shape.*'))
                <img src="{{ asset('images/logo-shape-transparent.png') }}" alt="Anare Shape" width="951" height="882" loading="lazy">
            @else
                <img src="{{ asset('images/logo-putih.png') }}" alt="{{ $website->text('contact', 'name') }}" width="1254" height="1254" loading="lazy">
            @endif
        </a>
        <p class="footer-address">{{ $website->text('contact', 'address') }}</p>
        <small class="footer-copyright">{{ __('site.copyright') }} © {{ date('Y') }} {{ $website->text('contact', 'name') }}. {{ __('site.footer.rights') }}</small>
    </div>
    <div>
        <h4>{{ __('site.footer.hours') }}</h4>
        <p>{{ $website->text('contact', 'days') }}<br>{{ $website->text('contact', 'hours') }}</p>
    </div>
    <div>
        <h4>{{ __('site.footer.follow') }}</h4>
        @foreach($socialProfiles as $account)
            <a class="footer-contact-link" href="{{ $account['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram {{ $account['label'] }} &#64;{{ $account['handle'] }}">
                <span class="footer-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-instagram"/></svg></span>
                <span>{{ $account['handle'] }}</span>
            </a>
        @endforeach
    </div>
    <div>
        <h4>{{ __('site.footer.contact') }}</h4>
        @if($whatsappUrl)
        <a class="footer-contact-link" href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('site.footer.whatsapp', ['number' => $website->setting('contact', 'whatsapp')]) }}">
            <span class="footer-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-whatsapp"/></svg></span>
            <span><span class="footer-contact-platform">WhatsApp</span><span>{{ $website->setting('contact', 'whatsapp') }}</span></span>
        </a>
        @endif
        @if($website->setting('contact', 'email'))
            <a class="footer-contact-link" href="mailto:{{ $website->setting('contact', 'email') }}">{{ $website->setting('contact', 'email') }}</a>
        @endif
    </div>
</div>
