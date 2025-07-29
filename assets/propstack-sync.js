jQuery(document).ready(function ($) {
    $('.propstack-sync-btn').on('click', function (e) {
        e.preventDefault();

        const button = $(this);
        const row = button.closest('tr');
        const status = row.find('.sync-status');
        const projectId = button.data('project');
        const postId = button.data('post-id');

        status.text('🔄 Synchronisation läuft...');

        $.post(PropstackSync.ajaxUrl, {
            action: 'propstack_sync_project_ajax',
            nonce: PropstackSync.nonce,
            project_id: projectId,
            post_id: postId
        }, function (response) {
            if (response.success) {
                const created = response.data.created;
                const updated = response.data.updated;
                const total = response.data.total || created + updated;
                const message = `✅ ${created} neu, ${updated} aktualisiert (${created + updated}/${total})`;
                status.text(message);
            } else {
                status.text('❌ Fehler: ' + response.data);
            }
        }).fail(function () {
            status.text('❌ Serverfehler');
        });
    });
});
