<?php

namespace App\Filament\Resources\Blocklists;

use App\Filament\Resources\Blocklists\Pages\CreateBlocklist;
use App\Filament\Resources\Blocklists\Pages\EditBlocklist;
use App\Filament\Resources\Blocklists\Pages\ListBlocklists;
use App\Filament\Resources\Blocklists\Schemas\BlocklistForm;
use App\Filament\Resources\Blocklists\Tables\BlocklistsTable;
use App\Models\Blocklist;
use App\Models\EximUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlocklistResource extends Resource
{
    protected static ?string $model = Blocklist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|\UnitEnum|null $navigationGroup = 'Lists';
    
    protected static ?string $navigationLabel = 'Blocklist';
    
    protected static ?string $label = 'Blocklist entry';
    
    protected static ?string $recordTitleAttribute = 'Blocklist';
    
    protected static ?int $navigationSort = 30;

    /**
     * Apply permissions to the query based on user role
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // System admin can see all blocklist entries
        if ($user->isSystemAdmin()) {
            return $query;
        }

        // Domain admin can see blocklist entries for their domains only
        if ($user->isDomainAdmin()) {
            $domainIds = $user->domains->pluck('domain_id');
            return $query->whereIn('domain_id', $domainIds);
        }

        // Domain user can see blocklist entries for their own user only
        if ($user->isDomainUser()) {
            // Get the user's exim user record to find their localpart
            $eximUser = EximUser::where('username', $user->email)
                ->whereIn('type', ['local', 'alias', 'catch'])
                ->first();
            
            if ($eximUser) {
                return $query->where('domain_id', $eximUser->domain_id)
                    ->where('user_id', $eximUser->user_id);
            }
            
            return $query->whereRaw('1 = 0');
        }

        // Default: no access
        return $query->whereRaw('1 = 0');
    }

    /**
     * Determine who can create blocklist entries
     */
    public static function canCreate(): bool
    {
        $user = auth()->user();
        
        if (!$user) return false;
        
        // System admins and domain admins can create
        if ($user->isSystemAdmin() || $user->isDomainAdmin()) {
            return true;
        }
        
        // Domain users can create their own entries
        if ($user->isDomainUser()) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine who can edit blocklist entries
     */
    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if (!$user) return false;

        // System admin can edit any
        if ($user->isSystemAdmin()) return true;

        // Domain admin can edit entries for their domains
        if ($user->isDomainAdmin()) {
            return $user->domains->contains('domain_id', $record->domain_id);
        }

        // Domain user can edit their own entries
        if ($user->isDomainUser()) {
            $eximUser = EximUser::where('username', $user->email)
                ->whereIn('type', ['local', 'alias', 'catch'])
                ->first();
            
            if ($eximUser) {
                return $record->domain_id === $eximUser->domain_id && 
                       $record->user_id === $eximUser->user_id;
            }
        }

        return false;
    }

    /**
     * Determine who can delete blocklist entries
     */
    public static function canDelete($record): bool
    {
        $user = auth()->user();

        if (!$user) return false;

        // System admin can delete any
        if ($user->isSystemAdmin()) return true;

        // Domain admin can delete entries for their domains
        if ($user->isDomainAdmin()) {
            return $user->domains->contains('domain_id', $record->domain_id);
        }

        // Domain user can delete their own entries
        if ($user->isDomainUser()) {
            $eximUser = EximUser::where('username', $user->email)
                ->whereIn('type', ['local', 'alias', 'catch'])
                ->first();
            
            if ($eximUser) {
                return $record->domain_id === $eximUser->domain_id && 
                       $record->user_id === $eximUser->user_id;
            }
        }

        return false;
    }

    /**
     * Determine if a record can be viewed
     */
    public static function canView($record): bool
    {
        $user = auth()->user();

        if (!$user) return false;

        // System admin can view any
        if ($user->isSystemAdmin()) return true;

        // Domain admin can view entries for their domains
        if ($user->isDomainAdmin()) {
            return $user->domains->contains('domain_id', $record->domain_id);
        }

        // Domain user can view their own entries
        if ($user->isDomainUser()) {
            $eximUser = EximUser::where('username', $user->email)
                ->whereIn('type', ['local', 'alias', 'catch'])
                ->first();
            
            if ($eximUser) {
                return $record->domain_id === $eximUser->domain_id && 
                       $record->user_id === $eximUser->user_id;
            }
        }

        return false;
    }

    /**
     * Control if the resource shows up in navigation
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        
        if (!$user) return false;
        
        // System admins can always view
        if ($user->isSystemAdmin()) {
            return true;
        }
        
        // Domain admins can view blocklist resource
        if ($user->isDomainAdmin()) {
            return true;
        }
        
        // Domain users can view blocklist resource
        if ($user->isDomainUser()) {
            return true;
        }
        
        return false;
    }

    /**
     * Add navigation badge with counts
     */
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        
        if (!$user) return null;
        
        if ($user->isSystemAdmin()) {
            return (string) Blocklist::count();
        }
        
        if ($user->isDomainAdmin()) {
            $domainIds = $user->domains->pluck('domain_id');
            return (string) Blocklist::whereIn('domain_id', $domainIds)->count();
        }
        
        if ($user->isDomainUser()) {
            $eximUser = EximUser::where('username', $user->email)
                ->whereIn('type', ['local', 'alias', 'catch'])
                ->first();
            
            if ($eximUser) {
                return (string) Blocklist::where('domain_id', $eximUser->domain_id)
                    ->where('user_id', $eximUser->user_id)
                    ->count();
            }
        }
        
        return null;
    }

    public static function form(Schema $schema): Schema
    {
        return BlocklistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlocklistsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlocklists::route('/'),
            'create' => CreateBlocklist::route('/create'),
            'edit' => EditBlocklist::route('/{record}/edit'),
        ];
    }
    
    public static function afterDelete($record): void
    {
        cache()->forget('filament.navigation.items');
        cache()->forget('filament.resources.' . static::class);
    }    
    
    public static function canGloballySearch(): bool
    {
        return false;
    }    
}