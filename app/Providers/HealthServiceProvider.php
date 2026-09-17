<?php

namespace App\Providers;

use App\Checks\VersionCheck;
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\RedisMemoryUsageCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

class HealthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $checks = [
            DatabaseCheck::new(),
            DatabaseConnectionCountCheck::new()
                ->warnWhenMoreConnectionsThan((int) config('health.vexim.database_connections.warning', 40))
                ->failWhenMoreConnectionsThan((int) config('health.vexim.database_connections.failure', 50)),
            CacheCheck::new(),
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage((int) config('health.vexim.disk.warning', 80))
                ->failWhenUsedSpaceIsAbovePercentage((int) config('health.vexim.disk.failure', 90)),
            ScheduleCheck::new()
                ->heartbeatMaxAgeInMinutes((int) config('health.vexim.schedule.max_age_minutes', 2)),
            DebugModeCheck::new()->expectedToBe(false),
            EnvironmentCheck::new()
                ->expectEnvironment((string) config('health.vexim.expected_environment', 'production')),
            OptimizedAppCheck::new(),
            VersionCheck::new()
                ->includePrereleases(false)
                ->repoUrl(config('vexim.package.url'))
                ->if(function () {
                    $cacheKey = 'health:version_check:last_run';

                    if (cache()->has($cacheKey)) {
                        return false;
                    }

                    cache()->put($cacheKey, true, now()->addDay());

                    return true;
                }),
        ];

        if ($this->shouldCheckQueue()) {
            $checks[] = QueueCheck::new()
                ->failWhenHealthJobTakesLongerThanMinutes((int) config('health.vexim.queue.max_age_minutes', 5));
        }

        if ($this->shouldCheckRedis()) {
            $checks[] = RedisCheck::new();
            $checks[] = RedisMemoryUsageCheck::new()
                ->warnWhenAboveMb((float) config('health.vexim.redis.memory_warning_mb', 256))
                ->failWhenAboveMb((float) config('health.vexim.redis.memory_failure_mb', 500));
        }

        Health::checks($checks);
    }

    private function shouldCheckQueue(): bool
    {
        $configured = config('health.vexim.queue.enabled');

        if ($configured !== null) {
            return (bool) $configured;
        }

        return ! in_array(config('queue.default'), ['sync', 'null'], true);
    }

    private function shouldCheckRedis(): bool
    {
        $configured = config('health.vexim.redis.enabled');

        if ($configured !== null) {
            return (bool) $configured;
        }

        return in_array('redis', [
            config('cache.default'),
            config('queue.default'),
            config('session.driver'),
        ], true);
    }
}
