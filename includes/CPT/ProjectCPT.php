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
                'name' => 'Projects',
                'singular_name' => 'Project',
            ],
            'public' => true,
            'has_archive' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-building',
            'supports' => ['title', 'editor', 'thumbnail'],
            'show_in_rest' => true,
        ]);
    }
}
