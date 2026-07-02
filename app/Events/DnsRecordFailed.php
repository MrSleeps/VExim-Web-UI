<?php

namespace App\Events;

use VEximweb\Core\Data\Models\Domain;

class DnsRecordFailed
{
    public function __construct(
        public Domain $domain,
        public string $recordType,
        public string $recordName,
        public string $errorMessage,
        public string $provider = ''
    ) {}
}