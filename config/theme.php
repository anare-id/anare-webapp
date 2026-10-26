<?php

declare(strict_types=1);

$fields = [
    'accent' => 'Warna aksen', 'pale' => 'Latar lembut', 'ink' => 'Warna teks', 'background' => 'Latar section',
    'catalog_background' => 'Latar halaman katalog', 'treatment_label' => 'Teks kategori / treatment',
    'treatment_secondary' => 'Teks sekunder', 'treatment_border' => 'Border treatment', 'treatment_card' => 'Latar kartu',
    'treatment_category' => 'Latar kategori', 'treatment_muted' => 'Teks deskripsi', 'treatment_soft' => 'Latar durasi',
    'treatment_comparison' => 'Latar before / after', 'treatment_comparison_label' => 'Label before / after',
    'treatment_panel' => 'Latar accordion', 'treatment_disabled' => 'Pagination nonaktif', 'treatment_footer' => 'Footer treatment',
];
$skin = ['#75a5c1', '#e7f0f5', '#303e49', '#f4f8fa', '#f1f8fb', '#385b72', '#557489', '#e0eaf0', '#edf4f8', '#eaf2f6', '#6b7780', '#f0f6f9', '#eef4f7', '#779fb8', '#f4f8fa', '#adbcc5', '#1f4054'];
$shape = ['#a9977f', '#eee9e3', '#59483a', '#f6f1eb', '#f8f4ef', '#59483a', '#806c56', '#ded5ca', '#eee9e3', '#f2ede6', '#827568', '#f3eee7', '#f6f1eb', '#a9977f', '#faf7f3', '#b5a99b', '#514336'];

return [
    'groups' => [
        'shared' => ['label' => 'Warna bersama', 'fields' => ['footer' => 'Latar footer', 'neutral_button' => 'Tombol artikel', 'neutral_button_hover' => 'Hover tombol artikel'], 'defaults' => ['footer' => '#242424', 'neutral_button' => '#737373', 'neutral_button_hover' => '#5f5f5f']],
        'home' => ['label' => 'Beranda', 'fields' => ['ink' => 'Warna teks', 'button' => 'Tombol layanan', 'button_hover' => 'Hover tombol layanan'], 'defaults' => ['ink' => '#303a42', 'button' => '#2e4050', 'button_hover' => '#496a81']],
        'skin' => ['label' => 'Anare Skin', 'fields' => $fields, 'defaults' => array_combine(array_keys($fields), $skin)],
        'shape' => ['label' => 'Anare Shape', 'fields' => $fields + ['header_background' => 'Latar header', 'header_text' => 'Teks header'], 'defaults' => array_combine(array_keys($fields), $shape) + ['header_background' => '#d3c6b4', 'header_text' => '#211e1a']],
    ],
];
