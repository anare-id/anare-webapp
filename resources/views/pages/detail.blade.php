@extends('layouts.app')
@section('title', 'Treatment Details')
@section('theme', $type)
@section('content')
@php
    $title = ucwords(str_replace('-', ' ', $slug));
    $photos = $type === 'skin' ? ['treatment4.JPG', 'treatment3.JPG'] : ['treatment2.JPG', 'treatment1.JPG'];
@endphp
<section class="section detail-intro container">
    <a class="back" href="{{ url('/'.$type) }}">‹ Back to Services</a>
    <div class="detail-grid">
        <img src="{{ asset('images/'.$photos[0]) }}" alt="{{ $title }}">
        <div>
            <h1>{{ $title }}</h1>
            <span class="pill">Treatment</span>
            <p>Discover a personalized approach to aesthetic treatment at Anare Clinic. Our team can guide you through the procedure and expected care.</p>
            <a class="button" href="{{ route('doctors') }}">Consult Our Doctors</a>
        </div>
    </div>
</section>
<section class="section muted">
    <div class="container center">
        <h2>Before vs After</h2>
        <p>Individual results may vary. Ask our team about treatment outcomes.</p>
        <div class="before-after">
            <img src="{{ asset('images/'.$photos[0]) }}" alt="{{ $title }} treatment example" loading="lazy">
            <img src="{{ asset('images/'.$photos[1]) }}" alt="{{ $title }} treatment example" loading="lazy">
        </div>
    </div>
</section>
<section class="section faq-section">
    <div class="container">
        <h2>Frequently Asked Questions</h2>
        @foreach(['What is this treatment?', 'How do I prepare for a consultation?', 'How can I book an appointment?'] as $question)
            <details><summary>{{ $question }}</summary><p>Our team will explain the treatment, suitability and care during your consultation.</p></details>
        @endforeach
    </div>
</section>
@endsection
