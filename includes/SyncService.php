<?php

namespace Propstack\Includes;

use Propstack\Includes\PostHandler\ApartmentHandler;
use Propstack\Includes\PostHandler\ProjectHandler;
// use Propstack\Includes\PostHandler\ParkingHandler;
// use Propstack\Includes\PostHandler\GewerbeHandler;

class SyncService
{
    /**
     * Synchronisiert alle Daten eines bestimmten Projekts.
     *
     * @param string $projekt_id       – die ID aus Propstack (z. B. 12345)
     * @param int    $projekt_post_id  – die WordPress-Post-ID des Projekts
     */
    public function sync_project($projekt_id, $projekt_post_id): array
    {
        $client = new ApiClient();

        $page = 1;
        $per = 120;
        $all_objects = [];

        do {
            $response = $client->fetch([
                'project_id' => $projekt_id,
                'per'        => $per,
                'page'       => $page,
            ]);

            if (!is_array($response)) {
                break;
            }

            $count = count($response);
            $all_objects = array_merge($all_objects, $response);
            $page++;
        } while ($count === $per);

        $created = 0;
        $updated = 0;

        foreach ($all_objects as $item) {
            $rs_type = $item['rs_type'] ?? null;

            switch ($rs_type) {
                case 'APARTMENT':
                    $result = ApartmentHandler::create_or_update($item, $projekt_post_id);
                    break;
                case 'PARKING':
                    // $result = ParkingHandler::create_or_update($item, $projekt_post_id);
                    continue 2; // aktuell deaktiviert
                case 'COMMERCIAL':
                    // $result = GewerbeHandler::create_or_update($item, $projekt_post_id);
                    continue 2; // aktuell deaktiviert
                default:
                    continue 2;
            }

            if ($result === 'created') {
                $created++;
            } elseif ($result === 'updated') {
                $updated++;
            }
        }

        // Speichere letzten Sync-Zeitpunkt als ACF-Feld am Projekt
        if (function_exists('update_field')) {
            update_field('letzter_sync', current_time('mysql'), $projekt_post_id);
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'objects' => $all_objects,
        ];
    }
    function propstack_sync_all_projects() {
    $args = [
        'post_type' => 'project',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    ];

    $projects = get_posts($args);

    $service = new \Propstack\Includes\SyncService();

    foreach ($projects as $project) {
        $project_id = get_field('project_id', $project->ID); // oder get_post_meta(...)
        if ($project_id) {
            $service->sync_project($project_id, $project->ID);
        }
    }
}
}
