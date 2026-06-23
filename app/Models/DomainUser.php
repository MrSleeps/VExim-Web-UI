<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VEximweb\Core\Data\Models\Domain;

/**
 * Represents the relationship between a user and a domain.
 * 
 * This pivot model manages which users (administrators) have access to which domains,
 * including their role within that domain (e.g., domain_admin, viewer, etc.).
 */
class DomainUser extends Model
{
    
    /** @var string The primary key column name. */
    protected $primaryKey = 'id';
    
    /** @var bool Indicates if the model should be timestamped. */
    public $timestamps = true;
    
    protected $table = 'vw_domain_user';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'domain_id',
        'role',
    ];
    
    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'domain_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Get the user associated with this pivot record.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    /**
     * Get the domain associated with this pivot record.
     * 
     * @return BelongsTo
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'domain_id');
    }
    
    /**
     * Check if the user has admin role for this domain.
     * 
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'domain_admin';
    }
    
    /**
     * Check if the user has viewer role for this domain.
     * 
     * @return bool
     */
    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }
    
    /**
     * Promote user to admin role.
     * 
     * @return bool
     */
    public function promoteToAdmin(): bool
    {
        return $this->update(['role' => 'domain_admin']);
    }
    
    /**
     * Demote user to viewer role.
     * 
     * @return bool
     */
    public function demoteToViewer(): bool
    {
        return $this->update(['role' => 'viewer']);
    }
}