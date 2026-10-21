<?php

return [
    'instagram' => [
        'username' => 'anareclinic.id',
        'profile_url' => 'https://www.instagram.com/anareclinic.id/',
        'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
        'api_version' => env('INSTAGRAM_API_VERSION', 'v22.0'),
        'limit' => 6,
        'cache_seconds' => 900,
        'snapshot_path' => resource_path('data/instagram-feed.json'),
        'accounts' => [
            'clinic' => [
                'username' => 'anareclinic.id',
                'profile_url' => 'https://www.instagram.com/anareclinic.id/',
                'access_token' => env('INSTAGRAM_ANARECLINIC_ACCESS_TOKEN', env('INSTAGRAM_ACCESS_TOKEN')),
            ],
            'shape' => [
                'username' => 'anareshape.id',
                'profile_url' => 'https://www.instagram.com/anareshape.id/',
                'access_token' => env('INSTAGRAM_ANARESHAPE_ACCESS_TOKEN'),
            ],
        ],
    ],

    'profiles' => [
        ['label' => 'Anare Clinic', 'handle' => 'anareclinic.id', 'url' => 'https://www.instagram.com/anareclinic.id/'],
        ['label' => 'Anare Shape', 'handle' => 'anareshape.id', 'url' => 'https://www.instagram.com/anareshape.id/'],
    ],

    'whatsapp' => [
        'number' => '+628113210288',
        'url' => 'https://wa.me/628113210288',
    ],
    'whatsapp' => [
        'number' => '+628113210288',
        'url' => 'https://wa.me/628113210288',
    ],
];
