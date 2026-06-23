<?php

namespace App\Observers;

use VEximweb\Core\Data\Models\EximUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EximUserObserver
{
    public function __construct()
    {
        Log::info('EximUserObserver instantiated');
    }
    
    /**
     * Handle the EximUser "updated" event.
     */
    public function updated(EximUser $eximUser): void
    {
        Log::info('Observer updated() method fired', [
            'user_id' => $eximUser->user_id,
            'username' => $eximUser->username,
            'was_changed_crypt' => $eximUser->wasChanged('crypt'),
            'all_changes' => $eximUser->getChanges()
        ]);
        
        if ($eximUser->wasChanged('crypt')) {
            Log::info('Password change detected in observer');
            
            try {
                activity()
                    ->performedOn($eximUser)
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'event' => 'password_change',
                        'user_identifier' => $eximUser->username,
                        'changed_at' => now()->toDateTimeString(),
                        'ip' => request()->ip()
                    ])
                    ->event('password_updated')
                    ->log('Password was changed for ' . $eximUser->username);
                
                Log::info('Activity log created successfully from observer');
            } catch (\Exception $e) {
                Log::error('Failed to create activity log: ' . $e->getMessage());
            }
        }
    }
}