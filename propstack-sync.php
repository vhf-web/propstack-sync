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
