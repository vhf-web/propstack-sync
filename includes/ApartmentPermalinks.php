<?php 
namespace Propstack\Includes;

class ApartmentPermalinks
{
    public static function init()
    {
        add_filter('post_type_link', [self::class, 'custom_apartment_permalink'], 10, 2);
        add_action('init', [self::class, 'add_rewrite_rules']);
    }

    public static function custom_apartment_permalink($post_link, $post)
    {
        if ($post->post_type !== 'apartment') {
            return $post_link;
        }

        $slug = $post->post_name;
        $project = get_field('related_project', $post->ID);

        if (!$slug) {
            return $post_link;
        }

        if (is_array($project)) {
            $project = $project[0] ?? null;
        }

        $project_slug = $project ? get_post_field('post_name', $project) : null;

        $total_projects = count(get_posts([
            'post_type' => 'project',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ]));

        if ($total_projects > 1 && $project_slug) {
            return home_url("/$project_slug/$slug");
        }

        return home_url("/$slug");
    }

    public static function add_rewrite_rules()
    {
        // Standard: /{project-slug}/{apartment-slug}
        add_rewrite_rule(
            '^([^/]+)/([^/]+)/?$',
            'index.php?post_type=apartment&name=$matches[2]',
            'top'
        );

        // Speziell: /pmp/{apartment-slug}
        add_rewrite_rule(
            '^pmp/([^/]+)/?$',
            'index.php?post_type=apartment&name=$matches[1]',
            'top'
        );
    }
}
