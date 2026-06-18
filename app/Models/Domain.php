<?php

namespace App\Models;

use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\LogsAllActivities;

/**
 * Represents an email domain in the mail system.
 * 
 * A domain contains configuration for email handling including user accounts,
 * quotas, spam filtering, virus scanning, and various email processing features.
 * Domains can have multiple Exim users (email accounts) and administrators.
 */
class Domain extends Model implements HasName
{
    use LogsAllActivities;
    
    /** @var string The database table associated with the model. */
    protected $table = 'domains';
    
    /** @var string The primary key column name for this model. */
    protected $primaryKey = 'domain_id';
    
    /** @var bool Indicates that this model does not use timestamp columns. */
    public $timestamps = false;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'domain',           // The domain name (e.g., 'example.com')
        'maildir',          // Path to the mail storage directory
        'uid',              // Unix user ID for the domain's mail storage
        'gid',              // Unix group ID for the domain's mail storage
        'max_accounts',     // Maximum number of email accounts allowed for this domain
        'quotas',           // Storage quota in megabytes for the entire domain
        'type',             // Domain type (e.g., 'local', 'alias', 'remote')
        'avscan',           // Whether virus scanning is enabled
        'blocklists',       // Whether blocklist filtering is enabled
        'enabled',          // Whether the domain is active and accepting email
        'mailinglists',     // Whether mailing list functionality is enabled
        'maxmsgsize',       // Maximum allowed message size in bytes
        'pipe',             // Whether piping to external programs is allowed
        'spamassassin',     // Whether SpamAssassin spam filtering is enabled
        'sa_tag',           // SpamAssassin score threshold for tagging as spam
        'sa_refuse',        // SpamAssassin score threshold for rejecting email
    ];
    
    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
        'avscan' => 'boolean',
        'blocklists' => 'boolean',
        'mailinglists' => 'boolean',
        'pipe' => 'boolean',
        'spamassassin' => 'boolean',
        'max_accounts' => 'integer',
        'quotas' => 'integer',
        'maxmsgsize' => 'integer',
        'sa_tag' => 'integer',
        'sa_refuse' => 'integer',
        'uid' => 'integer',
        'gid' => 'integer',
    ];
    
    /**
     * Get the display name for Filament admin panel.
     * 
     * @return string
     */
    public function getFilamentName(): string
    {
        return $this->domain;
    }
    
    /**
     * Get all Exim users (email accounts) associated with this domain.
     * 
     * @return HasMany
     */
    public function eximUsers(): HasMany
    {
        return $this->hasMany(EximUser::class, 'domain_id', 'domain_id');
    }
    
    /**
     * Get all administrators assigned to this domain.
     * 
     * Administrators have domain_admin privileges and can manage this domain's
     * settings, users, and blocklists.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function administrators()
    {
        return $this->belongsToMany(User::class, 'vw_domain_user', 'domain_id', 'user_id')
                    ->withPivot('role')
                    ->withTimestamps()
                    ->wherePivot('role', 'domain_admin');
    }
    
    /**
     * Get the domain alias associated with this domain.
     * 
     * An alias allows this domain to be known by another name, redirecting
     * email from the alias to this domain.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function domainAlias()
    {
        return $this->hasOne(DomainAlias::class, 'domain_id');
    }   
    
    /**
     * Get the DKIM configuration for this domain
     */
    public function dkim(): HasOne
    {
        return $this->hasOne(DKIM::class, 'domain_id', 'domain_id');
    }
    
    /**
     * Check if domain has active DKIM
     */
    public function hasActiveDkim(): bool
    {
        return $this->dkim && $this->dkim->enabled;
    }    
}
