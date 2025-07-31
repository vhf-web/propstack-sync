<?php


namespace Propstack\Includes\Admin;

use Propstack\Includes\SyncService;
use Propstack\Includes\PostHandler\ProjectHandler;

class DashboardPage
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_propstack_sync_project_ajax', [$this, 'ajax_sync_project']);
        add_action('wp_ajax_propstack_sync_project_meta', [$this, 'ajax_sync_project_meta']);
        add_action('admin_post_propstack_sync_project', [$this, 'handle_project_sync']);
    }

    public function add_menu_page()
    {
        // 🔧 Nur Menüeintrag registrieren – keine get_posts() hier!
        add_menu_page(
            'Propstack Übersicht',
            'Propstack',
            'manage_options',
            'propstack-dashboard',
            [$this, 'render_page'],
            'dashicons-update',
            20
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

    public function render_page()
    {
        // ✅ Sicherstellen, dass CPT registriert ist
        if (!post_type_exists('project')) {
            echo '<div class="notice notice-error"><p><strong>Fehler:</strong> Der Post Type <code>project</code> ist nicht registriert.</p></div>';
            return;
        }

        // 📦 Projekte laden
        $projects = get_posts([
            'post_type'      => 'project',
            'post_status'    => 'publish',
            'numberposts'    => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
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
                    $propstack_id = get_field('propstack_id', $project->ID);
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
                                🏢 Wohnungen synchronisieren
                            </button>
                            <span class="sync-status sync-status-apartment" style="margin-left:10px;"></span>

                            <button class="button propstack-sync-meta-btn"
                                    data-project="<?php echo esc_attr($propstack_id); ?>"
                                    data-post-id="<?php echo esc_attr($project->ID); ?>">
                                🏗️ Projekt-Stammdaten synchronisieren
                            </button>
                            <span class="sync-status sync-status-project" style="margin-left:10px;"></span>
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
            'count'   => $result['created'] + $result['updated'],
            'total'   => count($result['objects']),
        ]);
    }

    public function ajax_sync_project_meta()
    {
        check_ajax_referer('propstack_sync_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Keine Berechtigung');
        }

        $project_id = sanitize_text_field($_POST['project_id']);

        $result = ProjectHandler::syncProjects([$project_id]);

        wp_send_json_success([
            'message' => sprintf('Projekt-Sync abgeschlossen: %d erstellt, %d aktualisiert', $result['created'], $result['updated']),
        ]);
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
            'page'      => 'propstack-dashboard',
            'sync_done' => 1,
            'created'   => $result['created'],
            'updated'   => $result['updated'],
        ], admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit;
    }
}
