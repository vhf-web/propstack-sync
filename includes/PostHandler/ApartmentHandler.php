<?php
namespace Propstack\Includes\PostHandler;

class ApartmentHandler
{
     /**
     * Holt den Statusnamen basierend auf der Statuszahl.
     */
    private static function get_status_name_by_id( $status_id ) {
        $status_names = array(
            142965 => 'Akquise',
            142966 => 'Vorbereitung',
            142967 => 'Vermarktung',
            142968 => 'Reserviert',
            142969 => 'Verkauft',
            147772 => 'Inaktiv',
            147773 => 'Archiviert',
            155686 => 'Beauftragt',
        );

        // Rückgabe des Statusnamens oder "Unbekannt", falls der Status nicht existiert
        return isset($status_names[$status_id]) ? $status_names[$status_id] : 'Unbekannt';
    }

    public static function create_or_update(array $item, int $project_post_id): string
    {
        // 1. Titel wählen
        $title = $item['name'] ?? $item['unit_id'] ?? 'Untitled';
        // 2. Bestehenden Beitrag finden
        $query = new \WP_Query([
            'post_type'      => 'apartment',
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'     => 'propstack_id', // ← eindeutig!
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

        // 3. Postdaten vorbereiten
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
        
        $status_name = self::get_status_name_by_id($item['property_status_id'] ?? 0); // Holen des Statusnamens anhand der Zahl

        // 4. ACF-Felder speichern
        if (function_exists('update_field')) {
            update_field('propstack_id', $item['id'] ?? '', $post_id);
            update_field('unit_id', $item['unit_id'] ?? '', $post_id);
            update_field('address', $item['address'] ?? $item['short_address'] ?? '', $post_id);
            update_field('street', $item['street'] ?? '', $post_id);
            update_field('house_number', $item['house_number'] ?? '', $post_id);
            update_field('zip_code', $item['zip_code'] ?? '', $post_id);
            update_field('city', $item['city'] ?? '', $post_id);
            update_field('property_space_value', $item['property_space_value'] ?? '', $post_id);
            update_field('object_type', $item['object_type'] ?? '', $post_id);
            update_field('rs_type', $item['rs_type'] ?? '', $post_id);
            update_field('living_space', $item['living_space'] ?? '', $post_id);
            update_field('number_of_rooms', $item['number_of_rooms'] ?? '', $post_id);
            update_field('number_of_bed_rooms', $item['number_of_bed_rooms'] ?? '', $post_id);
            update_field('number_of_bath_rooms', $item['number_of_bath_rooms'] ?? '', $post_id);
            update_field('construction_year', $item['construction_year'] ?? '', $post_id);
            update_field('energy_efficiency_class', $item['energy_efficiency_class'] ?? '', $post_id);
            update_field('thermal_characteristic', $item['thermal_characteristic'] ?? '', $post_id);
            update_field('building_energy_rating_type', $item['building_energy_rating_type'] ?? '', $post_id);
            update_field('description_note', $item['description_note'] ?? '', $post_id);
            update_field('long_description_note', $item['long_description_note'] ?? '', $post_id);
            update_field('energy_certificate_availability', $item['energy_certificate_availability'] ?? '', $post_id);
            update_field('firing_types', $item['firing_types'] ?? '', $post_id);
            update_field('furnishing_note', $item['furnishing_note'] ?? '', $post_id);
            update_field('location_note', $item['location_note'] ?? '', $post_id);
            update_field('other_note', $item['other_note'] ?? '', $post_id);
            update_field('status_name', $status_name, $post_id);
            update_field('price', $item['price'] ?? '', $post_id);
            update_field('base_rent', $item['base_rent'] ?? '', $post_id);
            update_field('floor', $item['floor'] ?? '', $post_id);
            update_field('total_rent', $item['total_rent'] ?? '', $post_id);
            update_field('service_charge', $item['service_charge'] ?? '', $post_id);
            update_field('rented', $item['rented'] ?? '', $post_id);
            update_field('related_project', $project_post_id, $post_id);
            update_field('object_number', $item['object_number'] ?? '', $post_id);
            update_field('status', $item['status'] ?? '', $post_id);
            update_field('marketing_type', $item['marketing_type'] ?? '', $post_id);
            update_field('viewing_note', $item['viewing_note'] ?? '', $post_id);
            update_field('balcony', $item['balcony'] ?? '', $post_id);
            update_field('elevator', $item['elevator'] ?? '', $post_id);
            update_field('heating_type', $item['heating_type'] ?? '', $post_id);
            update_field('features', $item['features'] ?? '', $post_id);
            update_field('free_from', $item['free_from'] ?? '', $post_id);

$gallery_urls = [];

foreach ($item['images'] ?? [] as $image) {
    if (!empty($image['url'])) {
        $gallery_urls[] = [
            'url'   => esc_url_raw($image['url']),
            'title' => sanitize_text_field($image['title'] ?? ''),
        ];
    }
}

update_field('gallery_urls', $gallery_urls, $post_id);

        }

        // 5. Dokumente als JSON (wenn nötig)
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
