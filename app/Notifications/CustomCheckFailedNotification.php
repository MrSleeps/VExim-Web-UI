<?php

namespace App\Notifications;

use App\Channels\FinMailChannel;
use App\Checks\VersionCheck;
use App\Services\HealthNotificationRecipients;
use Carbon\Carbon;
use FinityLabs\FinMail\Helpers\TokenValue;
use FinityLabs\FinMail\Mail\TemplateMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Spatie\Health\Enums\Status;

class CustomCheckFailedNotification extends Notification
{
    use Queueable;

    protected Collection $results;

    public function __construct(array $results)
    {
        $this->results = collect($results);
    }

    public function via(): array
    {
        if (HealthNotificationRecipients::mail() === []) {
            return [];
        }

        if ($this->getVersionCheckResult()) {
            return [FinMailChannel::class];
        }

        return ['mail'];
    }

    public function shouldSend(mixed $notifiable, string $channel): bool
    {
        if (! config('health.notifications.enabled')) {
            return false;
        }

        $throttleMinutes = (int) config('health.notifications.throttle_notifications_for_minutes', 60);

        if ($throttleMinutes === 0) {
            return true;
        }

        $cacheKey = config('health.notifications.throttle_notifications_key', 'health:latestNotificationSentAt:').$channel;
        $timestamp = cache()->get($cacheKey);

        if (! $timestamp) {
            cache()->put($cacheKey, now()->timestamp);

            return true;
        }

        if (Carbon::createFromTimestamp($timestamp)->addMinutes($throttleMinutes)->isFuture()) {
            return false;
        }

        cache()->put($cacheKey, now()->timestamp);

        return true;
    }

    public function toFinMail(mixed $notifiable): ?TemplateMail
    {
        $result = $this->getVersionCheckResult();

        if (! $result) {
            return null;
        }

        $recipients = HealthNotificationRecipients::mail();

        if ($recipients === []) {
            return null;
        }

        $meta = $result->meta ?? [];
        $priority = $meta['update_priority'] ?? 'medium';

        if ($priority === 'low') {
            return null;
        }

        return TemplateMail::make('out-of-date-app')
            ->to($recipients)
            ->models([
                'current_version' => new TokenValue($meta['current_version'] ?? 'Unknown'),
                'latest_version' => new TokenValue($meta['latest_version'] ?? 'Unknown'),
                'update_priority' => new TokenValue(strtoupper($meta['update_priority'] ?? 'MEDIUM')),
                'update_message' => new TokenValue($result->getNotificationMessage()),
                'check_time' => new TokenValue($result->ended_at?->format('Y-m-d H:i:s') ?? 'Unknown'),
                'status' => new TokenValue($result->status->value),
            ]);
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('Health check failed for '.config('app.name'))
            ->lines(
                $this->results
                    ->filter(fn ($r) => $r->status != Status::ok())
                    ->map(fn ($r) => $r->getNotificationMessage())
                    ->toArray()
            );
    }

    public function toSlack(): SlackMessage
    {
        $hasVersionOnly = $this->getVersionCheckResult() &&
            $this->results->every(
                fn ($r) => $r->check instanceof VersionCheck || $r->status == Status::ok()
            );

        if ($hasVersionOnly) {
            return (new SlackMessage)
                ->warning()
                ->content('Version update available for '.config('app.name'));
        }

        return (new SlackMessage)
            ->error()
            ->content(
                $this->results
                    ->filter(fn ($r) => $r->status != Status::ok())
                    ->map(fn ($r) => $r->getNotificationMessage())
                    ->join("\n")
            );
    }

    private function getVersionCheckResult(): mixed
    {
        return $this->results->first(
            fn ($r) => $r->check instanceof VersionCheck
                && $r->status != Status::ok()
                && (($r->meta['update_available'] ?? false) === true)
        );
    }
}
