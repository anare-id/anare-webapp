<!doctype html>
<html lang="{{ app()->getLocale() }}">
@php $shapePage = request()->routeIs('shape', 'shape.*'); @endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $website->text('contact', 'name')) | {{ $website->text('contact', 'name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Cormorant+Garamond:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/anare.css') }}?v={{ filemtime(public_path('css/anare.css')) }}">
    <link rel="icon" type="image/png" href="{{ asset('images/'.($shapePage ? 'logo-shape-transparent.png' : 'logo-biru.png')) }}">
    <script src="{{ asset('js/anare.js') }}?v={{ filemtime(public_path('js/anare.js')) }}" defer></script>
    <style id="website-theme">{!! \App\Services\ThemePalette::css($website->settings()['theme'] ?? []) !!}</style>
</head>
<body class="@yield('theme', 'skin') @yield('body-class')">
    @include('partials.icons')
    <a class="skip-link" href="#main-content">{{ __('site.nav.skip') }}</a>
    <header class="site-header">
        <div class="nav-shell">
            <a class="logo" href="{{ route('home') }}" aria-label="{{ __('site.nav.home_label') }}">
                <img class="logo-default" src="{{ asset('images/'.($shapePage ? 'logo-shape-transparent.png' : 'logo-biru.png')) }}" alt="{{ $shapePage ? 'Anare Shape' : $website->text('contact', 'name') }}" width="{{ $shapePage ? 951 : 1080 }}" height="{{ $shapePage ? 882 : 1080 }}">
                <img class="logo-light" src="{{ asset('images/'.($shapePage ? 'logo-shape-transparent.png' : 'logo-putih.png')) }}" alt="" aria-hidden="true" width="{{ $shapePage ? 951 : 1254 }}" height="{{ $shapePage ? 882 : 1254 }}">
            </a>
            <nav id="primary-navigation" class="nav-links" aria-label="{{ __('site.nav.label') }}">
                <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>{{ __('site.nav.home') }}</a>
                <div class="dropdown">
                    <div class="services-trigger">
                        <a href="{{ request()->routeIs('shape', 'shape.*') ? route('shape') : route('skin') }}" @if(request()->routeIs('skin', 'skin.*', 'shape', 'shape.*')) aria-current="page" @endif>{{ request()->routeIs('skin', 'skin.*') ? 'Anare Skin' : __('site.nav.services') }}</a>
                        <button class="dropdown-toggle" type="button" aria-label="{{ __('site.js.show_services') }}" aria-expanded="false" aria-controls="services-navigation">
                            <svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="m4 6 4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                    <div id="services-navigation" class="dropdown-menu" hidden>
                        <a href="{{ route('skin') }}">Anare Skin</a>
                        <a href="{{ route('shape') }}">Anare Shape</a>
                    </div>
                </div>
                <a href="{{ route('doctors') }}" @if(request()->routeIs('doctors', 'doctors.*')) aria-current="page" @endif>{{ __('site.nav.doctors') }}</a>
                <a href="{{ route('articles') }}" @if(request()->routeIs('articles', 'articles.*')) aria-current="page" @endif>{{ __('site.nav.articles') }}</a>
            </nav>
            <div class="nav-actions">
                @include('partials.language-switcher')
                <button class="menu-toggle" type="button" aria-label="{{ __('site.js.open_navigation') }}" aria-expanded="false" aria-controls="primary-navigation">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>
    <main id="main-content">@yield('content')</main>
    <footer class="site-footer home-footer">
        @include('partials.clinic-footer')
    </footer>
    <script id="site-translations" type="application/json">@json(__('site.js'))</script>
</body>
</html>
