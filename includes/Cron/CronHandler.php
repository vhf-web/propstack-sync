<?php

namespace Propstack\Includes\Cron;

use Propstack\Includes\SyncService;

class CronHandler
{
    public static function register()
    {
        add_action('propstack_daily_sync_event', [self::class, 'run_daily_sync']);
    }

    public static function run_daily_sync()
    {
        $syncService = new SyncService();
        if (function_exists('Propstack\Includes\propstack_sync_all_projects')) {
            \Propstack\Includes\propstack_sync_all_projects();
        }
    }

    public static function activate()
    {
        if (!wp_next_scheduled('propstack_daily_sync_event')) {
            wp_schedule_event(time(), 'daily', 'propstack_daily_sync_event');
        }
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook('propstack_daily_sync_event');
    }
}