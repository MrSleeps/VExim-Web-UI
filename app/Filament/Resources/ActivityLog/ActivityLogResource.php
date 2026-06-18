<?php

namespace App\Filament\Resources\ActivityLog;

use App\Filament\Resources\ActivityLog\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLog\Pages\ViewActivityLog;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Clock;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Activity Log';

    protected static ?int $navigationSort = 99;

    protected static ?string $label = 'Activity';
    
    protected static ?string $pluralLabel = 'Activity Logs';

/**
 * Determine if the user can view any activity logs
 */
public static function canViewAny(): bool
{
    $user = auth()->user();
    if (!$user) {
        return false;
    }
    
    // System admins can always view
    if ($user->isSystemAdmin()) {
        return true;
    }
    
    // Domain admins can view (they'll see only their domain's activities)
    if ($user->isDomainAdmin()) {
        return true;
    }
    
    // Domain users can view (they'll see only their own activities)
    if ($user->isDomainUser()) {
        return true;
    }
    
    return false;
}

/**
 * Determine if the user can view a specific activity record
 * This is called after getEloquentQuery() filtering
 */
public static function canView(Model $record): bool
{
    $user = auth()->user();
    if (!$user) {
        return false;
    }
    
    // System admins can view any record
    if ($user->isSystemAdmin()) {
        return true;
    }
    
    // For domain admins, we need to check if they have access to this record
    // The query builder will already filter, but this is an extra security check
    if ($user->isDomainAdmin()) {
        $domainIds = $user->domains()->pluck('domains.domain_id')->toArray();
        
        // Check if the subject is an EximUser in their domains
        if ($record->subject_type === 'App\Models\EximUser') {
            $eximUser = \App\Models\EximUser::where('user_id', $record->subject_id)->first();
            if ($eximUser && in_array($eximUser->domain_id, $domainIds)) {
                return true;
            }
        }
        
        // Check if the subject is a Domain they own
        if ($record->subject_type === 'App\Models\Domain' && in_array($record->subject_id, $domainIds)) {
            return true;
        }
        
        // Check if they are the causer (performed the action)
        if ($record->causer_id == $user->getAuthIdentifier() && $record->causer_type === 'App\Models\User') {
            return true;
        }
        
        return false;
    }
    
    // Domain users can only view their own actions
    if ($user->isDomainUser()) {
        return $record->causer_id == $user->getAuthIdentifier() && $record->causer_type === 'App\Models\User';
    }
    
    return false;
}

    /**
     * Disable create/update/delete for activity logs
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

/**
 * Get the query builder with proper relationships and permissions
 */
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    $user = auth()->user();

    // Eager load the causer relationship
    $query->with(['causer']);
    
    // Order by most recent first
    $query->latest('created_at');

    if (!$user) {
        return $query->whereRaw('1 = 0');
    }

    // System admin sees everything
    if ($user->isSystemAdmin()) {
        return $query;
    }

    // Domain admin sees only activity related to their domains
    if ($user->isDomainAdmin()) {
        $domainIds = $user->domains()->pluck('domains.domain_id')->toArray();
        
        return $query->where(function (Builder $query) use ($domainIds, $user) {
            // Show activities where the subject is an EximUser or Domain in their domains
            $query->where(function (Builder $subQuery) use ($domainIds) {
                // For EximUser records
                $subQuery->where('subject_type', 'App\Models\EximUser')
                    ->whereHasMorph('subject', ['App\Models\EximUser'], function (Builder $q) use ($domainIds) {
                        $q->whereIn('domain_id', $domainIds);
                    });
            })->orWhere(function (Builder $subQuery) use ($domainIds) {
                // For Domain records
                $subQuery->where('subject_type', 'App\Models\Domain')
                    ->whereIn('subject_id', $domainIds);
            })->orWhere(function (Builder $subQuery) use ($user) {
                // Show their own actions (as causer)
                $subQuery->where('causer_id', $user->getAuthIdentifier())
                    ->where('causer_type', 'App\Models\User');
            });
        });
    }

    // Domain user sees only their own actions
    if ($user->isDomainUser()) {
        return $query->where(function (Builder $query) use ($user) {
            // Only show activities where they are the causer (the one who performed the action)
            $query->where('causer_id', $user->getAuthIdentifier())
                ->where('causer_type', 'App\Models\User');
        });
    }

    // No permission - return no records
    return $query->whereRaw('1 = 0');
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date/Time')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('causer.email')
                    ->label('User')
                    ->formatStateUsing(fn ($record) => $record->causer?->email ?? 'System')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('causer', function ($q) use ($search) {
                            $q->where('email', 'like', "%{$search}%");
                        });
                    }),
                
            Tables\Columns\TextColumn::make('description')
                ->label('Description')
                ->formatStateUsing(function ($record) {
                    $event = $record->event;
                    $subjectType = class_basename($record->subject_type);
                    $changes = $record->properties?->toArray() ?? [];
                    $identifier = self::extractIdentifier($record, $changes);

                    return match($event) {
                        'created' => match($subjectType) {
                            'EximUser' => "Created email account: {$identifier}",
                            'Domain' => "Created domain: {$identifier}",
                            default => "Created {$subjectType}: {$identifier}"
                        },
                        'deleted' => match($subjectType) {
                            'EximUser' => "Deleted email account: {$identifier}",
                            'Domain' => "Deleted domain: {$identifier}",
                            default => "Deleted {$subjectType}: {$identifier}"
                        },
                        'updated' => match($subjectType) {
                            'EximUser' => "Updated email account: {$identifier}" . (self::hasAnyChanges($changes) ? " - " . self::formatChanges($changes) : ''),
                            'Domain' => "Updated domain: {$identifier}" . (self::hasAnyChanges($changes) ? " - " . self::formatChanges($changes) : ''),
                            default => "Updated {$subjectType}: {$identifier}"
                        },
                        default => $record->description
                    };
                })
                ->searchable()
                ->wrap(),
                
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Subject Type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('Subject ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'login' => 'info',
                        'logout' => 'secondary',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('properties')
                    ->label('Properties')
                    ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Log Type')
                    ->options([
                        'default' => 'Default',
                        'user' => 'User',
                        'domain' => 'Domain',
                        'email' => 'Email',
                    ]),
                
                Tables\Filters\SelectFilter::make('event')
                    ->label('Event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'login' => 'Login',
                        'logout' => 'Logout',
                    ]),
                
                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('User')
                    ->options(fn () => User::pluck('name', 'id')->toArray()),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading('Activity Details')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->searchable()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
            'view' => ViewActivityLog::route('/{record}'),
        ];
    }
    
    /**
     * Extract the primary identifier from the activity log
     */
    private static function extractIdentifier($record, array $changes): string
    {
        $subjectType = class_basename($record->subject_type);

        // For EximUser (email accounts)
        if ($subjectType === 'EximUser') {
            // Check in attributes (for create/update)
            if (isset($changes['attributes']['username'])) {
                return $changes['attributes']['username'];
            }
            // Check in old attributes (for delete/update)
            if (isset($changes['old']['username'])) {
                return $changes['old']['username'];
            }

            // Try to load the model directly using user_id (since primary key is user_id, not id)
            if ($record->subject_id) {
                try {
                    // The subject_id in activity_log corresponds to user_id in EximUser table
                    $model = \App\Models\EximUser::where('user_id', $record->subject_id)->first();
                    if ($model && $model->username) {
                        return $model->username;
                    }
                } catch (\Exception $e) {
                    // Model might be deleted
                }
            }

            // Try to build from localpart if available
            foreach (['attributes', 'old'] as $key) {
                if (isset($changes[$key]['localpart'])) {
                    // Get the domain name if domain_id is available
                    $domain = '';
                    if (isset($changes[$key]['domain_id'])) {
                        try {
                            $domainModel = \App\Models\Domain::where('domain_id', $changes[$key]['domain_id'])->first();
                            if ($domainModel && $domainModel->domain) {
                                $domain = '@' . $domainModel->domain;
                            }
                        } catch (\Exception $e) {
                            $domain = ' (domain ID: ' . $changes[$key]['domain_id'] . ')';
                        }
                    }
                    return $changes[$key]['localpart'] . $domain;
                }
            }

            // Fallback to subject_id with note that it's user_id
            return "User ID: {$record->subject_id}";
        }

        // For Domain
        if ($subjectType === 'Domain') {
            if (isset($changes['attributes']['domain'])) {
                return $changes['attributes']['domain'];
            }
            if (isset($changes['old']['domain'])) {
                return $changes['old']['domain'];
            }

            if ($record->subject_id) {
                try {
                    $model = \App\Models\Domain::where('domain_id', $record->subject_id)->first();
                    if ($model && $model->domain) {
                        return $model->domain;
                    }
                } catch (\Exception $e) {
                    // Model might not exist
                }
            }

            return "Domain ID: {$record->subject_id}";
        }

        // Default fallback
        return "ID: {$record->subject_id}";
    }

    /**
     * Check if there are any meaningful changes
     */
    private static function hasAnyChanges(array $changes): bool
    {
        return !empty($changes['attributes']) || !empty($changes['old']);
    }

    /**
     * Format the changes made during an update
     */
    private static function formatChanges(array $changes): string
    {
        $changedFields = [];

        // Get changed attributes
        $attributes = $changes['attributes'] ?? [];
        $old = $changes['old'] ?? [];

        // Define friendly field names
        $fieldLabels = [
            'smtp' => 'forwarding address',
            'localpart' => 'local part',
            'username' => 'username',
            'type' => 'type',
            'enabled' => 'status',
            'forward' => 'forwarding',
            'quota' => 'quota',
            'maxmsgsize' => 'max message size',
            'updated_at' => 'last updated',
            'created_at' => 'created',
        ];

        foreach ($attributes as $field => $newValue) {
            if (isset($old[$field]) && $old[$field] != $newValue) {
                $label = $fieldLabels[$field] ?? $field;
                $oldValue = self::formatValue($old[$field]);
                $newValue = self::formatValue($newValue);
                $changedFields[] = "{$label}: {$oldValue} → {$newValue}";
            } elseif (!isset($old[$field]) && !empty($newValue) && $field !== 'updated_at') {
                $label = $fieldLabels[$field] ?? $field;
                $changedFields[] = "added {$label}: " . self::formatValue($newValue);
            }
        }

        return empty($changedFields) 
            ? 'changed settings' 
            : implode(', ', array_slice($changedFields, 0, 3)) . (count($changedFields) > 3 ? '...' : '');
    }

    /**
     * Format values for display
     */
    private static function formatValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if ($value === null || $value === '') {
            return 'empty';
        }

        if (is_string($value) && strlen($value) > 50) {
            return substr($value, 0, 47) . '...';
        }

        return (string) $value;
    }
}