<?php

namespace Propstack\Includes;

class ContentTypePermalinks
{
    // Mapping CPT => URL-Slug (sichtbare Slugs in der URL)
    protected static $slug_map = [
        'apartment' => 'wohnung',
        'parking'   => 'stellplatz',
        'gewerbe'   => 'gewerbe',
    ];

    public static function init()
    {
        add_filter('post_type_link', [self::class, 'custom_permalink'], 10, 2);
        add_action('init', [self::class, 'add_rewrite_rules']);
    }

    /**
     * Erzeugt immer Permalinks wie /wohnung/slug
     */
    public static function custom_permalink($post_link, $post)
    {
        $post_type = $post->post_type;

        if (!isset(self::$slug_map[$post_type])) {
            return $post_link;
        }

        $slug = $post->post_name;
        if (!$slug) {
            return $post_link;
        }

        $type_slug = self::$slug_map[$post_type];
        return home_url("/$type_slug/$slug");
    }

    /**
     * Registriert nur die Rewrite-Regel /wohnung/slug
     */
    public static function add_rewrite_rules()
    {
        foreach (self::$slug_map as $post_type => $type_slug) {
            add_rewrite_rule(
                '^' . $type_slug . '/([^/]+)/?$',
                'index.php?post_type=' . $post_type . '&name=$matches[1]',
                'top'
            );
        }
    }
}
