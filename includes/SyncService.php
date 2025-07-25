<?php

namespace Propstack\Includes;

use Propstack\Includes\PostHandler\ApartmentHandler;
//use Propstack\Includes\PostHandler\ParkingHandler;
//use Propstack\Includes\PostHandler\GewerbeHandler;

class SyncService
{
    /**
     * Synchronisiert alle Daten eines bestimmten Projekts.
     *
     * @param string $projekt_id – die ID aus Propstack (z. B. 12345)
     * @param int $projekt_post_id – die WordPress-Post-ID des Projekts
     */
    public function sync_project($projekt_id, $projekt_post_id): array
    {
        $client = new ApiClient();
        $objects = $client->fetch(['project_id' => $projekt_id]);

        $created = 0;
        $updated = 0;

        foreach ($objects as $item) {
            $rs_type = $item['rs_type'] ?? null;

            switch ($rs_type) {
                case 'APARTMENT':
                    $result = ApartmentHandler::create_or_update($item, $projekt_post_id);
                    break;
                case 'PARKING':
                    $result = ParkingHandler::create_or_update($item, $projekt_post_id);
                    break;
                case 'COMMERCIAL':
                    $result = GewerbeHandler::create_or_update($item, $projekt_post_id);
                    break;
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
        ];
    }
}
