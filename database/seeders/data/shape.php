<?php

return [
    'name' => 'Anare Shape',
    'hero_title' => 'Endolipo, Slimming, Tightening',
    'hero_description' => 'Lorem ipsum dolor sit amet, cupidatat mollit aute excepteur pariatur. Aliquip officia enim in velit fugiat dolore ut in irure cupidatat velit. Proident deserunt sunt dolore aliquip nostrud',
    'default_category' => 'endolipo',
    'category_aliases' => ['all' => 'endolipo'],
    'per_page' => 6,
    'pagination_slots' => 5,
    'show_pagination' => false,
    'categories' => [
        'endolipo' => 'Endolipo',
        'slimming' => 'Slimming',
        'tightening' => 'Tightening',
    ],

    // The six Endolipo areas follow the supplied reference. Existing Slimming
    // and Tightening services remain available through their own categories.
    'treatments' => [
        'double-chin' => ['name' => 'Double Chin', 'category' => 'endolipo', 'image' => 'treatment4.JPG', 'image_position' => 'center 65%'],
        'lower-cheek' => ['name' => 'Lower Cheek', 'category' => 'endolipo', 'image' => 'treatment3.JPG', 'image_position' => 'center 60%'],
        'arms' => ['name' => 'Arms', 'category' => 'endolipo', 'image' => 'treatment5.JPG', 'image_position' => 'center 65%'],
        'back' => ['name' => 'Back', 'category' => 'endolipo', 'image' => 'treatment4.JPG', 'image_position' => 'center 65%'],
        'belly' => ['name' => 'Belly', 'category' => 'endolipo', 'image' => 'treatment3.JPG', 'image_position' => 'center 60%'],
        'waist' => ['name' => 'Waist', 'category' => 'endolipo', 'image' => 'treatment5.JPG', 'image_position' => 'center 65%'],
        'body-slimming' => [
            'name' => 'Body Slimming', 'category' => 'slimming',
            'image' => 'treatment1.JPG', 'image_position' => 'center 65%',
        ],
        'skin-tightening' => [
            'name' => 'Skin Tightening', 'category' => 'tightening',
            'image' => 'treatment5.JPG', 'image_position' => 'center 65%',
        ],
    ],

    'legacy_treatments' => [
        'endolipo' => ['name' => 'Endolipo', 'category' => 'endolipo', 'image' => 'treatment2.JPG', 'image_position' => 'center 65%'],
    ],

    'comparison' => [
        'before' => 'ANARE9355 copy 2.jpg',
        'after' => 'ANARE9391 copy 2.jpg',
        'image_position' => 'center 55%',
        'illustrative' => true,
    ],
    'conditions' => [
        'Pre-condition' => 'Informasi persiapan sebelum treatment akan disampaikan oleh tim Anare Shape saat konsultasi.',
        'Post-condition' => 'Informasi perawatan setelah treatment akan disampaikan oleh tim Anare Shape saat konsultasi.',
        'Downtime' => 'Informasi downtime akan disampaikan oleh tim Anare Shape sesuai evaluasi saat konsultasi.',
    ],
];
