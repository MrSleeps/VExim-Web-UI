<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents a whitelist entry for bypassing spam and blocklist filters.
 * 
 * The Whitelist model allows specific senders to always be accepted,
 * bypassing spam filtering and blocklist checks. Whitelist entries can be:
 * - Global: Apply to all domains on the system
 * - Domain-wide: Apply to all users within a specific domain
 * - Per-user: Apply only to a specific email account
 * 
 * This is useful for ensuring important emails (e.g., from business partners,
 * payment processors, or system notifications) are never marked as spam.
 */
class Whitelist extends Model
{
    use HasFactory;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'whitelist_senders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'domain_id',    // Domain ID (0 for global, >0 for domain-specific)
        'user_id',      // User ID
        'localpart',    // Local part for user-specific rules (null for domain-wide)
        'sender',       // Email address or pattern to whitelist (e.g., 'trusted@example.com')
        'comment',      // Optional comment explaining why this sender is whitelisted
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'domain_id' => 'integer',
        'localpart' => 'string',
        'sender' => 'string',
        'comment' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the domain associated with this whitelist entry.
     * 
     * Returns null for global whitelist entries (domain_id = 0).
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'domain_id');
    }

    /**
     * Scope for global whitelist entries (apply to all domains).
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGlobal($query)
    {
        return $query->where('domain_id', 0);
    }

    /**
     * Scope for domain-wide whitelist entries (apply to entire domain).
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDomainWide($query)
    {
        return $query->whereNotNull('domain_id')
            ->where('domain_id', '!=', 0)
            ->whereNull('localpart');
    }

    /**
     * Scope for specific user whitelist entries.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $domainId The domain ID
     * @param string $localpart The local part of the email address
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, $domainId, $localpart)
    {
        return $query->where('domain_id', $domainId)
            ->where('localpart', $localpart);
    }

    /**
     * Scope for domain-specific entries (both domain-wide and per-user).
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $domainId The domain ID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDomain($query, $domainId)
    {
        return $query->where('domain_id', $domainId);
    }

    /**
     * Check if this is a global whitelist entry.
     * 
     * @return bool
     */
    public function isGlobal(): bool
    {
        return $this->domain_id === 0;
    }

    /**
     * Check if this is a domain-wide rule.
     * 
     * @return bool
     */
    public function isDomainWide(): bool
    {
        return $this->domain_id > 0 && is_null($this->localpart);
    }

    /**
     * Check if this is a per-user rule.
     * 
     * @return bool
     */
    public function isPerUser(): bool
    {
        return $this->domain_id > 0 && !is_null($this->localpart);
    }

    /**
     * Get the full email address for user-specific whitelist entries.
     * 
     * Returns the complete email address (localpart@domain) for per-user rules,
     * or null for domain-wide and global rules.
     * 
     * @return string|null
     */
    public function getFullEmailAttribute(): ?string
    {
        if ($this->isPerUser()) {
            return $this->localpart . '@' . optional($this->domain)->domain;
        }
        return null;
    }
}