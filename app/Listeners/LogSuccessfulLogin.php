<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Spatie\Activitylog\Facades\Activity;
use Illuminate\Support\Facades\Log;

class LogSuccessfulLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        try {
            activity()
                ->causedBy($event->user)
                ->performedOn($event->user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'guard' => $event->guard,
                    'remember' => $event->remember ? 'yes' : 'no',
                ])
                ->event('login')
                ->log('User logged in successfully');
                
            Log::info('Login logged successfully', ['user_id' => $event->user->id]);
        } catch (\Exception $e) {
            Log::error('Failed to log login activity: ' . $e->getMessage());
        }
    }
}