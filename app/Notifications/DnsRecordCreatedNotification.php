<?php

namespace App\Notifications;

use App\Events\DnsRecordCreated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use VEximweb\Core\Domain\Filament\Resources\DomainResource;

class DnsRecordCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected DnsRecordCreated $event;

    public function __construct(DnsRecordCreated $event)
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
            'title' => $this->event->message,
            'icon' => 'heroicon-o-document-plus',
            'color' => 'success',
            'message' => $this->event->message,
            'domain_id' => $this->event->domain->domain_id,
            'domain' => $this->event->domain->domain,
            'record_type' => $this->event->recordType,
            'record_name' => $this->event->recordName,
            'record_value' => $this->event->recordValue,
            'provider' => $this->event->provider,
            'provider_name' => $this->getProviderName($this->event->provider),
            'action_url' => $url,
            'time' => now()->toDateTimeString(),
        ];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->event->message,
            'message' => $this->event->message,
            'domain' => $this->event->domain->domain,
            'record_type' => $this->event->recordType,
            'provider' => $this->event->provider,
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