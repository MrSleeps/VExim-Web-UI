<?php

namespace App\Notifications;

use App\Events\DnsRecordFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use VEximweb\Core\Domain\Filament\Resources\DomainResource;

class DnsRecordFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected DnsRecordFailed $event;

    public function __construct(DnsRecordFailed $event)
    {
        $this->event = $event;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $url = DomainResource::getUrl('edit', ['record' => $this->event->domain->domain_id]);

        return [
            'format' => 'filament',
            'duration' => 'persistent',
            'title' => "DNS Record Creation Failed", // Changed from $this->event->message
            'icon' => 'heroicon-o-exclamation-triangle',
            'color' => 'danger',
            'message' => "Failed to create {$this->event->recordType} record for {$this->event->domain->domain}: {$this->event->errorMessage}",
            'domain_id' => $this->event->domain->domain_id,
            'domain' => $this->event->domain->domain,
            'record_type' => $this->event->recordType,
            'record_name' => $this->event->recordName,
            'provider' => $this->event->provider,
            'provider_name' => $this->getProviderName($this->event->provider),
            'error_message' => $this->event->errorMessage,
            'action_url' => $url,
            'time' => now()->toDateTimeString(),
        ];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => "DNS Record Creation Failed", // Changed from $this->event->message
            'message' => "Failed to create {$this->event->recordType} record for {$this->event->domain->domain}: {$this->event->errorMessage}",
            'domain' => $this->event->domain->domain,
            'record_type' => $this->event->recordType,
            'error' => $this->event->errorMessage,
        ];
    }

    protected function getProviderName(string $provider): string
    {
        $names = [
            'cloudflare' => 'Cloudflare',
            'aws' => 'AWS Route 53',
            'digitalocean' => 'DigitalOcean',
            'godaddy' => 'GoDaddy',
            'namecheap' => 'Namecheap',
            'linode' => 'Linode',
            'custom' => 'Custom DNS',
        ];

        return $names[$provider] ?? ucfirst($provider);
    }
}