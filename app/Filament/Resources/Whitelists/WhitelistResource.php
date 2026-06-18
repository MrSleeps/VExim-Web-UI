<?php

namespace App\Filament\Resources\Whitelists;

use App\Filament\Resources\Whitelists\Pages\CreateWhitelist;
use App\Filament\Resources\Whitelists\Pages\EditWhitelist;
use App\Filament\Resources\Whitelists\Pages\ListWhitelists;
use App\Filament\Resources\Whitelists\Schemas\WhitelistForm;
use App\Filament\Resources\Whitelists\Tables\WhitelistsTable;
use App\Models\Whitelist;
use App\Models\EximUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WhitelistResource extends Resource
{
    protected static ?string $model = Whitelist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|\UnitEnum|null $navigationGroup = 'Lists';
    
    protected static ?string $navigationLabel = 'Whitelist';
    
    protected static ?string $recordTitleAttribute = 'Whitelist';
    
    protected static ?int $navigationSort = 30;

    /**
     * Get the navigation badge count based on user role
     */
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        
        if (!$user) {
            return null;
        }
        
        // System admin - count all whitelist entries
        if ($user->isSystemAdmin()) {
            return (string) Whitelist::count();
        }
        
        // Domain admin - count whitelist entries for all their domains
        if ($user->isDomainAdmin()) {
            $domainIds = $user->domains->pluck('domain_id'); // FIXED: Use collection
            return (string) Whitelist::whereIn('domain_id', $domainIds)->count();
        }
        
        // Domain user - count only their own whitelist entries
        if ($user->isDomainUser()) {
            $eximUser = EximUser::where('username', $user->email)
                ->whereIn('type', ['local', 'alias', 'catch'])
                ->first();
            
            if ($eximUser) {
                return (string) Whitelist::where('domain_id', $eximUser->domain_id)
                    ->where('localpart', $eximUser->localpart)
                    ->count();
            }
        }
        
        return null;
    }

    public static function form(Schema $schema): Schema
    {
        return WhitelistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhitelistsTable::configure($table);
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
            'index' => ListWhitelists::route('/'),
            'create' => CreateWhitelist::route('/create'),
            'edit' => EditWhitelist::route('/{record}/edit'),
        ];
    }

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

        // System admin can see all whitelist entries
        if ($user->isSystemAdmin()) {
            return $query;
        }

        // Domain admin can see whitelist entries for their domains only
        if ($user->isDomainAdmin()) {
            $domainIds = $user->domains->pluck('domain_id');
            return $query->whereIn('domain_id', $domainIds);
        }

        // Domain user can see whitelist entries for their own user only
        if ($user->isDomainUser()) {
            $eximUser = EximUser::where('username', $user->email)
                ->whereIn('type', ['local', 'alias', 'catch'])
                ->first();
            
            if ($eximUser) {
                return $query->where('domain_id', $eximUser->domain_id)
                    ->where('localpart', $eximUser->localpart);
            }
            
            return $query->whereRaw('1 = 0');
        }
        return $query->whereRaw('1 = 0');
    }

    /**
     * Determine who can create whitelist entries
     */
    public static function canCreate(): bool
    {
        $user = auth()->user();

        if (!$user) return false;

        // Allow all authenticated users (system_admin, domain_admin, domain-user)
        return $user->isSystemAdmin() || $user->isDomainAdmin() || $user->isDomainUser();
    }

    /**
     * Determine who can edit whitelist entries
     */
    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if (!$user) return false;

        // System admin can edit any
        if ($user->isSystemAdmin()) return true;

        // Domain admin can edit entries for their domains
        if ($user->isDomainAdmin()) {
            // FIXED: Use collection contains instead of query builder
            return $user->domains->contains('domain_id', $record->domain_id);
        }

        // Domain user can edit their own entries
        if ($user->isDomainUser()) {
            $eximUser = EximUser::where('username', $user->email)
                ->whereIn('type', ['local', 'alias', 'catch'])
                ->first();
            
            if ($eximUser) {
                return $record->domain_id === $eximUser->domain_id && 
                       $record->localpart === $eximUser->localpart;
            }
        }

        return false;
    }

    /**
     * Determine who can delete whitelist entries
     */
    public static function canDelete($record): bool
    {
        $user = auth()->user();

        if (!$user) return false;

        // System admin can delete any
        if ($user->isSystemAdmin()) return true;

        // Domain admin can delete entries for their domains
        if ($user->isDomainAdmin()) {
            // FIXED: Use collection contains instead of query builder
            return $user->domains->contains('domain_id', $record->domain_id);
        }

        // Domain user can delete their own entries
        if ($user->isDomainUser()) {
            $eximUser = EximUser::where('username', $user->email)
                ->whereIn('type', ['local', 'alias', 'catch'])
                ->first();
            
            if ($eximUser) {
                return $record->domain_id === $eximUser->domain_id && 
                       $record->localpart === $eximUser->localpart;
            }
        }

        return false;
    }

    /**
     * Determine if records can be bulk deleted
     */
    public static function canDeleteAny(): bool
    {
        $user = auth()->user();

        if (!$user) return false;

        // System admin and domain admin can bulk delete
        // Domain users cannot bulk delete (only single delete)
        return $user->isSystemAdmin() || $user->isDomainAdmin();
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
                       $record->localpart === $eximUser->localpart;
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
        
        // Domain admins can view whitelist resource
        if ($user->isDomainAdmin()) {
            return true;
        }
        
        // Domain users can view whitelist resource
        if ($user->isDomainUser()) {
            return true;
        }
        
        return false;
    }
    
    public static function afterDelete($record): void
    {
        cache()->forget('filament.navigation.items');
        cache()->forget('filament.resources.' . static::class);
    }    
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['domain.domain','localpart','sender'];
    }
}