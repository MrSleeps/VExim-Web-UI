<?php
namespace App\Notifications;

use App\Channels\FinMailChannel;
use App\Checks\VersionCheck;
use FinityLabs\FinMail\Mail\TemplateMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Spatie\Health\Enums\Status;
use FinityLabs\FinMail\Helpers\TokenValue;

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
        if ($this->getVersionCheckResult()) {
            return [FinMailChannel::class];
        }

        return ['mail'];
    }

    public function toFinMail(mixed $notifiable): ?TemplateMail
    {
        $result = $this->getVersionCheckResult();

        if (! $result) {
            return null;
        }

        $meta     = $result->meta ?? [];
        $priority = $meta['update_priority'] ?? 'medium';

        if ($priority === 'low') {
            return null;
        }

        return TemplateMail::make('out-of-date-app')
            ->to(config('health.notifications.mail.to'))
            ->models([
                'current_version' => new TokenValue($meta['current_version'] ?? 'Unknown'),
                'latest_version'  => new TokenValue($meta['latest_version']  ?? 'Unknown'),
                'update_priority' => new TokenValue(strtoupper($meta['update_priority'] ?? 'MEDIUM')),
                'update_message'  => new TokenValue($result->getNotificationMessage()),
                'check_time'      => new TokenValue($result->ended_at?->format('Y-m-d H:i:s') ?? 'Unknown'),
                'status'          => new TokenValue($result->status->value),
        ]);
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('Health check failed for ' . config('app.name'))
            ->lines(
                $this->results
                    ->filter(fn($r) => $r->status != Status::ok())
                    ->map(fn($r) => $r->getNotificationMessage())
                    ->toArray()
            );
    }

    public function toSlack(): SlackMessage
    {
        $hasVersionOnly = $this->getVersionCheckResult() &&
            $this->results->every(
                fn($r) => $r->check instanceof VersionCheck || $r->status == Status::ok()
            );

        if ($hasVersionOnly) {
            return (new SlackMessage)
                ->warning()
                ->content('Version update available for ' . config('app.name'));
        }

        return (new SlackMessage)
            ->error()
            ->content(
                $this->results
                    ->filter(fn($r) => $r->status != Status::ok())
                    ->map(fn($r) => $r->getNotificationMessage())
                    ->join("\n")
            );
    }

    private function getVersionCheckResult(): mixed
    {
        return $this->results->first(
            fn($r) => $r->check instanceof VersionCheck && $r->status != Status::ok()
        );
    }
}