jQuery(document).ready(function ($) {
    $('.propstack-sync-btn').on('click', function () {
        const button = $(this);
        const projectId = button.data('project');
        const postId = button.data('post-id');
        const status = button.siblings('.sync-status-apartment');
        status.text('⏳ Lädt...');

        $.post(PropstackSync.ajaxUrl, {
            action: 'propstack_sync_project_ajax',
            nonce: PropstackSync.nonce,
            project_id: projectId,
            post_id: postId
        }, function (response) {
            if (response.success) {
                status.text('✅ ' + response.data.message);
            } else {
                status.text('❌ Fehler');
            }
        });
    });

    $('.propstack-sync-meta-btn').on('click', function () {
        const button = $(this);
        const projectId = button.data('project');
        const status = button.siblings('.sync-status-project');
        status.text('⏳ Lädt...');

        $.post(PropstackSync.ajaxUrl, {
            action: 'propstack_sync_project_meta',
            nonce: PropstackSync.nonce,
            project_id: projectId
        }, function (response) {
              console.log('Projekt-Meta-Sync Response:', response); // <--- NEU
            if (response.success) {
                status.text('✅ ' + response.data.message);
            } else {
                status.text('❌ Fehler beim Projekt-Sync');
            }
        });
    });
});
