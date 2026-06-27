<?php

namespace App\Services;

use App\Notifications\DnsRecordCreatedNotification;
use App\Notifications\DnsRecordFailedNotification;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use VEximweb\Core\Data\Models\User;

class DnsNotificationService
{
    public function recordCreated($event)
    {

        $users = $this->getUsers($event);
        LaravelNotification::send($users, new DnsRecordCreatedNotification($event));

        // 3. Log
        Log::info("DNS record created: {$event->domain->domain}");
    }

    public function recordFailed($event)
    {

        // 2. Database notification
        $users = $this->getUsers($event);
        LaravelNotification::send($users, new DnsRecordFailedNotification($event));

        // 3. Log
        Log::error("DNS record failed: {$event->domain->domain}");
    }

    private function getUsers($event)
    {
        // Get system admins
        $admins = User::role('system_admin')->get();
        
        // Fix: Specify the table for domain_id to avoid ambiguity
        $domainAdmins = User::whereHas('domains', function($q) use ($event) {
            $q->where('domains.domain_id', $event->domain->domain_id); // Specify 'domains.' table
        })->get();
        
        return $admins->merge($domainAdmins)->unique('id');
    }
}
