<?php

declare(strict_types=1);

$fields = [
    'accent' => 'Warna aksen', 'pale' => 'Latar lembut', 'ink' => 'Warna teks', 'background' => 'Latar section',
    'catalog_background' => 'Latar halaman katalog', 'treatment_label' => 'Teks kategori / treatment',
    'treatment_secondary' => 'Teks sekunder', 'treatment_border' => 'Border treatment', 'treatment_card' => 'Latar kartu',
    'treatment_category' => 'Latar kategori', 'treatment_muted' => 'Teks deskripsi', 'treatment_soft' => 'Latar durasi',
    'treatment_comparison' => 'Latar before / after', 'treatment_comparison_label' => 'Label before / after',
    'treatment_panel' => 'Latar accordion', 'treatment_disabled' => 'Pagination nonaktif', 'treatment_footer' => 'Footer treatment',
    'footer_text' => 'Teks footer',
];
$skin = ['#75a5c1', '#e7f0f5', '#303e49', '#f4f8fa', '#f1f8fb', '#385b72', '#557489', '#e0eaf0', '#edf4f8', '#eaf2f6', '#6b7780', '#f0f6f9', '#eef4f7', '#779fb8', '#f4f8fa', '#adbcc5', '#1f4054', '#ffffff'];
$shape = ['#a9977f', '#eee9e3', '#59483a', '#f6f1eb', '#f8f4ef', '#59483a', '#806c56', '#ded5ca', '#eee9e3', '#f2ede6', '#827568', '#f3eee7', '#f6f1eb', '#a9977f', '#faf7f3', '#b5a99b', '#9b897f', '#ffffff'];

return [
    'groups' => [
        'shared' => ['label' => 'Footer Anare & elemen bersama', 'section' => 'anare', 'fields' => ['footer' => 'Latar footer Anare', 'footer_text' => 'Teks footer Anare', 'neutral_button' => 'Tombol artikel / bahasa', 'neutral_button_hover' => 'Hover tombol artikel'], 'defaults' => ['footer' => '#898989', 'footer_text' => '#ffffff', 'neutral_button' => '#737373', 'neutral_button_hover' => '#5f5f5f']],
        'home' => ['label' => 'Beranda, Dokter & Artikel', 'section' => 'anare', 'fields' => ['ink' => 'Warna teks', 'button' => 'Tombol layanan', 'button_hover' => 'Hover tombol layanan', 'accent' => 'Warna aksen', 'pale' => 'Latar lembut', 'background' => 'Latar section', 'canvas' => 'Latar halaman'], 'defaults' => ['ink' => '#303a42', 'button' => '#2e4050', 'button_hover' => '#496a81', 'accent' => '#75a5c1', 'pale' => '#e7f0f5', 'background' => '#f4f8fa', 'canvas' => '#ffffff']],
        'skin' => ['label' => 'Anare Skin', 'fields' => $fields, 'defaults' => array_combine(array_keys($fields), $skin)],
        'shape' => ['label' => 'Anare Shape', 'fields' => $fields + ['header_background' => 'Latar header', 'header_text' => 'Teks header'], 'defaults' => array_combine(array_keys($fields), $shape) + ['header_background' => '#d3c6b4', 'header_text' => '#211e1a']],
    ],
    'sections' => [
        'anare' => ['label' => 'Anare', 'description' => 'Beranda, Dokter, Artikel dan profil/detailnya.'],
        'skin' => ['label' => 'Anare Skin', 'description' => 'Katalog dan halaman detail treatment Skin.'],
        'shape' => ['label' => 'Anare Shape', 'description' => 'Katalog dan halaman detail treatment Shape.'],
    ],
    'presets' => [
        'sage' => ['label' => 'Sage', 'accent' => '#7e9b87', 'ink' => '#344a3e', 'pale' => '#ecf2ec', 'background' => '#f5f7f2', 'footer' => '#718576', 'button' => '#526c59', 'button_hover' => '#3f5946', 'secondary' => '#6f8173', 'border' => '#dbe5da', 'disabled' => '#acb9aa'],
        'rose' => ['label' => 'Rose', 'accent' => '#be9597', 'ink' => '#63484c', 'pale' => '#f5e8e9', 'background' => '#fbf5f4', 'footer' => '#a38186', 'button' => '#947079', 'button_hover' => '#76535d', 'secondary' => '#947a80', 'border' => '#ead9dd', 'disabled' => '#c5b0b4'],
        'ocean' => ['label' => 'Ocean', 'accent' => '#6d9fb8', 'ink' => '#25495c', 'pale' => '#e7f1f7', 'background' => '#f5f9fc', 'footer' => '#597e94', 'button' => '#426c86', 'button_hover' => '#31546b', 'secondary' => '#638195', 'border' => '#d5e4ee', 'disabled' => '#a4bbc9'],
    ],
];
