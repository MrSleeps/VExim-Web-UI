<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Relaticle\ActivityLog\Concerns\InteractsWithTimeline;
use Relaticle\ActivityLog\Contracts\HasTimeline;
use Relaticle\ActivityLog\Timeline\TimelineBuilder;
use App\Traits\LogsAllActivities;

/**
 * Manages application-wide configuration settings with caching support.
 * 
 * The Setting model provides a key-value store for dynamic configuration
 * settings that can be changed at runtime without modifying code. Settings
 * are automatically type-casted (integer, boolean, json, string) and cached
 * for optimal performance. Changes to settings automatically clear the cache.
 * 
 * LOGGING: Uses Spatie's activity logging system (matching other models):
 * - Logs all fillable attributes when changes occur
 * - Only records dirty changes (logOnlyDirty)
 * - Skips empty change logs (dontLogEmptyChanges)
 * - Supports timeline view via Relaticle Activity Log
 * 
 * Common use cases include:
 * - System configuration parameters
 * - Feature flags
 * - User-configurable application settings
 * - Dynamic constants that may change between deployments
 */
class Setting extends Model implements HasTimeline
{
    use LogsAllActivities;
    use InteractsWithTimeline;
    
    protected $table = 'vw_settings';
    
    protected $fillable = [
        'key',          // Unique identifier for the setting (e.g., 'site_name')
        'value',        // The setting value (stored as string, cast on retrieval)
        'type',         // Data type: 'string', 'integer', 'boolean', 'json'
        'description'   // Human-readable explanation of what this setting does
    ];
    
    protected $casts = [
        'type' => 'string',
    ];
 
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
     * Retrieve a setting value by its key.
     * 
     * @param string $key The setting key to retrieve
     * @param mixed $default Default value to return if setting doesn't exist
     * @return mixed The setting value with proper type casting
     */
    public static function get(string $key, $default = null)
    {
        $settings = static::getAllSettings();
        return $settings[$key] ?? $default;
    }
    
    /**
     * Store or update a setting value.
     * 
     * Automatically detects the appropriate data type based on the value
     * if not explicitly provided. Handles type casting and cache invalidation.
     * 
     * @param string $key The setting key to store
     * @param mixed $value The value to store
     * @param string|null $type Optional data type override
     * @param string|null $description Optional description of the setting
     * @return void
     */
    public static function set(string $key, $value, string $type = null, string $description = null): void
    {
        // Auto-detect type if not specified
        if (!$type) {
            $type = match(true) {
                is_int($value) => 'integer',
                is_bool($value) => 'boolean',
                is_array($value) => 'json',
                default => 'string'
            };
        }
        
        $storedValue = match($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value
        };
        
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
                'description' => $description
            ]
        );
        
        Cache::forget('settings.all');
    }
    
    /**
     * Retrieve all settings with proper type casting.
     * 
     * Results are cached for 1 hour to reduce database queries.
     * 
     * @return array Associative array of all settings [key => typed_value]
     */
    public static function getAllSettings(): array
    {
        return Cache::remember('settings.all', 3600, function () {
            $settings = [];
            $records = static::all();
            
            foreach ($records as $record) {
                $settings[$record->key] = $record->getTypedValue();
            }
            
            return $settings;
        });
    }
    
    /**
     * Get the typed value of this setting based on its type.
     * 
     * @return mixed The properly cast value
     */
    public function getTypedValue()
    {
        return match($this->type) {
            'integer' => (int) $this->value,
            'boolean' => (bool) $this->value,
            'json' => json_decode($this->value, true),
            default => $this->value
        };
    }
    
    /**
     * Magic accessor for typed value attribute.
     * 
     * @return mixed
     */
    public function getTypedValueAttribute()
    {
        return $this->getTypedValue();
    }
    
    /**
     * Manually clear the settings cache.
     * 
     * @return void
     */
    public static function clearCache(): void
    {
        Cache::forget('settings.all');
    }
    
    /**
     * Boot the model and set up event listeners.
     * 
     * Automatically clears cache when settings are saved or deleted.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saved(function () {
            static::clearCache();
        });
        
        static::deleted(function () {
            static::clearCache();
        });
    }
}