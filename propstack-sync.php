<?php
/**
 * Plugin Name: Propstack Sync
 * Description: Synchronizes real estate objects from Propstack into WordPress custom post types.
 * Version: 0.1.0
 * Author: Berlintina
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


// Include files
require_once plugin_dir_path( __FILE__ ) . 'includes/cpt-types.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/api-client.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-sync-button.php';
require_once plugin_dir_path(__FILE__) . 'includes/sync.php';


add_filter('acf/settings/load_json', function ($paths) {
    $paths[] = plugin_dir_path(__FILE__) . 'acf-json';
    return $paths;
});


