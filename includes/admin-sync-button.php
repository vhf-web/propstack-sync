<?php
if (!defined('ABSPATH')) exit;

/**
 * Adds a submenu under Tools for manual syncing
 */
add_action('admin_menu', function () {
    add_management_page(
        'Propstack Sync',
        'Propstack Sync',
        'manage_options',
        'propstack-sync',
        'propstack_sync_admin_page'
    );
});

function propstack_sync_admin_page() {
    if (isset($_POST['propstack_manual_sync'])) {
        propstack_sync_properties();
        echo '<div class="updated"><p>Synced successfully!</p></div>';
    }

    echo '<div class="wrap">';
    echo '<h1>Propstack Sync</h1>';
    echo '<form method="POST">';
    echo '<input type="submit" name="propstack_manual_sync" class="button-primary" value="Sync Now">';
    echo '</form>';
    echo '</div>';
}