<?php

use Illuminate\Support\Facades\Schedule;

/*
| Scheduled tasks. On the server ONE cron entry runs them all (see docs/GO_LIVE.md):
|   * * * * * cd /path/to/site && php artisan schedule:run >> /dev/null 2>&1
*/

// Send queued order emails + WhatsApp alerts. Shared hosting can't keep a worker running,
// so the scheduler starts one every minute; it stops when the queue is empty.
// Set QUEUE_WORKER_VIA_SCHEDULER=false if the server runs `queue:work` under Supervisor instead.
if (config('orders.queue_worker_via_scheduler')) {
    Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
        ->everyMinute()
        ->withoutOverlapping(5);
}

// Nightly backup (database + uploads) to storage/app/backups, old backups cleaned, health checked
Schedule::command('backup:clean')->dailyAt('01:00');
Schedule::command('backup:run')->dailyAt('01:30');
Schedule::command('backup:monitor')->dailyAt('03:00');

// Housekeeping: failed jobs older than 30 days, activity log entries older than a year,
// error log entries not seen for ERROR_LOG_RETENTION_DAYS (default 30)
Schedule::command('queue:prune-failed --hours=720')->daily();
Schedule::command('model:prune', ['--model' => [\App\Models\AdminActivity::class, \App\Models\ErrorLog::class]])->daily();
