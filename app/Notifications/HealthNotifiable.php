<?php

namespace App\Notifications;

use App\Services\HealthNotificationRecipients;
use Illuminate\Notifications\Notifiable as NotifiableTrait;

class HealthNotifiable
{
    use NotifiableTrait;

    /** @return string|array<int, string> */
    public function routeNotificationForMail(): string|array
    {
        $recipients = HealthNotificationRecipients::mail();

        return count($recipients) === 1 ? $recipients[0] : $recipients;
    }

    public function routeNotificationForSlack(): string
    {
        return (string) config('health.notifications.slack.webhook_url', '');
    }

    public function getKey(): int
    {
        return 1;
    }
}
