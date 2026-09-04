<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/**
 * Bewijst dat de queue-worker effectief draait: dispatch de job en kijk of
 * de cache-sleutel `queue_health_check` verschijnt. Handig na een deploy op
 * shared hosting, waar de worker via de scheduler-cron loopt.
 *
 *   php artisan tinker --execute="App\Jobs\QueueHealthCheckJob::dispatch();"
 *   php artisan tinker --execute="echo Cache::get('queue_health_check');"
 */
class QueueHealthCheckJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cache::put('queue_health_check', now()->format('d/m/Y H:i:s'), now()->addDay());
    }
}
