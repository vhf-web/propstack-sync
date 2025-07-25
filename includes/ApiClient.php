<?php

namespace Propstack\Includes;

use Propstack\Includes\Admin\SettingsPage;

class ApiClient
{
    public function fetch(): array
    {
        $baseUrl    = SettingsPage::get_api_url();
        $projectId  = SettingsPage::get_project_id();
        $token      = SettingsPage::get_api_token(); 

        if (empty($baseUrl) || empty($projectId)) {
            error_log('[PropstackSync] Fehlende API-Einstellungen (URL oder Projekt-ID leer)');
            return []; 
        }

        $url = rtrim($baseUrl, '?') . '?project_id=' . urlencode($projectId);

        $response = wp_remote_get($url, [
            'headers' => [
                'X-Api-Key' => $token,
                'Accept'    => 'application/json',
            ],
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            error_log('[PropstackSync] Fehler beim Abruf: ' . $response->get_error_message());
            return [];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        error_log('[PropstackSync] URL: ' . $url);
        error_log('[PropstackSync] Status: ' . $response_code);
        error_log('[PropstackSync] Body: ' . mb_substr($body, 0, 500)); // max 500 chars

        $data = json_decode($body, true);

        if (!is_array($data) || !isset($data['data'])) {
            error_log('[PropstackSync] Ungültige API-Antwort: kein [data]-Key');
            return [];
        }

        return $data['data'];
    }
}
