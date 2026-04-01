<?php
namespace Propstack\Includes\PostHandler;

use Propstack\Includes\ApiClient;

class ProjectHandler
{
    public static function syncProjects(array $projectIds)
    {
        $apiClient = new ApiClient();
        $response = $apiClient->fetch(['project_ids' => implode(',', $projectIds)], 'projects');

        if (empty($response)) {
            return ['created' => 0, 'updated' => 0];
        }

        $updatedProjects = 0;
        $skipped = 0;

        foreach ($response['data'] as $projectData) {
            $external_id = $projectData['id'];

            $existing = get_posts([
                'post_type'   => 'project',
                'post_status' => 'any',
                'numberposts' => 1,
                'meta_query'  => [
                    [
                        'key'     => 'propstack_id',
                        'value'   => $external_id,
                        'compare' => '='
                    ]
                ]
            ]);

            if (empty($existing)) {
                $skipped++;
                continue;
            }

            $post_id = $existing[0]->ID;

            // ✅ Nur Meta aktualisieren
            update_post_meta($post_id, 'street', $projectData['street'] ?? '');
            update_post_meta($post_id, 'house_number', $projectData['house_number'] ?? '');
            update_post_meta($post_id, 'zip_code', $projectData['zip_code'] ?? '');
            update_post_meta($post_id, 'city', $projectData['city'] ?? '');
            update_post_meta($post_id, 'address', $projectData['address'] ?? '');
            update_post_meta($post_id, 'country', $projectData['country'] ?? '');

            $updatedProjects++;
        }

        return [
            'updated' => $updatedProjects,
            'skipped' => $skipped,
            'created' => 0,
        ];
    }
}
