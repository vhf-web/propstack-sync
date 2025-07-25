<?php

namespace Propstack\Includes\PostHandler;

class ApartmentHandler
{
public static function create_or_update(array $item, int $projekt_post_id): string
{
    $title = $item['name'] ?? $item['unit_id'] ?? 'Unbenannt';
    $existing = get_page_by_title($title, OBJECT, 'we');
    $post_id = $existing ? $existing->ID : null;

    $post_data = [
        'post_title'   => $title,
        'post_type'    => 'we',
        'post_status'  => 'publish',
        'post_content' => $item['long_description_note'] ?? '',
    ];

    if ($post_id) {
        $post_data['ID'] = $post_id;
        wp_update_post($post_data);
        $action = 'updated';
    } else {
        $post_id = wp_insert_post($post_data);
        $action = 'created';
    }

    // ACF-Felder speichern
    if (function_exists('update_field')) {
        update_field('wohnflaeche', $item['living_space'], $post_id);
        update_field('preis', $item['price'], $post_id);
        update_field('zimmeranzahl', $item['number_of_rooms'], $post_id);
        update_field('zugehoeriges_projekt', $projekt_post_id, $post_id);
    }

    // Dokumente als Media-Dateien anhängen
    if (!empty($item['documents'])) {
        foreach ($item['documents'] as $doc) {
            $url = $doc['doc']['url'] ?? null;
            $title = $doc['title'] ?? null;
            if ($url && $title) {
                self::attach_document($url, $title, $post_id);
            }
        }
    }

    return $action;
}

protected static function attach_document($url, $title, $post_id)
{
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Lade PDF temporär herunter
    $tmp = download_url($url);
    if (is_wp_error($tmp)) {
        return;
    }

    $file_array = [
        'name'     => sanitize_file_name($title),
        'tmp_name' => $tmp,
    ];

    // Füge Datei als Attachment hinzu
    $attachment_id = media_handle_sideload($file_array, $post_id);

    if (!is_wp_error($attachment_id)) {
        // Optional: Setze als ACF-Galerie oder einfach verlinken
        // update_field('dokumente', [...], $post_id);
        add_post_meta($post_id, '_propstack_document_ids', $attachment_id);
    }
}

    private static function find_by_unit_id(string $unitId): ?int
    {
        $query = new \WP_Query([
            'post_type' => 'apartment',
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => 'unit_id',
                    'value' => $unitId,
                ],
            ],
            'fields' => 'ids',
            'posts_per_page' => 1,
        ]);

        return $query->have_posts() ? $query->posts[0] : null;
    }

    private static function create(array $data): void
    {
        $postId = wp_insert_post([
            'post_type' => 'apartment',
            'post_status' => 'publish',
            'post_title' => $data['name'] ?? $data['unit_id'],
        ]);

        if (!is_wp_error($postId)) {
            self::update_fields($postId, $data);
        }
    }

    private static function update(int $postId, array $data): void
    {
        wp_update_post([
            'ID' => $postId,
            'post_title' => $data['name'] ?? $data['unit_id'],
        ]);

        self::update_fields($postId, $data);
    }

    private static function update_fields(int $postId, array $data): void
    {
        update_field('unit_id', $data['unit_id'], $postId);
        update_field('address', $data['address'] ?? $data['short_address'], $postId);
        update_field('price', $data['base_rent'] ?? null, $postId);
        update_field('living_space', $data['living_space'] ?? $data['property_space_value'], $postId);
        update_field('status_name', $data['property_status_id'] ?? null, $postId);
        update_field('free_from', $data['free_from'] ?? null, $postId);
        update_field('image_url', $data['images'][0]['url'] ?? null, $postId);
        update_field('number_of_rooms', $data['number_of_rooms'] ?? null, $postId);
        update_field('number_of_bed_rooms', $data['number_of_bed_rooms'] ?? null, $postId);
        update_field('number_of_bath_rooms', $data['number_of_bath_rooms'] ?? null, $postId);
        update_field('floor', $data['floor'] ?? null, $postId);
        update_field('total_rent', $data['total_rent'] ?? null, $postId);
        update_field('service_charge', $data['service_charge'] ?? null, $postId);
        update_field('construction_year', $data['construction_year'] ?? null, $postId);
        update_field('energy_efficiency_class', $data['energy_efficiency_class'] ?? null, $postId);
        update_field('description_note', $data['description_note'] ?? null, $postId);
        update_field('long_description_note', $data['long_description_note'] ?? null, $postId);
        update_field('rented', $data['rented'] ?? null, $postId);
        update_field('free_from', $data['free_from'] ?? null, $postId);

    }
}
