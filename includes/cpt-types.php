<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Apartment CPT
add_action('init', function() {
    register_post_type('apartment', [
        'labels' => [
            'name' => 'Apartments',
            'singular_name' => 'Apartment',
            'add_new_item' => 'Add New Apartment',
        ],
        'public' => true,
        'menu_icon' => 'dashicons-building',
        'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'has_archive' => true,
        'show_in_rest' => true,
    ]);
});

// Parking Space CPT
add_action('init', function() {
    register_post_type('parking_space', [
        'labels' => [
            'name' => 'Parking Spaces',
            'singular_name' => 'Parking Space',
            'add_new_item' => 'Add New Parking Space',
        ],
        'public' => true,
        'menu_icon' => 'dashicons-car',
        'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'has_archive' => true,
        'show_in_rest' => true,
    ]);
});
