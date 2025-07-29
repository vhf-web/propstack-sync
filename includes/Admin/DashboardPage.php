<?php

namespace Propstack\Includes\Admin;

use Propstack\Includes\SyncService;

class DashboardPage
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_propstack_sync_project_ajax', [$this, 'ajax_sync_project']);
        add_action('admin_post_propstack_sync_project', [$this, 'handle_project_sync']);
    }

    public function add_menu_page()
    {
        add_menu_page(
            'Propstack Übersicht',
            'Propstack',
            'manage_options',
            'propstack-dashboard',
            [$this, 'render_page'],
            'dashicons-update',
            20
        );

        add_submenu_page(
            'propstack-dashboard',
            'Einstellungen',
            'Einstellungen',
            'manage_options',
            'propstack-settings',
            [\Propstack\Includes\Admin\SettingsPage::class, 'render_settings_page_static']
        );
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script(
            'propstack-sync-js',
            PROPSTACK_PLUGIN_URL . 'assets/propstack-sync.js',
            ['jquery'],
            '1.0',
            true
        );

        wp_localize_script('propstack-sync-js', 'PropstackSync', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('propstack_sync_nonce'),
        ]);
    }

    public function ajax_sync_project()
    {
        check_ajax_referer('propstack_sync_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Keine Berechtigung');
        }

        $project_id = sanitize_text_field($_POST['project_id']);
        $post_id    = (int) $_POST['post_id'];

        $service = new SyncService();
        $result  = $service->sync_project($project_id, $post_id);

        wp_send_json_success([
            'message' => sprintf('%d neu, %d aktualisiert', $result['created'], $result['updated']),
            'count' => $result['created'] + $result['updated'],
            'total' => count($result['objects']),
        ]);
    }

    public function render_page()
    {
        $projects = get_posts([
            'post_type' => 'project',
            'post_status' => 'publish',
            'numberposts' => -1,
        ]);
        ?>
        <div class="wrap">
            <h1>Propstack Sync Dashboard</h1>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Projekt</th>
                        <th>Propstack-ID</th>
                        <th>Letzter Sync</th>
                        <th>Aktion</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($projects as $project):
                    $propstack_id = get_field('project_id', $project->ID);
                    $last_sync    = get_field('letzter_sync', $project->ID);
                    ?>
                    <tr>
                        <td><?php echo esc_html($project->post_title); ?></td>
                        <td><?php echo esc_html($propstack_id ?: '❌'); ?></td>
                        <td><?php echo esc_html($last_sync ?: '–'); ?></td>
                        <td>
                            <?php if ($propstack_id): ?>
                                <button class="button propstack-sync-btn"
                                        data-project="<?php echo esc_attr($propstack_id); ?>"
                                        data-post-id="<?php echo esc_attr($project->ID); ?>">
                                    Jetzt synchronisieren
                                </button>
                                <span class="sync-status" style="margin-left:10px;"></span>
                            <?php else: ?>
                                <em>Keine ID</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_project_sync()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung');
        }

        $project_id = sanitize_text_field($_POST['project_id']);
        $post_id    = (int) $_POST['post_id'];

        $service = new SyncService();
        $result  = $service->sync_project($project_id, $post_id);

        $redirect_url = add_query_arg([
            'page'     => 'propstack-dashboard',
            'sync_done' => 1,
            'created'   => $result['created'],
            'updated'   => $result['updated'],
        ], admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit;
    }
}
