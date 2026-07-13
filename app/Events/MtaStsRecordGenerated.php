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
     * The MTA-STS record (null for CNAME records)
     */
    public ?MtaSts $mtaSts;
    
    /**
     * The DNS zone/domain
     */
    public string $zone;
    
    /**
     * The record name (e.g., "_mta-sts.example.com" or "mta-sts")
     */
    public string $name;
    
    /**
     * The record type (TXT, CNAME, etc.)
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
     * Create a new MTA-STS record generated event.
     * 
     * @param MtaSts|null $mtaSts The MTA-STS record (null for CNAME records)
     * @param string $zone The DNS zone/domain
     * @param string $name The record name
     * @param string $type The record type (TXT, CNAME, etc.)
     * @param string $content The record content/value
     * @param int $ttl Time to live in seconds
     * @param string $operation The operation type (create, update, delete)
     * @param string|null $recordId Optional record ID for updates/deletes
     */
    public function __construct(
        ?MtaSts $mtaSts,
        string $zone,
        string $name,
        string $type,
        string $content,
        int $ttl = 3600,
        string $operation = 'create',
        ?string $recordId = null
    ) {
        $this->mtaSts = $mtaSts;
        $this->zone = $zone;
        $this->name = $name;
        $this->type = $type;
        $this->content = $content;
        $this->ttl = $ttl;
        $this->operation = $operation;
        $this->recordId = $recordId;
        
        // Log when the event is instantiated
        Log::info('MtaStsRecordGenerated event constructed', [
            'zone' => $zone,
            'name' => $name,
            'type' => $type,
            'mta_sts_id' => $mtaSts?->id ?? 'null',
            'operation' => $operation,
        ]);
    }
}