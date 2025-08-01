<?php 
namespace Propstack\Includes;

class ContentTypePermalinks
{
    public static function init()
    {
        add_filter('post_type_link', [self::class, 'custom_permalink'], 10, 2);
        add_action('init', [self::class, 'add_rewrite_rules']);
    }

    public static function custom_permalink($post_link, $post)
    {
        
        $post_type = $post->post_type;
        if (!in_array($post_type, ['apartment', 'parking', 'gewerbe'])) {  
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

        
        if (!post_type_exists('project')) {
            return home_url("/$slug");
        }

        
        $total_projects = count(get_posts([
            'post_type'   => 'project',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields'      => 'ids'
        ]));

        
        if ($total_projects > 1 && $project_slug) {
            return home_url("/$project_slug/$post_type/$slug");
        }

       
        return home_url("/$post_type/$slug");
    }

    public static function add_rewrite_rules()
    {
        
        $post_types = get_post_types(['_builtin' => false]);

        foreach ($post_types as $post_type) {
            
            add_rewrite_rule(
                "^([^/]+)/$post_type/([^/]+)/?$",
                'index.php?post_type=' . $post_type . '&name=$matches[2]',
                'top'
            );
        }

        
        add_rewrite_rule(
            '^([^/]+)/([^/]+)/?$',
            'index.php?post_type=$matches[1]&name=$matches[2]',
            'top'
        );
    }
}
