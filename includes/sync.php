<?php
if (!defined('ABSPATH')) exit;

/**
 * Syncs Propstack objects into apartment and parking_space posts
 */
function propstack_sync_properties() {
    $estates = propstack_get_api_data(); // defined in api-client.php
    error_log('💾 Starting sync, got ' . count($estates) . ' items');

    foreach ($estates as $item) {
        if (!is_array($item) || !isset($item['id'])) {
            error_log('⚠️ Skipping invalid item (missing id)');
            continue;
        }

        // Detect type based on rs_type or object_type
        $type = null;
        if (isset($item['rs_type']) && $item['rs_type'] === 'GARAGE') {
            $type = 'parking_space';
        } elseif (
            (isset($item['object_type']) && $item['object_type'] === 'LIVING') &&
            (!empty($item['living_space']) || $item['rs_type'] === 'APARTMENT')
        ) {
            $type = 'apartment';
        } else {
            error_log("⏭️ Skipping unsupported object_type or rs_type");
            continue;
        }

        error_log("📝 Processing type={$type} id={$item['id']}");
        propstack_upsert_post($item, $type);
    }
}

/**
 * Creates or updates a post based on propstack_id
 */
function propstack_upsert_post($item, $post_type) {
    $existing = get_posts([
        'post_type'   => $post_type,
        'meta_key'    => 'propstack_id',
        'meta_value'  => $item['id'],
        'post_status' => 'any',
        'numberposts' => 1,
    ]);

    $post_data = [
        'post_title'  => $item['title'] ?? $item['unit_id'] ?? 'No Title',
        'post_status' => 'publish',
        'post_type'   => $post_type,
    ];

    $post_id = $existing ? $existing[0]->ID : wp_insert_post($post_data);

    update_post_meta($post_id, 'propstack_id', $item['id']);
    update_post_meta($post_id, 'unit_id', $item['unit_id'] ?? '');
    update_post_meta($post_id, 'address', $item['address'] ?? '');
    update_post_meta($post_id, 'lat', $item['lat'] ?? '');
    update_post_meta($post_id, 'lng', $item['lng'] ?? '');
    update_post_meta($post_id, 'status_name', $item['property_status_id'] ?? '');
    update_post_meta($post_id, 'free_from', $item['translations'][0]['free_from'] ?? '');

    $price = $item['price'] ?? $item['base_rent'] ?? '';
    update_post_meta($post_id, 'price', $price);

    if ($post_type === 'apartment') {
        update_post_meta($post_id, 'living_space', $item['living_space'] ?? '');
    }

    if (!empty($item['images'][0]['url'])) {
        update_post_meta($post_id, 'image_url', $item['images'][0]['url']);
    }

    error_log("✅ Synced post ID {$post_id} for unit {$item['unit_id']}");
}
