<?php

namespace App\Services;

use VEximweb\Core\Data\Models\User;

class HealthNotificationRecipients
{
    /**
     * Resolve the email recipients for application health notifications.
     *
     * HEALTH_TO_ADDRESS remains available as an explicit override. When it is
     * not configured, notifications are sent to every system administrator.
     *
     * @return array<int, string>
     */
    public static function mail(): array
    {
        $configured = config('health.notifications.mail.to');

        if (is_string($configured) && trim($configured) !== '') {
            return [trim($configured)];
        }

        if (is_array($configured)) {
            $configured = collect($configured)
                ->filter(fn ($email) => is_string($email) && trim($email) !== '')
                ->map(fn (string $email) => trim($email))
                ->unique()
                ->values()
                ->all();

            if ($configured !== []) {
                return $configured;
            }
        }

        return User::role('system_admin')
            ->whereNotNull('email')
            ->pluck('email')
            ->filter(fn ($email) => is_string($email) && trim($email) !== '')
            ->map(fn (string $email) => trim($email))
            ->unique()
            ->values()
            ->all();
    }
}
