<?php
namespace Propstack\Includes\CPT;

if (!defined('ABSPATH')) exit;

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
                'name'               => 'Wohnungen',
                'singular_name'      => 'Wohnungseinheit',
                'add_new'            => 'Wohnungseinheit hinzufügen',
                'add_new_item'       => 'Neue Wohnungseinheit hinzufügen',
                'edit_item'          => 'Wohnungseinheit bearbeiten',
                'new_item'           => 'Neue Wohnungseinheit',
                'view_item'          => 'Wohnungseinheit ansehen',
                'search_items'       => 'Wohnungseinheiten durchsuchen',
                'not_found'          => 'Keine Wohnungseinheiten gefunden',
                'not_found_in_trash' => 'Keine Wohnungseinheiten im Papierkorb',
                'all_items'          => 'Alle Wohnungseinheiten',
                'menu_name'          => 'Wohnungseinheiten',
                'name_admin_bar'     => 'Wohnungseinheit',
            ],
            'public'       => true,
            'menu_icon'    => 'dashicons-building',
            'supports'     => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'has_archive'  => true,
            'show_in_rest' => true,
        ]);

        error_log('[PropstackSync] ✅ CPT "Wohnungseinheiten" (slug: apartment) registered');
    }
}
