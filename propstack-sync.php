<?php
/**
 * Plugin Name: Propstack Sync
 * Description: Synchronizes real estate objects from Propstack into WordPress custom post types.
 * Version: 0.1.0
 * Author: Berlintina
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Autoload classes via Composer
require_once __DIR__ . '/vendor/autoload.php';

// Register ACF JSON loading
add_filter('acf/settings/load_json', function ($paths) {
    $paths[] = plugin_dir_path(__FILE__) . 'acf-json';
    return $paths;
});

// In plugin oder Theme functions.php (besser im Plugin)
function register_projekt_post_type() {
    register_post_type('projekt', [
        'labels' => [
            'name' => 'Projekte',
            'singular_name' => 'Projekt',
        ],
        'public' => true,
        'has_archive' => false,
        'menu_position' => 20,
        'menu_icon' => 'dashicons-building',
        'supports' => ['title', 'editor', 'thumbnail'],
        'show_in_rest' => true,
    ]);
}
add_action('init', 'register_projekt_post_type');

// Boot plugin
add_action('plugins_loaded', function () {
    // CPTs
    if (class_exists(\Propstack\Includes\CPT\ApartmentCPT::class)) {
        new \Propstack\Includes\CPT\ApartmentCPT();
    }

    if (class_exists(\Propstack\Includes\CPT\ParkingCPT::class)) {
        new \Propstack\Includes\CPT\ParkingCPT();
    }

    // Admin interface
    if (class_exists(\Propstack\Includes\Admin\SettingsPage::class)) {
        new \Propstack\Includes\Admin\SettingsPage();
    }

    if (class_exists(\Propstack\Includes\Admin\SyncButton::class)) {
        new \Propstack\Includes\Admin\SyncButton();
    }
});
