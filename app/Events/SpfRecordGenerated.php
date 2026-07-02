<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SpfRecordGenerated
{
    use Dispatchable, SerializesModels;
    
    /**
     * The DNS zone/domain
     */
    public string $zone;
    
    /**
     * The record name (e.g., "default._domainkey.example.com")
     */
    public string $name;
    
    /**
     * The record type (TXT, A, MX, CNAME, etc.)
     */
    public string $type;
    
    /**
     * The record content/value
     */
    public string $content;
    
    /**
     * Time to live in seconds (default: 3600)
     */
    public int $ttl;
    
    /**
     * Optional: The operation type (create, update, delete)
     */
    public string $operation;
    
    /**
     * Optional: Record ID for updates/deletes
     */
    public ?string $recordId;
    
    /**
     * Create a new DNS record required event.
     */
    public function __construct(
        string $zone,
        string $name,
        string $type,
        string $content,
        int $ttl = 3600,
        string $operation = 'create',
        ?string $recordId = null
    ) {
        $this->zone = $zone;
        $this->name = $name;
        $this->type = $type;
        $this->content = $content;
        $this->ttl = $ttl;
        $this->operation = $operation;
        $this->recordId = $recordId;
        
        // Log when the event is instantiated
        Log::info('SpfKeyGenerated event constructed', [
            'zone' => $zone,
            'name' => $name,
            'type' => $type,
            'operation' => $operation,
        ]);
    }
}