<?php

namespace App\Providers;

use App\Checks\VersionCheck;
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Facades\Health;

class HealthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Health::checks([
            DatabaseCheck::new(),
            VersionCheck::new()
                ->includePrereleases(false)
                ->repoUrl(config('vexim.package.url'))
                ->if(function() {
                    $cacheKey = 'health:version_check:last_run';
                    if (cache()->has($cacheKey)) {
                        return false;
                    }
                    cache()->put($cacheKey, true, now()->addDay());
                    return true;
                }),
        ]);
    }
}