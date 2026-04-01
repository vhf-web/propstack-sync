<?php
/**
 * Plugin Name: Propstack Sync
 * Description: Synchronizes real estate objects from Propstack into WordPress custom post types.
 * Version: 0.1.0
 * Author: Berlintina
 */

if (!defined('ABSPATH')) {
    exit;
}

// Autoload classes via Composer
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/ContentTypePermalinks.php';

use Propstack\Includes\CPT\ApartmentCPT;
use Propstack\Includes\CPT\ParkingCPT;
use Propstack\Includes\CPT\ProjectCPT;
use Propstack\Includes\Cron\CronHandler;
use Propstack\Includes\Admin\SyncButton;
use Propstack\Includes\Admin\DashboardPage;

// Plugin-URL-Konstante
define('PROPSTACK_PLUGIN_URL', plugin_dir_url(__FILE__));

// 🔧 ACF JSON laden
add_filter('acf/settings/load_json', function ($paths) {
    $paths[] = plugin_dir_path(__FILE__) . 'acf-json';
    return $paths;
});

// ✅ CPTs direkt initialisieren (nicht per init-Hook)
new ApartmentCPT();
new ProjectCPT();
// Wenn Parking später gebraucht wird, aktivieren:
// new ParkingCPT();
// 🧰 Weitere Komponenten nach Plugin-Load
add_action('init', function () {
    if (class_exists(CronHandler::class)) {
        new CronHandler();
    }

    if (class_exists(SyncButton::class)) {
        new SyncButton();
    }

    if (class_exists(DashboardPage::class)) {
        new DashboardPage();
    }

    \Propstack\Includes\ContentTypePermalinks::init();
});

// 🛠️ Cron-Hooks
register_activation_hook(__FILE__, [CronHandler::class, 'activate']);
register_deactivation_hook(__FILE__, [CronHandler::class, 'deactivate']);

// 🔍 Admin-Filter: Wohnungen nach Projekt filtern
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

// 🎯 Filter-Logik anwenden
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
