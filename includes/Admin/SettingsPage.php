<?php

namespace Propstack\Includes\Admin;

use Propstack\Includes\SyncService;

class SettingsPage
{
    const OPTION_API_URL     = 'propstack_api_url';
    const OPTION_PROJECT_ID  = 'propstack_project_id';
    const OPTION_API_TOKEN   = 'propstack_api_token';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'maybe_run_sync']);
    }

    public function add_menu_page()
    {
        add_options_page(
            'Propstack Sync',
            'Propstack Sync',
            'manage_options',
            'propstack-sync',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings()
    {
        register_setting('propstack_sync_options', self::OPTION_API_URL);
        register_setting('propstack_sync_options', self::OPTION_PROJECT_ID);
        register_setting('propstack_sync_options', self::OPTION_API_TOKEN);
    }

    public function render_settings_page()
    {
        ?>
        <div class="wrap">
            <h1>Propstack Sync Einstellungen</h1>
            <form method="post" action="options.php">
                <?php settings_fields('propstack_sync_options'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="propstack_api_url">API URL</label></th>
                        <td>
                            <input type="url"
                                   name="<?php echo esc_attr(self::OPTION_API_URL); ?>"
                                   id="propstack_api_url"
                                   value="<?php echo esc_attr(self::get_api_url()); ?>"
                                   class="regular-text"
                                   placeholder="https://api.propstack.de/v2/properties">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="propstack_project_id">Projekt ID</label></th>
                        <td>
                            <input type="text"
                                   name="<?php echo esc_attr(self::OPTION_PROJECT_ID); ?>"
                                   id="propstack_project_id"
                                   value="<?php echo esc_attr(self::get_project_id()); ?>"
                                   class="regular-text"
                                   placeholder="z. B. 404126">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="propstack_api_token">API Token (optional)</label></th>
                        <td>
                            <input type="text"
                                   name="<?php echo esc_attr(self::OPTION_API_TOKEN); ?>"
                                   id="propstack_api_token"
                                   value="<?php echo esc_attr(self::get_api_token()); ?>"
                                   class="regular-text"
                                   placeholder="Bearer-Token, wenn benötigt">
                            <p class="description">Du kannst den Token auch sicher in der <code>wp-config.php</code> setzen mit <code>define('PROPSTACK_API_TOKEN', '...');</code>.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Speichern'); ?>
            </form>

            <hr>

            <form method="post" action="">
                <input type="hidden" name="propstack_manual_sync" value="1">
                <?php submit_button('Jetzt synchronisieren', 'primary', 'submit', false); ?>
            </form>
        </div>
        <?php
    }

    public static function get_api_url(): ?string
    {
        return get_option(self::OPTION_API_URL);
    }

    public static function get_project_id(): ?string
    {
        return get_option(self::OPTION_PROJECT_ID);
    }

    public static function get_api_token(): ?string
    {
        if (defined('PROPSTACK_API_TOKEN')) {
            return PROPSTACK_API_TOKEN;
        }

        return get_option(self::OPTION_API_TOKEN);
    }

    public function maybe_run_sync()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!empty($_POST['propstack_manual_sync'])) {
            $projekt_id = self::get_project_id();
            $projekt_post_id = 0; // kein echter Post, reiner API-Test

            if ($projekt_id) {
                $service = new SyncService();
                $result = $service->sync_project($projekt_id, $projekt_post_id);

                $message = sprintf(
                    '✔ %d neue Apartments erstellt, %d aktualisiert.',
                    $result['created'],
                    $result['updated']
                );

                add_action('admin_notices', function () use ($message) {
                    echo '<div class="notice notice-success is-dismissible"><p>' .
                        esc_html($message) .
                        '</p></div>';
                });
            } else {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible"><p>' .
                        esc_html__('❌ Kein Projekt ID angegeben.', 'propstack') .
                        '</p></div>';
                });
            }
        }
    }
}
