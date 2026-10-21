<?php

return [
    'name' => 'Anare Skin',
    'hero_title' => 'Treatment Reguler',
    'hero_description' => 'Lorem ipsum dolor sit amet, cupidatat mollit aute excepteur pariatur. Aliquip officia enim in velit fugiat dolore ut in irure cupidatat velit. Proident deserunt sunt dolore aliquip nostrud',
    'default_category' => 'acne',
    'aliases' => ['peeling' => 'acne-peel', 'skin-rejuvenation' => 'photoglow-laser'],
    'per_page' => 6,
    'pagination_slots' => 5,
    'show_pagination' => true,

    'categories' => [
        'acne' => 'Acne',
        'scar' => 'Scar',
        'hyperpigmentation' => 'Hyperpigmentation',
        'dull-skin' => 'Dull Skin',
        'dry-skin' => 'Dry Skin',
        'aging' => 'Aging',
        'large-pores' => 'Large Pores',
        'hair-treatment' => 'Hair Treatment',
    ],

    // The six supplied treatments belong to the Acne listing.
    // Additional category entries can be added here without changing the views.
    'treatments' => [
        'acne-injection' => [
            'name' => 'Acne Injection',
            'category' => 'acne',
            'image' => 'treatment3.JPG',
            'catalog_image' => 'treatment4.JPG',
            'image_position' => 'center 60%',
            'duration' => '30 Minutes',
            'description' => 'Injeksi yang dilakukan secara targeted pada jerawat tertentu untuk membantu mengurangi inflamasi dan mempercepat proses recovery.',
        ],
        'acne-toning-laser' => [
            'name' => 'Acne Toning Laser',
            'category' => 'acne',
            'image' => 'IMG_0646.JPG',
            'catalog_image' => 'treatment3.JPG',
            'image_position' => 'center 65%',
        ],
        'acne-peel' => [
            'name' => 'Acne Peel',
            'category' => 'acne',
            'image' => 'IMG_0597.JPG',
            'catalog_image' => 'treatment5.JPG',
            'image_position' => 'center 55%',
        ],
        'photoglow-laser' => [
            'name' => 'Photoglow Laser',
            'category' => 'acne',
            'image' => 'treatment5.JPG',
            'catalog_image' => 'treatment4.JPG',
            'image_position' => 'center 65%',
        ],
        'sebum-reducer-injection' => [
            'name' => 'Sebum Reducer Injection',
            'category' => 'acne',
            'image' => 'IMG_0672.JPG',
            'catalog_image' => 'treatment3.JPG',
            'image_position' => 'center 65%',
        ],
        'facial-treatment' => [
            'name' => 'Facial Treatment',
            'subtitle' => '(Anare Care)',
            'category' => 'acne',
            'image' => 'article.JPG',
            'catalog_image' => 'treatment5.JPG',
            'image_position' => 'center 65%',
        ],
    ],

    // These portraits are design illustrations, not verified treatment results.
    // Replace the pair per treatment when its actual before/after assets exist.
    'comparison' => [
        'before' => 'IMG_0560.JPG',
        'after' => 'IMG_0561.JPG',
        'illustrative' => true,
    ],

    'conditions' => [
        'Pre-condition' => 'Informasi persiapan sebelum treatment akan disampaikan oleh tim Anare Clinic saat konsultasi.',
        'Post-condition' => 'Informasi perawatan setelah treatment akan disampaikan oleh tim Anare Clinic saat konsultasi.',
        'Downtime' => 'Informasi downtime akan disampaikan oleh tim Anare Clinic sesuai evaluasi kondisi kulit saat konsultasi.',
    ],
];
