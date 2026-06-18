<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\EximUser;

class Group extends Model
{
    use HasFactory;

    protected $table = 'groups';

    protected $fillable = [
        'domain_id',
        'name',
        'is_public',
        'enabled'
    ];

    protected $casts = [
        'domain_id' => 'integer',
        'enabled' => 'boolean',
        'is_public' => 'boolean', 
    ];

    /**
     * Get the domain that owns this group.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }

    /**
     * Get the members (users/aliases) in this group.
     * Assuming member_id references another table like users or aliases.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            EximUser::class,
            'group_contents',
            'group_id',
            'member_id'
        );
    }

    /**
     * Scope for enabled groups only.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', 1);
    }

    /**
     * Scope for public groups only.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', '1');
    }

    /**
     * Check if group is public.
     */
    public function isPublic(): bool
    {
        return $this->is_public === '1';
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
    
    // If you need to access the pivot directly
    public function groupContents()
    {
        return $this->hasMany(GroupContent::class, 'group_id');
    }    
    
}