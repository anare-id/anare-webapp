<div class="language-switcher" role="group" aria-label="{{ __('site.nav.languages') }}">
    @foreach(config('app.supported_locales') as $code => $name)
        <form method="POST" action="{{ route('locale.update', ['locale' => $code]) }}" class="language-form" data-language-form>
            @csrf
            <input type="hidden" name="return_to" value="{{ request()->getRequestUri() }}">
            <button class="language-option {{ app()->getLocale() === $code ? 'active' : '' }}" type="submit" lang="{{ $code }}" aria-label="{{ __('site.nav.switch_'.$code) }}" aria-pressed="{{ app()->getLocale() === $code ? 'true' : 'false' }}" title="{{ $name }}">{{ strtoupper($code) }}</button>
        </form>
    @endforeach
</div>
