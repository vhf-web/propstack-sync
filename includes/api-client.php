<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fetches data from the Propstack API using the credentials from wp-config.php
 *
 * @return array List of estates from Propstack or an empty array on error
 */
function propstack_get_api_data() {
    if (!defined('PROPSTACK_API_URL') || !defined('PROPSTACK_API_TOKEN')) {
        return [];
    }

    $url = add_query_arg([
        'project_id' => 404126,
        'limit' => 1000,
    ], PROPSTACK_API_URL);

    
    $response = wp_remote_get(PROPSTACK_API_URL, [
        'headers' => [
            'X-API-KEY' => PROPSTACK_API_TOKEN,
            'Content-Type'  => 'application/json',
        ],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        error_log('Propstack API error: ' . $response->get_error_message());
        return [];
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);


    error_log('📡 API returned ' . count($data['data']) . ' items');
    error_log('🔍 Dump item: ' . print_r($item, true));

    return $data['data'];
}