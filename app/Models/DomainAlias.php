<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\LogsAllActivities;

/**
 * Represents an alias for an email domain.
 * 
 * Domain aliases allow a domain to be known by multiple names. Emails sent to
 * the alias domain are automatically redirected to the primary domain. For 
 * example, if 'example.net' is an alias for 'example.com', email sent to
 * user@example.net will be delivered to user@example.com.
 * 
 * The alias name serves as the primary key and must be unique across the system.
 */
class DomainAlias extends Model
{
    use LogsAllActivities;
    
    /** @var string The database table associated with the model. */
    protected $table = 'domainalias';
    
    /** @var string The primary key column name - uses the alias name itself. */
    protected $primaryKey = 'alias';
    
    /** @var bool Indicates that the primary key is not auto-incrementing. */
    public $incrementing = false;
    
    /** @var string The data type of the primary key (string since alias is a domain name). */
    protected $keyType = 'string';    

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'domain_id',    // Foreign key linking to the primary domain
        'alias',        // The alias domain name (e.g., 'example-alias.com')
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'domain_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the primary domain that this alias points to.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }
    
    /**
     * Scope query to filter domain aliases accessible by a specific user.
     * 
     * System administrators can see all domain aliases.
     * Domain administrators can only see aliases for domains they manage.
     * Regular users cannot see any domain aliases.
     * 
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSystemAdmin()) {
            return $query;
        }
        
        if ($user->isDomainAdmin()) {
            $domainIds = $user->domains()->pluck('domains.domain_id');
            return $query->whereIn('domain_id', $domainIds);
        }
        
        // Return empty result for unauthorized users
        return $query->whereRaw('1 = 0');
    }
}