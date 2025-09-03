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
                'singular_name'      => 'Wohnung',
                'add_new'            => 'Wohnung hinzufügen',
                'add_new_item'       => 'Neue Wohnung hinzufügen',
                'edit_item'          => 'Wohnung bearbeiten',
                'new_item'           => 'Neue Wohnung',
                'view_item'          => 'Wohnung ansehen',
                'search_items'       => 'Wohnungen durchsuchen',
                'not_found'          => 'Keine Wohnungen gefunden',
                'not_found_in_trash' => 'Keine Wohnungen im Papierkorb',
                'all_items'          => 'Alle Wohnungen',
                'menu_name'          => 'Wohnungen',
                'name_admin_bar'     => 'Wohnung',
            ],
            'rewrite' => [
            'slug' => 'wohnung',
            'with_front' => false,
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


