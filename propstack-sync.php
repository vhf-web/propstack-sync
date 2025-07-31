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
require_once __DIR__ . '/includes/ApartmentPermalinks.php';
use Propstack\Includes\Cron\CronHandler;


// Register ACF JSON loading
add_filter('acf/settings/load_json', function ($paths) {
    $paths[] = plugin_dir_path(__FILE__) . 'acf-json';
    return $paths;
});


// Boot plugin
add_action('plugins_loaded', function () {
    // CPTs
     if (class_exists(\Propstack\Includes\CPT\ProjectCPT::class)) {
        new \Propstack\Includes\CPT\ProjectCPT();
    }
    if (class_exists(\Propstack\Includes\CPT\ApartmentCPT::class)) {
        new \Propstack\Includes\CPT\ApartmentCPT();
    }

    if (class_exists(\Propstack\Includes\CPT\ParkingCPT::class)) {
        new \Propstack\Includes\CPT\ParkingCPT();
    }
    if (class_exists(\Propstack\Includes\Cron\CronHandler::class)) {
        new  Propstack\Includes\Cron\CronHandler;
     }
    // Admin interface
    if (class_exists(\Propstack\Includes\Admin\SettingsPage::class)) {
        new \Propstack\Includes\Admin\SettingsPage();
    }

    if (class_exists(\Propstack\Includes\Admin\SyncButton::class)) {
        new \Propstack\Includes\Admin\SyncButton();
    }
    if (class_exists(\Propstack\Includes\Admin\DashboardPage::class)) {
        new \Propstack\Includes\Admin\DashboardPage();
    }
        \Propstack\Includes\ApartmentPermalinks::init();
     
});

register_activation_hook(__FILE__, [CronHandler::class, 'activate']);
register_deactivation_hook(__FILE__, [CronHandler::class, 'deactivate']);
define('PROPSTACK_PLUGIN_URL', plugin_dir_url(__FILE__));

add_action('restrict_manage_posts', function () {
    global $typenow;

    if ($typenow !== 'apartment') {
        return;
    }

    $projects = get_posts([
        'post_type'      => 'project',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    $current = $_GET['related_project'] ?? '';

    echo '<select name="related_project">';
    echo '<option value="">Alle Projekte</option>';
    foreach ($projects as $project) {
        $selected = $current == $project->ID ? 'selected' : '';
        echo '<option value="' . esc_attr($project->ID) . '" ' . $selected . '>' . esc_html($project->post_title) . '</option>';
    }
    echo '</select>';
});

add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->get('post_type') === 'apartment' && isset($_GET['related_project']) && $_GET['related_project'] !== '') {
        $query->set('meta_query', [
            [
                'key'   => 'related_project',
                'value' => $_GET['related_project'],
            ]
        ]);
    }
});

