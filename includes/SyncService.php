<?php

namespace Propstack\Includes;

use Propstack\Includes\PostHandler\ApartmentHandler;

class SyncService
{
    public function run(): array
    {
        $client = new ApiClient();
        $objects = $client->fetch();

        $created = 0;
        $updated = 0;

        foreach ($objects as $item) {
            if (($item['rs_type'] ?? null) !== 'APARTMENT') {
                continue;
            }

            $result = ApartmentHandler::create_or_update($item);

            if ($result === 'created') {
                $created++;
            } elseif ($result === 'updated') {
                $updated++;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
        ];
    }
}
