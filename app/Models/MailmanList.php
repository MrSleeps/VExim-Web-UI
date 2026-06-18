<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class MailmanList extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mailman_lists';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'list_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'domain_id',
        'list_name',
        'list_email',
        'mailman_list_id',
        'enabled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'domain_id' => 'integer',
        'enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Get the domain that owns the mailing list.
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'domain_id');
    }

    /**
     * Scope a query to only include enabled lists.
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope a query to only include lists for a specific domain.
     */
    public function scopeForDomain(Builder $query, int $domainId): Builder
    {
        return $query->where('domain_id', $domainId);
    }

    /**
     * Scope a query to find a list by its full email address.
     */
    public function scopeByEmail(Builder $query, string $email): Builder
    {
        return $query->where('list_email', $email);
    }

    /**
     * Get the full list address with domain.
     */
    public function getFullAddressAttribute(): string
    {
        return $this->list_email;
    }

    /**
     * Get the local part of the list email.
     */
    public function getLocalPartAttribute(): string
    {
        return explode('@', $this->list_email)[0] ?? $this->list_name;
    }

    /**
     * Get the domain part of the list email.
     */
    public function getDomainPartAttribute(): string
    {
        return explode('@', $this->list_email)[1] ?? '';
    }

    /**
     * Determine if the list is active and usable.
     */
    public function isActive(): bool
    {
        return $this->enabled && !$this->trashed();
    }
    
    /**
     * Set the mailman_list_id automatically from list_email
     */
    public function setMailmanListIdAttribute($value)
    {
        // If mailman_list_id is not set, generate it from list_email
        if (empty($value) && !empty($this->list_email)) {
            $parts = explode('@', $this->list_email);
            $this->attributes['mailman_list_id'] = $parts[0] . '.' . ($parts[1] ?? '');
        } else {
            $this->attributes['mailman_list_id'] = $value;
        }
    }

    /**
     * Boot the model
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            // Always set mailman_list_id from list_email if not explicitly set
            if (empty($model->mailman_list_id) && !empty($model->list_email)) {
                $parts = explode('@', $model->list_email);
                $model->mailman_list_id = $parts[0] . '.' . ($parts[1] ?? '');
            }
        });
    }    
}