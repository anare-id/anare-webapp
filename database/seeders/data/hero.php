<?php

return [
    'interval' => 5000,
    // Use 'glass' to restore the previous translucent, blurred control pill.
    'controls_style' => 'plain',

    // Three local, replaceable demo photos for each existing hero.
    'slides' => [
        'home' => [
            ['image' => 'hero.jpg', 'alt' => 'Anare Clinic beauty and skincare', 'position' => 'center 50%'],
            ['image' => 'IMG_0646.JPG', 'alt' => 'Aesthetic treatment at Anare Clinic', 'position' => 'center 65%'],
            ['image' => 'section.jpg', 'alt' => 'Skin confidence and personal care', 'position' => 'center 45%'],
        ],
        'skin' => [
            ['image' => '045A8353a.jpg', 'alt' => 'Anare Skin aesthetic care', 'position' => 'center 38%'],
            ['image' => 'anare-skin.png', 'alt' => 'Anare Skin personalized treatment', 'position' => 'center', 'mobile_position' => '70% center'],
            ['image' => 'treatment5.JPG', 'alt' => 'Skin treatment at Anare Clinic', 'position' => 'center 65%'],
        ],
        'shape' => [
            ['image' => 'ANARE9410 copy 2.jpg', 'alt' => 'Anare Shape face and body care', 'position' => 'center 25%'],
            ['image' => 'anare-shape.png', 'alt' => 'Anare Shape beauty and confidence', 'position' => 'center', 'mobile_position' => '75% center'],
            ['image' => 'section.jpg', 'alt' => 'Personalized body and aesthetic care', 'position' => 'center'],
        ],
        'doctors' => [
            ['image' => 'doctors-team-hero.webp', 'mobile_image' => 'doctors-team-hero-mobile.webp', 'team' => true, 'alt' => 'Enam dokter Anare Clinic', 'position' => 'center bottom', 'fit' => 'contain', 'background' => '#91948e'],
            ['image' => 'dokter4.png', 'alt' => 'The Anare Clinic medical team', 'position' => 'center 10%'],
            ['image' => 'dokter6.png', 'alt' => 'Personal care from the Anare Clinic team', 'position' => 'center 10%'],
        ],
    ],
];
