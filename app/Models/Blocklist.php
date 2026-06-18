<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsAllActivities;

/**
 * Represents a blocklist entry that filters email based on header or value patterns.
 * 
 * Blocklist entries are used to block or flag emails that match specific criteria,
 * such as certain header values or content patterns. Each blocklist entry is 
 * associated with a specific domain and the user who created it.
 */
class Blocklist extends Model
{
    use LogsAllActivities;
    
    /** @var string The primary key column name for this model. */
    protected $primaryKey = 'block_id';
    
    /** @var bool Indicates that this model does not use timestamp columns. */
    public $timestamps = false;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'domain_id',    // Foreign key linking to the associated domain
        'user_id',      // Foreign key linking to the user who created the blocklist entry
        'blockhdr',     // The email header field to check (e.g., 'From', 'Subject', 'Received')
        'blockval',     // The value or pattern to match against the header
        'color',        // Visual indicator for the blocklist severity (e.g., 'red', 'yellow')
    ];
    
    /**
     * Get the domain that owns this blocklist entry.
     * 
     * @return BelongsTo
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'domain_id');
    }
    
    /**
     * Get the user who created this blocklist entry.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(EximUser::class, 'user_id', 'user_id');
    }
}