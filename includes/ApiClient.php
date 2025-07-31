<?php

namespace Propstack\Includes;


class ApiClient
{
    public function fetch(array $params = [], string $endpoint = 'properties'): array
{
    $url = match ($endpoint) {
        'projects'   => defined('PROPSTACK_PROJECTS_API_URL')   ? PROPSTACK_PROJECTS_API_URL   : null,
        'properties' => defined('PROPSTACK_PROPERTIES_API_URL') ? PROPSTACK_PROPERTIES_API_URL : null,
        default      => null
    };

    $apiKey = defined('PROPSTACK_API_TOKEN') ? PROPSTACK_API_TOKEN : null;

    if (!$url || !$apiKey) {
        error_log('❌ API URL oder Token fehlt.');
        return [];
    }

    $query = http_build_query($params);
    $response = wp_remote_get($url . '?' . $query, [
        'headers' => [
            'X-API-Key' => $apiKey,
            'Accept'    => 'application/json',
        ],
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        error_log('❌ WP Error: ' . $response->get_error_message());
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // 👇 Wichtig: unterschiedliche Struktur je nach Endpoint
    if ($endpoint === 'projects') {
        return is_array($data) ? $data : [];
    }

    return $data['data'] ?? [];
}

}

