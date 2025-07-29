<?php

namespace Propstack\Includes;

use Propstack\Includes\Admin\SettingsPage;

class ApiClient
{
   public function fetch(array $params = []): array
{
    $url    = SettingsPage::get_api_url();
    $apiKey = SettingsPage::get_api_token();
 
    $query = http_build_query($params);

     error_log('URL: ' . $url . '?' . $query);

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

return $data['data'] ?? [];
}

}

