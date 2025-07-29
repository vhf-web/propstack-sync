<?php 
namespace Propstack\Includes\PostHandler;

class ApartmentHandler
{
    public static function create_or_update(array $item, int $project_post_id): string
    {
        // Titel: name oder unit_id
        $title = $item['name'] ?? $item['unit_id'] ?? 'Untitled';

        // Suche nach bestehendem Beitrag
        $query = new \WP_Query([
            'post_type'      => 'apartment',
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'     => 'unit_id',
                    'value'   => $item['id'] ?? '',
                    'compare' => '='
                ],
                [
                    'key'     => 'related_project',
                    'value'   => $project_post_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'fields'         => 'ids'
        ]);

        $existing_post_id = $query->have_posts() ? $query->posts[0] : null;

        $post_data = [
            'post_title'   => $title,
            'post_type'    => 'apartment',
            'post_status'  => 'publish',
            'post_content' => $item['description_note'] ?? '',
        ];

        if ($existing_post_id) {
            $post_data['ID'] = $existing_post_id;
            wp_update_post($post_data);
            $post_id = $existing_post_id;
            $action = 'updated';
        } else {
            $post_id = wp_insert_post($post_data);
            $action = 'created';
        }

        // ACF-Felder aktualisieren
        if (function_exists('update_field')) {
            // Wichtig: Key aus JSON muss zum ACF-Feldnamen passen
            update_field('unit_id', $item['id'] ?? '', $post_id); 
            update_field('address', $item['address'] ?? $item['short_address'] ?? '', $post_id);
            update_field('price', $item['price'] ?? null, $post_id);
            update_field('street', $item['street'] ?? null, $post_id);
            update_field('house_number', $item['house_number'] ?? null, $post_id);
            update_field('zip_code', $item['zip_code'] ?? null, $post_id);
            update_field('city', $item['city'] ?? null, $post_id);
            update_field('base_rent', $item['base_rent'] ?? null, $post_id);
            update_field('property_space_value', $item['base_rent'] ?? null, $post_id);
            update_field('living_space', $item['living_space'] ?? null, $post_id);
            update_field('number_of_rooms', $item['number_of_rooms'] ?? null, $post_id);
            update_field('number_of_bed_rooms', $item['number_of_bed_rooms'] ?? null, $post_id);
            update_field('number_of_bath_rooms', $item['number_of_bath_rooms'] ?? null, $post_id);
            update_field('floor', $item['floor'] ?? null, $post_id);
            update_field('total_rent', $item['total_rent'] ?? null, $post_id);
            update_field('service_charge', $item['service_charge'] ?? null, $post_id);
            update_field('construction_year', $item['construction_year'] ?? null, $post_id);
            update_field('energy_efficiency_class', $item['energy_efficiency_class'] ?? null, $post_id);
            update_field('description_note', $item['description_note'] ?? null, $post_id);
            update_field('long_description_note', $item['long_description_note'] ?? null, $post_id);
            update_field('rented', $item['rented'] ?? null, $post_id);
            update_field('free_from', $item['free_from'] ?? null, $post_id);
            update_field('related_project', $project_post_id, $post_id);
            update_field('object_number', $item['object_number'] ?? '', $post_id);
            update_field('status', $item['status'] ?? '', $post_id);
            update_field('status_name', $item['status_name'] ?? '', $post_id);
            update_field('marketing_type', $item['marketing_type'] ?? '', $post_id);
            update_field('rs_type', $item['rs_type'] ?? '', $post_id);
            update_field('object_type', $item['object_type'] ?? '', $post_id);
            update_field('viewing_note', $item['viewing_note'] ?? '', $post_id);
            update_field('balcony', $item['balcony'] ?? '', $post_id);
            update_field('elevator', $item['elevator'] ?? '', $post_id);
            update_field('heating_type', $item['heating_type'] ?? '', $post_id);
            update_field('features', $item['features'] ?? '', $post_id);
            update_field('furnishing_note', $item['furnishing_note'] ?? null, $post_id);
            update_field('location_note', $item['location_note'] ?? null, $post_id);
            update_field('other_note', $item['other_note'] ?? null, $post_id);
            update_field('thermal_characteristic', $item['thermal_characteristic'] ?? null, $post_id);
            update_field('building_energy_rating_type', $item['building_energy_rating_type'] ?? null, $post_id);
            update_field('energy_certificate_availability', $item['energy_certificate_availability'] ?? null, $post_id);
            update_field('firing_types', $item['firing_types'] ?? null, $post_id);

            // Bild-URL (nur erstes Bild)
            $image_url = $item['images'][0]['url'] ?? null;
            if ($image_url) {
                update_field('image_url', $image_url, $post_id);
            }
        }

        // Dokumente als JSON in post_meta
        $doc_urls = array_filter(array_map(function ($doc) {
            return isset($doc['doc']['url']) && filter_var($doc['doc']['url'], FILTER_VALIDATE_URL)
                ? $doc['doc']['url']
                : null;
        }, $item['documents'] ?? []));

        if (!empty($doc_urls)) {
            update_post_meta($post_id, 'propstack_document_urls', json_encode($doc_urls));
        }

        return $action;
    }
}
