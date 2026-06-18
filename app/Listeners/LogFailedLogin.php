<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Spatie\Activitylog\Facades\Activity;
use Illuminate\Support\Facades\Log;

class LogFailedLogin
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        try {
            $email = $event->credentials['email'] ?? 'unknown';
            
            // For failed logins, we don't have a user object, so we'll use null for subject
            // but we need to provide a default value for subject_id
            activity()
                ->withProperties([
                    'email' => $email,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'guard' => $event->guard,
                ])
                ->event('failed_login')
                ->log("Failed login attempt for: {$email}");
                
            Log::warning('Failed login attempt logged', ['email' => $email, 'ip' => request()->ip()]);
        } catch (\Exception $e) {
            Log::error('Failed to log failed login activity: ' . $e->getMessage());
        }
    }
}