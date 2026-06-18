<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Spatie\Activitylog\LogOptions;
use Relaticle\ActivityLog\Concerns\InteractsWithTimeline;
use Relaticle\ActivityLog\Contracts\HasTimeline;
use Relaticle\ActivityLog\Timeline\TimelineBuilder;
use Laravel\Sanctum\HasApiTokens;
use App\Models\VwPersonalAccessToken;
use App\Models\Activity;
/**
 * Represents a web interface user (administrator or domain manager).
 * 
 * The User model is separate from EximUser and handles authentication for
 * the Filament admin panel. These users manage domains, email accounts,
 * and system settings. They have role-based permissions:
 * - System Admin: Full access to all domains and system settings
 * - Domain Admin: Can manage specific domains they're assigned to
 * - Domain User: Limited access, typically for support or reporting
 * 
 * This model supports multi-tenancy, passkey authentication, two-factor
 * authentication, and comprehensive activity logging.
 */
#[Table('users_web')]
#[Fillable([
    'name', 
    'email', 
    'password',
    'active',
    'deactivated_at',
    'email_verified_at',
    'app_authentication_secret',
    'recovery_email',
    'max_domains',
    'max_alias_domains',
    'max_accounts',
    'max_alias_accounts',
    'max_quota'
])]
#[Hidden(['password', 'remember_token'])]
//class User extends Authenticatable implements HasTenants, FilamentUser, HasAppAuthentication, HasPasskeys, HasTimeline
class User extends Authenticatable implements HasTenants, FilamentUser, HasAppAuthentication, PasskeyUser, HasTimeline
{
    /** @use HasFactory<UserFactory> Factory for generating test users */
    use HasFactory, Notifiable, HasRoles, InteractsWithAppAuthentication, PasskeyAuthenticatable, InteractsWithTimeline, HasApiTokens;

    /**
     * Configure activity logging for this model (v5 syntax).
     * 
     * Logs changes to name and email fields, only recording dirty values
     * and avoiding empty log submissions.
     * 
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the attributes that should be cast.
     * 
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', 
            'active' => 'boolean', 
            'deactivated_at' => 'datetime', 
        ];
    }

    /**
     * Get the domains this user can administer.
     * 
     * Many-to-many relationship through the vw_domain_user pivot table,
     * which includes a 'role' field to specify the user's role for each domain.
     * 
     * @return BelongsToMany
     */
    public function domains(): BelongsToMany
    {
        return $this->belongsToMany(Domain::class, 'vw_domain_user', 'user_id', 'domain_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * Get the API tokens for the user.
     * This is required for Sanctum API authentication and the CreateRspamdApiToken command.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function tokens()
    {
        return $this->morphMany(VwPersonalAccessToken::class, 'tokenable');
    }

    /**
     * Check if this user is a super admin (can access all domains).
     * 
     * System administrators have unrestricted access to all domains
     * and system-wide settings.
     * 
     * @return bool
     */
    public function isSystemAdmin(): bool
    {
        return $this->hasRole('system_admin');
    }

    /**
     * Check if this user is a domain admin (can access their domains).
     * 
     * Domain administrators can manage only the domains they are
     * explicitly assigned to through the domains relationship.
     * 
     * @return bool
     */    
    public function isDomainAdmin(): bool
    {
        return $this->hasRole('domain_admin');
    }

    /**
     * Check if this user is a normal user (can only access their account).
     * 
     * Domain users have the most limited access, typically only
     * viewing their own profile and account information.
     * 
     * @return bool
     */    
    public function isDomainUser(): bool
    {
        return $this->hasRole('domain_user');
    }

    /**
     * Required by Filament - returns tenants (domains) this user can access.
     * 
     * System administrators can access all domains.
     * Domain administrators can only access domains they're assigned to.
     * 
     * @param Panel $panel The Filament panel instance
     * @return Collection Collection of Domain models the user can access
     */
    public function getTenants(Panel $panel): Collection
    {
        if ($this->isSystemAdmin()) {
            return Domain::all();
        }
        
        return $this->domains;
    }

    /**
     * Required by Filament - checks if user can access a specific tenant.
     * 
     * @param Domain $tenant The domain to check access for
     * @return bool True if user can access the domain
     */
    public function canAccessTenant($tenant): bool
    {
        if ($this->isSystemAdmin()) {
            return true;
        }
        
        return $this->domains()->where('domain_id', $tenant->getKey())->exists();
    }
    
    /**
     * Check if the user can access the Filament admin panel.
     * 
     * @param Panel $panel
     * @return bool Always returns true for authenticated users
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
    
    /**
     * Build the timeline for activity logging.
     * 
     * Automatically pulls all activity logs directly linked to this model.
     * 
     * @return TimelineBuilder
     */
    public function timeline(): TimelineBuilder
    {
        return TimelineBuilder::make($this)->fromActivityLog();
    }   
    
    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include inactive users.
     */
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    /**
     * Deactivate the user.
     */
    public function deactivate(): void
    {
        $this->update([
            'active' => false,
            'deactivated_at' => now(),
        ]);
    }

    /**
     * Activate the user.
     */
    public function activate(): void
    {
        $this->update([
            'active' => true,
            'deactivated_at' => null,
        ]);
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->active;
    }
    
    /**
     * Get all login activities for this user.
     */
    public function loginActivities()
    {
        return Activity::query()
            ->where('causer_type', self::class)
            ->where('causer_id', $this->getKey())
            ->whereIn('event', ['login', 'logout', 'failed_login'])
            ->orderBy('created_at', 'desc');
    }
    
    /**
     * Get successful logins only.
     */
    public function successfulLogins()
    {
        return $this->loginActivities()
            ->where('event', 'login');
    }
    
    /**
     * Get failed login attempts for this user.
     */
    public function failedLogins()
    {
        return Activity::query()
            ->where('properties->email', $this->email)
            ->where('event', 'failed_login')
            ->orderBy('created_at', 'desc');
    }
    
    /**
     * Get last login timestamp.
     */
    public function getLastLoginAtAttribute()
    {
        $lastLogin = $this->successfulLogins()->first();
        return $lastLogin?->created_at;
    }
    
    /**
     * Get last login IP address.
     */
    public function getLastLoginIpAttribute()
    {
        $lastLogin = $this->successfulLogins()->first();
        return $lastLogin?->properties['ip'] ?? null;
    } 
}