<?php

namespace App\Traits;

use Spatie\Activitylog\Support\LogOptions; 
use Spatie\Activitylog\Contracts\Loggable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

/**
 * Trait for comprehensive activity logging using Spatie's LogsActivity.
 * 
 * This trait configures Spatie's activity logging to log ALL model attributes
 * (not just fillable ones) whenever changes are made. It only logs dirty
 * values (actual changes) and skips empty change logs.
 * 
 * Key features:
 * - logAll(): Logs every attribute on the model, regardless of fillable
 * - logOnlyDirty(): Only records attributes that actually changed
 * - dontLogEmptyChanges(): Prevents creating log entries when nothing changed
 * 
 * This is more comprehensive than using logFillable() alone, making it ideal
 * for security-sensitive models where complete audit trails are required.
 */
trait LogsAllActivities
{
    use LogsActivity;
    
    /**
     * Configure activity logging options.
     * 
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()              // Log ALL attributes, not just fillable
            ->logOnlyDirty()        // Only log attributes that actually changed
            ->dontLogEmptyChanges(); // Skip if no meaningful changes
    }
}