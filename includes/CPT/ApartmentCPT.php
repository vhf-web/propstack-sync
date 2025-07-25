<?php
namespace Propstack\Includes\CPT;

if ( ! defined( 'ABSPATH' ) ) exit;

class ApartmentCPT
{
    public function __construct()
    {
        add_action('init', [$this, 'register_post_type']);
    }

    public function register_post_type()
    {
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

        error_log('[PropstackSync] ✅ ApartmentCPT registered');
    }
}
