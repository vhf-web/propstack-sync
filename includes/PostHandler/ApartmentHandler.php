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
            142967 => 'Verfügbar',
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
                'relation' => 'AND',
                [
                    'key'     => 'propstack_id', 
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

            $we_label = '';
            $source = trim((string) $item['unit_id']);
if (preg_match('/\bWE[\s\-\/]*([0-9]+)(?:[\s\-\/]+([A-ZÄÖÜß]+[0-9]*))?/iu', $source, $m)) {
    $digits = $m[1];
    $suffix = (isset($m[2]) && $m[2] !== '') ? ' ' . mb_strtoupper($m[2], 'UTF-8') : '';
    $we_label = 'WE ' . $digits . $suffix;
} else {
    $we_label = ''; 
}
update_field('we_label', $we_label, $post_id);

            $post_url = get_permalink($post_id);
    if ($status_name === 'Verfügbar') {
        $status_display = '<a href="' . esc_url($post_url) . '" title="Zur Detailseite ' . esc_attr($item['unit_id'] ?? '') . '">' . esc_html($status_name) . '</a>';
    } else {
        $status_display = esc_html($status_name);
    }
    update_field('status_display', $status_display, $post_id);

    update_field('price_formatted', number_format((float)($item['price'] ?? 0), 0, ',', '.'), $post_id);

    // Matterport
    $matterport_url = null;
    if (!empty($item['links']) && is_array($item['links'])) {
        foreach ($item['links'] as $link) {
            $u = $link['url'] ?? '';
            if (is_string($u) && $u !== '' && stripos($u, 'media.visonation.com') !== false && filter_var($u, FILTER_VALIDATE_URL)) {
                $matterport_url = esc_url_raw($u);
                break;
            }
        }
    }
    update_field('matterport_link', $matterport_url ?? '', $post_id);

    
    $gallery_urls  = [];
    $floorplan_url = null;

    foreach ($item['images'] ?? [] as $image) {
        if (!empty($image['url'])) {
            $url   = esc_url_raw($image['url']);
            $title = sanitize_text_field($image['title'] ?? '');

            if ($floorplan_url === null && (stripos($title, 'grundriss') !== false || stripos($url, 'grundriss') !== false)) {
                $floorplan_url = $url; // сохраняем только первый план
            } else {
                $gallery_urls[] = [
                    'url'   => $url,
                    'title' => $title,
                ];
            }
        }
    }

    update_field('gallery_urls', $gallery_urls, $post_id);
    update_field('floorplan_url', $floorplan_url ?: '', $post_id);

   
    $expose_url = null;
    if (!empty($item['documents']) && is_array($item['documents'])) {
        foreach ($item['documents'] as $document) {
            $u = $document['doc']['url'] ?? '';
            if (is_string($u) && $u !== '' && stripos($u, 'expose') !== false && filter_var($u, FILTER_VALIDATE_URL)) {
                $expose_url = esc_url_raw($u);
                break;
            }
        }
    }
    update_field('expose_url', $expose_url ?: '', $post_id);
} 
$featured_src = '';
if (!empty($gallery_urls[0]['url'])) {
    $featured_src = $gallery_urls[0]['url'];
} elseif (!empty($floorplan_url)) {
    $featured_src = $floorplan_url;
}

$parts = array_filter([
    'Wohnung - ' . trim((string)($item['unit_id'] ?? '')),
    trim((string)($item['street'] ?? '')) . (isset($item['house_number']) && $item['house_number'] !== '' ? ' ' . trim((string)$item['house_number']) : ''),
    trim((string)($item['zip_code'] ?? '')) . (isset($item['city']) && $item['city'] !== '' ? ' ' . trim((string)$item['city']) : ''),
]);

$alt_text = implode(', ', array_filter($parts));



if ($alt_text === '') {
    $alt_text = 'Wohnung ' . ($item['object_number'] ?? $post_id);
}

if ($featured_src) {
    error_log("[propstack] ApartmentHandler: chosen featured_src for post {$post_id}: {$featured_src}");
    \Propstack\Includes\MediaHelpers::set_featured_image_from_url($post_id, $featured_src, true, $alt_text);
} else {
    error_log("[propstack] ApartmentHandler: no featured_src for post {$post_id} (gallery empty, no floorplan)");
}


// 5. Dokumente als JSON (wenn nötig) — остаётся внутри функции
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