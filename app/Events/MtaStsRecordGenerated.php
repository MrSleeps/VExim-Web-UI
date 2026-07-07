<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use VEximweb\Plugin\MTASTS\Models\MtaSts;

class MtaStsRecordGenerated
{
    use Dispatchable, SerializesModels;
    
    /**
     * The MTA-STS record
     */
    public MtaSts $mtaSts;
    
    /**
     * The DNS zone/domain
     */
    public string $zone;
    
    /**
     * The record name (e.g., "_mta-sts.example.com")
     */
    public string $name;
    
    /**
     * The record type (TXT)
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
     * Create a new MTA-STS record created event.
     */
    public function __construct(
        MtaSts $mtaSts,
        string $zone,
        string $name,
        string $content,
        int $ttl = 3600,
        string $operation = 'create',
        ?string $recordId = null
    ) {
        $this->mtaSts = $mtaSts;
        $this->zone = $zone;
        $this->name = $name;
        $this->type = 'TXT';
        $this->content = $content;
        $this->ttl = $ttl;
        $this->operation = $operation;
        $this->recordId = $recordId;
        
        // Log when the event is instantiated
        Log::info('MtaStsRecordCreated event constructed', [
            'zone' => $zone,
            'name' => $name,
            'mta_sts_id' => $mtaSts->id,
            'operation' => $operation,
        ]);
    }
}