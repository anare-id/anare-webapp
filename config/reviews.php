<?php

return [
    'google' => [
        'place_id' => env('GOOGLE_PLACE_ID', 'ChIJfTCB-Mv71y0RBeDF30u7ey8'),
        'api_key' => env('GOOGLE_PLACES_API_KEY'),
        'place_name' => 'ANARE clinic',
        'reviews_url' => 'https://www.google.com/maps/place/ANARE+clinic/@-7.3234692,112.776346,17z/data=!4m8!3m7!1s0x2dd7fbcbf881307d:0x2f7bbb4bdfc5e005!8m2!3d-7.3234692!4d112.776346!9m1!1b1!16s%2Fg%2F11h_0nr9yw',
        'limit' => 2,
        'cache_seconds' => 21600,
        'snapshot_path' => resource_path('data/google-reviews.json'),
    ],
];
