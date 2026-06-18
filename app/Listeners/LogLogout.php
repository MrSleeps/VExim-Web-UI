<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Spatie\Activitylog\Facades\Activity;
use Illuminate\Support\Facades\Log;

class LogLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        try {
            if ($event->user) {
                activity()
                    ->causedBy($event->user)
                    ->performedOn($event->user)
                    ->withProperties([
                        'ip' => request()->ip(),
                        'guard' => $event->guard,
                    ])
                    ->event('logout')
                    ->log('User logged out');
                    
                Log::info('Logout logged successfully', ['user_id' => $event->user->id]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to log logout activity: ' . $e->getMessage());
        }
    }
}