@php
    $skin = $type === 'skin';
    $items = $skin
        ? [['Peeling', 'Acne', 'treatment4.JPG'], ['Facial Treatment', 'Scar', 'treatment3.JPG'], ['Skin Rejuvenation', 'Pigmentation', 'treatment5.JPG']]
        : [['Endolipo', 'Endolipo', 'treatment2.JPG'], ['Body Slimming', 'Slimming', 'treatment1.JPG'], ['Skin Tightening', 'Tightening', 'treatment5.JPG']];
@endphp
<div class="card-grid">
    @foreach($items as $item)
        <article class="service-card" data-category="{{ strtolower($item[1]) }}">
            <div class="image-wrap">
                <img src="{{ asset('images/'.$item[2]) }}" alt="{{ $item[0] }}" loading="lazy">
                <span class="image-label">{{ $item[1] }}</span>
            </div>
            <div class="card-copy">
                <h3>{{ $item[0] }}</h3>
                <p>Explore personalized aesthetic care and discover the right treatment for your needs.</p>
                <a class="card-btn" href="{{ url('/'.$type.'/'.\Illuminate\Support\Str::slug($item[0])) }}">View Details ›</a>
            </div>
        </article>
    @endforeach
</div>
