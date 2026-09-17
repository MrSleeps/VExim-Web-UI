<?php

namespace App\Notifications;

use App\Channels\FinMailChannel;
use App\Services\HealthNotificationRecipients;
use Carbon\Carbon;
use FinityLabs\FinMail\Helpers\TokenValue;
use FinityLabs\FinMail\Mail\TemplateMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

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

        if ($this->hasOnlyVersionProblem()) {
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
                'update_message' => new TokenValue($this->messageFor($result)),
                'check_time' => new TokenValue($result->ended_at?->format('Y-m-d H:i:s') ?? 'Unknown'),
                'status' => new TokenValue($this->statusFor($result)),
            ]);
    }

    public function toMail(): MailMessage
    {
        $mail = (new MailMessage)
            ->error()
            ->subject('Health check issue for '.config('app.name'))
            ->greeting('Health check issue detected')
            ->line('One or more application health checks need attention.');

        foreach ($this->problemResults() as $result) {
            $mail->line(sprintf(
                '**%s — %s**',
                $this->labelFor($result),
                strtoupper($this->statusFor($result))
            ));
            $mail->line($this->messageFor($result));
        }

        $mail->line('Please review the affected service or application setting.');

        return $mail;
    }

    public function toSlack(): SlackMessage
    {
        if ($this->hasOnlyVersionProblem()) {
            return (new SlackMessage)
                ->warning()
                ->content('Version update available for '.config('app.name'));
        }

        return (new SlackMessage)
            ->error()
            ->content(
                $this->problemResults()
                    ->map(fn ($result) => sprintf(
                        '%s [%s]: %s',
                        $this->labelFor($result),
                        strtoupper($this->statusFor($result)),
                        $this->messageFor($result)
                    ))
                    ->join("\n")
            );
    }

    private function problemResults(): Collection
    {
        return $this->results->filter(
            fn ($result) => ! in_array($this->statusFor($result), ['ok', 'skipped'], true)
        );
    }

    private function hasOnlyVersionProblem(): bool
    {
        $versionResult = $this->getVersionCheckResult();

        if (! $versionResult) {
            return false;
        }

        return $this->problemResults()->every(fn ($result) => $result === $versionResult);
    }

    private function getVersionCheckResult(): mixed
    {
        return $this->results->first(function ($result) {
            $meta = $result->meta ?? [];

            return $this->statusFor($result) !== 'ok'
                && (($meta['update_available'] ?? false) === true)
                && isset($meta['current_version'], $meta['latest_version']);
        });
    }

    private function labelFor(mixed $result): string
    {
        return isset($result->check) && method_exists($result->check, 'getLabel')
            ? $result->check->getLabel()
            : 'Unknown health check';
    }

    private function statusFor(mixed $result): string
    {
        return (string) ($result->status->value ?? 'unknown');
    }

    private function messageFor(mixed $result): string
    {
        $message = trim((string) $result->getNotificationMessage());

        if ($message !== '') {
            return $message;
        }

        if (method_exists($result, 'getShortSummary')) {
            return $result->getShortSummary();
        }

        return 'No additional details were provided.';
    }
}
