<?php

use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\DispatchQueueCheckJobsCommand;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;

Schedule::command(RunHealthChecksCommand::class)->everyMinute();

$queueHealthEnabled = config('health.vexim.queue.enabled');

if ($queueHealthEnabled === true || ($queueHealthEnabled === null && ! in_array(config('queue.default'), ['sync', 'null'], true))) {
    Schedule::command(DispatchQueueCheckJobsCommand::class)->everyMinute();
}

// Keep this last so a successful heartbeat means the scheduler reached the end.
Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyMinute();
