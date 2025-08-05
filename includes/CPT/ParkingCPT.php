<?php
namespace Propstack\Includes\CPT;

if ( ! defined( 'ABSPATH' ) ) exit;

class ParkingCPT
{
    public function __construct()
    {
        add_action('init', [$this, 'register_post_type']);
    }

    public function register_post_type()
    {
        register_post_type('parking_space', [
            'labels' => [
                'name' => 'Parking Spaces',
                'singular_name' => 'Parking Space',
                'add_new_item' => 'Add New Parking Space',
            ],
            'rewrite' => [
            'slug' => 'stellplatz',
            'with_front' => false,
            ],
            'public' => true,
            'menu_icon' => 'dashicons-car',
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'has_archive' => true,
            'show_in_rest' => true,
        ]);

        error_log('[PropstackSync] ✅ ParkingCPT registered');
    }
}
