<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Relaticle\ActivityLog\Concerns\InteractsWithTimeline;
use Relaticle\ActivityLog\Contracts\HasTimeline;
use Relaticle\ActivityLog\Timeline\TimelineBuilder;
use Spatie\Activitylog\Models\Concerns\HasActivity;  
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Illuminate\Support\Collection;
use App\Traits\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Support\Facades\Log;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

/**
 * Represents an email user account in the Exim mail system.
 * 
 * EximUser is the primary authentication model for email accounts. It handles
 * email delivery preferences, spam filtering settings, quotas, and various
 * email processing features like forwarding, vacation auto-responders, and
 * pipe commands. The model supports multiple password hash formats including
 * bcrypt (for modern auth) and SHA-512 (for legacy systems).
 * 
 * This model integrates with:
 * - Filament admin panel for user management
 * - Spatie Activity Log for audit trails
 * - Spatie Roles/Permissions for access control
 * - Multi-factor authentication for enhanced security
 */
//class EximUser extends Authenticatable implements HasTimeline, FilamentUser, HasAppAuthentication, CanResetPasswordContract, HasPasskeys
class EximUser extends Authenticatable implements HasTimeline, FilamentUser, HasAppAuthentication, CanResetPasswordContract, PasskeyUser
{
    use HasActivity;
    use InteractsWithTimeline;
    use HasRoles;
    use InteractsWithAppAuthentication;
    use CanResetPassword;
    use Notifiable;
    use PasskeyAuthenticatable;
    
    /** @var string The database table associated with the model. */
    protected $table = 'users';
    
    /** @var string The primary key column name. */
    protected $primaryKey = 'user_id';
    
    /** @var bool Indicates if the primary key is auto-incrementing. */
    public $incrementing = true;
    
    /** @var string The data type of the primary key. */
    protected $keyType = 'int';
    
    /** @var bool Indicates if the model should be timestamped. */
    public $timestamps = true;
    
    /** @var string The guard name for authentication and permissions. */
    protected $guard_name = 'web';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'domain_id',        // Foreign key to the parent domain
        'localpart',        // The local part of the email address (before @)
        'username',         // Full email address (localpart@domain)
        'crypt',            // Hashed password (bcrypt or SHA-512 format)
        'uid',              // Unix user ID for file system permissions
        'gid',              // Unix group ID for file system permissions
        'smtp',             // SMTP access flag (whether user can send mail)
        'pop',              // POP3/IMAP access flag (whether user can retrieve mail)
        'type',             // Account type (local, forward, alias, etc.)
        'admin',            // Whether user has administrative privileges
        'on_avscan',        // Enable virus scanning for this user
        'on_blocklist',     // Enable blocklist filtering for spam
        'on_forward',       // Enable email forwarding
        'on_piped',         // Enable piping emails to external programs
        'on_spamassassin',  // Enable SpamAssassin spam filtering
        'on_vacation',      // Enable vacation auto-responder
        'spam_drop',        // Whether to silently drop spam (vs deliver to spam folder)
        'enabled',          // Whether the account is active
        'flags',            // Additional user-specific flags
        'forward',          // Forwarding email address(es)
        'unseen',           // Whether to keep a local copy when forwarding
        'maxmsgsize',       // Maximum allowed message size in bytes
        'quota',            // Mailbox storage quota in megabytes
        'realname',         // User's real/display name
        'sa_tag',           // SpamAssassin score threshold for tagging as spam
        'sa_refuse',        // SpamAssassin score threshold for rejecting email
        'tagline',          // Custom tagline to add to spam messages
        'vacation',         // Vacation message content
        'created_at',       // Account creation timestamp
        'updated_at',       // Last update timestamp
    ];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'crypt',            // Hide password hash from serialized output
        'remember_token',   // Hide remember token for cookie-based auth
    ];
    
    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'domain_id' => 'integer',
        'uid' => 'integer',
        'gid' => 'integer',
        'admin' => 'boolean',
        'on_avscan' => 'boolean',
        'on_blocklist' => 'boolean',
        'on_forward' => 'boolean',
        'on_piped' => 'boolean',
        'on_spamassassin' => 'boolean',
        'on_vacation' => 'boolean',
        'spam_drop' => 'boolean',
        'enabled' => 'boolean',
        'unseen' => 'boolean',
        'maxmsgsize' => 'integer',
        'quota' => 'integer',
        'sa_tag' => 'integer',
        'sa_refuse' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ]; 
    
    /**
     * Get the domain that owns this user account.
     * 
     * @return BelongsTo
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'domain_id');
    }
    
    /**
     * Get the password for authentication.
     * 
     * @return string|null
     */
    public function getAuthPassword()
    {
        return $this->crypt;
    }
    
    /**
     * Get the column name for authentication identifier.
     * 
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }
    
    /**
     * Get the unique identifier for authentication.
     * 
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getKey();
    }
    
    /**
     * Set the user's password.
     * 
     * Filament already sends a bcrypt hash, so we store it directly.
     * This method handles both bcrypt and legacy hash formats.
     * 
     * @param string $value The password (plain text or already hashed)
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        // Log that password is being changed BEFORE we do it
        /*
        if ($this->exists && isset($this->crypt) && $this->crypt !== $value) {
            activity()
                ->performedOn($this)
                ->causedBy(auth()->user())
                ->withProperties([
                    'event' => 'password_change',
                    'user_identifier' => $this->username,
                    'timestamp' => now()->toIso8601String()
                ])
                ->log('password_updated');
        }
        */
        
        // Store the actual hash
        $this->attributes['crypt'] = $value;
        
        Log::info('Password stored', [
            'user' => $this->username,
            'hash_prefix' => substr($value, 0, 10)
        ]);
    }
    
    /**
     * Verify a password against the stored hash.
     * 
     * Supports multiple hash formats:
     * - bcrypt hashes (modern, $2y$ prefix)
     * - SHA-512 crypt (legacy, $6$ prefix)
     * 
     * @param string $password The password to verify
     * @return bool
     */
    public function verifyPassword($password): bool
    {
        $hash = $this->crypt;

        Log::info('=== EXIMUSER VERIFY PASSWORD ===', [
            'stored_hash_prefix' => substr($hash, 0, 10),
            'password_length' => strlen($password),
            'is_bcrypt_hash' => str_starts_with($password, '$2y$')
        ]);

        // If the input password is already a bcrypt hash (from Filament login)
        if (str_starts_with($password, '$2y$')) {
            // Compare the hashes directly
            $result = hash_equals($hash, $password);
            Log::info('Direct hash comparison', ['result' => $result]);
            return $result;
        }

        // For old SHA-512 users (plain text password during login)
        if (str_starts_with($hash, '$6$')) {
            $result = crypt($password, $hash) === $hash;
            Log::info('SHA-512 crypt verification', ['result' => $result]);
            return $result;
        }

        // For bcrypt stored hashes with plain text password (shouldn't happen now)
        if (str_starts_with($hash, '$2y$')) {
            $result = password_verify($password, $hash);
            Log::info('BCrypt verification with plain text', ['result' => $result]);
            return $result;
        }

        return false;
    }
    
    /**
     * Scope query to only include enabled user accounts.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', 1);
    }
    
    /**
     * Scope query to only include local user accounts.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLocal($query)
    {
        return $query->where('type', 'local');
    }
    
    /**
     * Check if the user account is enabled.
     * 
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) $this->enabled;
    }
    
    /**
     * Get the email address attribute.
     * 
     * @return string
     */
    public function getEmailAttribute(): string
    {
        return $this->username;
    }
    
    /**
     * Get the display name attribute.
     * 
     * @return string
     */
    public function getNameAttribute(): string
    {
        return $this->realname ?: $this->username;
    }
    
    /**
     * Determine if the user can access the Filament admin panel.
     * 
     * @param Panel $panel
     * @return bool
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
    
    /**
     * Check if the user is a system administrator.
     * 
     * @return bool
     */
    public function isSystemAdmin(): bool
    {
        return false;
    }
    
    /**
     * Check if the user is a domain administrator.
     * 
     * @return bool
     */
    public function isDomainAdmin(): bool
    {
        return false;
    }
    
    /**
     * Check if the user is a regular domain user.
     * 
     * @return bool
     */
    public function isDomainUser(): bool
    {
        return true;
    }
    
    /**
     * Get the domain relationship for multi-tenancy.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function domains()
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'domain_id');
    }
    
    /**
     * Get the tenants (domains) that this user belongs to.
     * 
     * @param Panel $panel
     * @return Collection
     */
    public function getTenants(Panel $panel): Collection
    {
        if ($this->domain) {
            return collect([$this->domain]);
        }
        
        return collect();
    }
    
    /**
     * Check if the user can access a specific tenant (domain).
     * 
     * @param mixed $tenant
     * @return bool
     */
    public function canAccessTenant($tenant): bool
    {
        if (!$this->domain) {
            return false;
        }
        
        return $this->domain->getKey() === $tenant->getKey();
    }
    
    /**
     * Override the default table name.
     * 
     * @return string
     */
    public function getTable()
    {
        return 'users';
    }
    
    /**
     * Override the default key name.
     * 
     * @return string
     */
    public function getKeyName()
    {
        return 'user_id';
    }
    
    /**
     * Get the column name for app authentication secret.
     * 
     * @return string
     */
    public function getAppAuthenticationSecretColumn(): string
    {
        return 'app_authentication_secret';
    }
    
    /**
     * Check if app authentication (MFA) is enabled.
     * 
     * @return bool
     */
    public function isAppAuthenticationEnabled(): bool
    {
        return false;
    }

    /**
     * Build the timeline for activity logging.
     * 
     * @return TimelineBuilder
     */
    public function timeline(): TimelineBuilder
    {
        return TimelineBuilder::make($this)->fromActivityLog();
    }
    
    /**
     * Configure activity logging options.
     * 
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    // Also add setters for when Filament updates the fields
    public function setNameAttribute($value): void
    {
        $this->attributes['realname'] = $value;
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['username'] = $value;
    }   

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_contents', 'member_id', 'group_id')
            ->using(GroupContent::class)
            ->withTimestamps();
    }    
}