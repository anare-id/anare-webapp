@forelse($reviewFeed['reviews'] as $review)
    <article class="home-review {{ $loop->first ? 'home-review-primary' : '' }}" data-review-id="{{ $review['id'] }}" data-review-rating="{{ $review['rating'] }}">
        <div class="review-heading"><span class="review-stars" role="img" aria-label="{{ __('site.home.five_stars') }}">★★★★★</span><img class="review-platform-logo" src="{{ asset('images/google-logo.svg') }}" width="74" height="24" alt="Google" loading="lazy"></div>
        <p class="review-text" lang="{{ $review['language'] }}">{{ $review['text'] }}</p>
        <div class="review-footer">
            <a class="review-author" href="{{ $review['author_url'] }}" target="_blank" rel="noopener noreferrer" title="{{ $review['author'] }}"><span class="review-avatar" aria-hidden="true">{{ $review['initials'] }}</span><span class="review-author-name">{{ $review['author'] }}</span></a>
            <a class="review-source" href="{{ $review['review_url'] }}" target="_blank" rel="noopener noreferrer">{{ __('site.reviews.read_google') }} <span aria-hidden="true">↗</span></a>
        </div>
    </article>
@empty
    <div class="home-review home-reviews-empty"><span class="review-platform">Google Reviews</span><p>{{ __('site.reviews.empty') }}</p><a class="review-source" href="{{ $reviewFeed['reviews_url'] }}" target="_blank" rel="noopener noreferrer">{{ __('site.reviews.view_all') }} ↗</a></div>
@endforelse
