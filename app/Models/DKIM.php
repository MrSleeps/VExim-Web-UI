<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a DKIM (DomainKeys Identified Mail) signing key for email authentication.
 * 
 * DKIM provides a method for validating domain name identity associated with an email
 * message through cryptographic authentication. This model stores the public/private
 * key pairs used to sign outgoing emails from domains, helping to prevent email
 * spoofing and improve deliverability.
 * 
 * Key features include:
 * - Stores RSA key pairs for domain-level email signing
 * - Configurable selector names for multiple key rotation
 * - Supports different canonicalization algorithms
 * - Generates DNS TXT records for public key publication
 * - Enables/disables signing per domain
 * 
 * The model integrates with the Domain model to associate DKIM keys with specific
 * domains in the email system, allowing each domain to maintain its own signing
 * configuration and key rotation schedule.
 */
class DKIM extends Model
{
    
    /** @var string The table associated with the model. */
    protected $table = 'dkim';
    
    /** @var string The primary key associated with the table. */
    protected $primaryKey = 'dkim_id';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'domain_id',
        'selector',
        'private_key',
        'public_key',
        'canonical',
        'enabled',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];
    
    /**
     * Get the domain that owns this DKIM key.
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'domain_id');
    }
    
    /**
     * Get DNS record for this DKIM key.
     * 
     * Returns the DNS TXT record configuration needed to publish the DKIM public key.
     * This record should be added to the domain's DNS zone file to enable email
     * receivers to verify DKIM signatures.
     * 
     * @return array<string, string> Associative array containing DNS record components
     */
    public function getDnsRecord(): array
    {
        return [
            'name' => $this->selector . '._domainkey.' . $this->domain->domain,
            'type' => 'TXT',
            'value' => "v=DKIM1; k=rsa; p=" . $this->public_key,
        ];
    }
}