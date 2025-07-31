<?php


namespace Propstack\Includes\CPT;

class ProjectCPT
{
    public function __construct()
    {
        add_action('init', [$this, 'register']);
    }

    public function register()
    {
        register_post_type('project', [
            'labels' => [
                'name'               => 'Projekte',
                'singular_name'      => 'Projekt',
                'add_new'            => 'Projekt hinzufügen',
                'add_new_item'       => 'Neues Projekt hinzufügen',
                'edit_item'          => 'Projekt bearbeiten',
                'new_item'           => 'Neues Projekt',
                'view_item'          => 'Projekt ansehen',
                'search_items'       => 'Projekte durchsuchen',
                'not_found'          => 'Kein Projekt gefunden',
                'not_found_in_trash' => 'Kein Projekt im Papierkorb',
                'all_items'          => 'Alle Projekte',
                'menu_name'          => 'Projekte',
                'name_admin_bar'     => 'Projekt',
            ],
            'public'        => true,
            'show_ui'       => true,
            'show_in_menu'  => true,
            'menu_icon'     => 'dashicons-building',
            'supports'      => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'has_archive'   => true,
            'show_in_rest'  => true,
            'rewrite'       => ['slug' => 'project'],
        ]);

        error_log('[PropstackSync] ✅ CPT "Projekt" (slug: project) registered');
    }
}
