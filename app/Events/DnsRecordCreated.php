<?php

namespace App\Events;

use VEximweb\Core\Data\Models\Domain;
use Illuminate\Support\Facades\Log;

class DnsRecordCreated
{
    public function __construct(
        public Domain $domain,
        public string $recordType,
        public string $recordName,
        public string $recordValue,
        public string $provider,
        public string $message = ''
    ) {
        Log::debug('DnsRecordCreated event constructed', [
            'domain_id' => $domain->domain_id,
            'domain' => $domain->domain,
            'provider' => $provider,
            'record_type' => $recordType,
            'record_name' => $recordName,
        ]);
    }
}