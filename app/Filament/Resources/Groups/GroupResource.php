<?php

namespace App\Filament\Resources\Groups;

use App\Filament\Resources\Groups\Pages\CreateGroup;
use App\Filament\Resources\Groups\Pages\EditGroup;
use App\Filament\Resources\Groups\Pages\ListGroups;
use App\Filament\Resources\Groups\Schemas\GroupForm;
use App\Filament\Resources\Groups\Tables\GroupsTable;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Group;
use App\Models\Domain;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    
    protected static string|\UnitEnum|null $navigationGroup = 'Account Management';
    
    protected static ?string $navigationLabel = 'Groups';
    
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return GroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GroupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('domain');
        $user = auth()->user();
        
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }
        
        if ($user->isSystemAdmin()) {
            return $query;
        }
        
        if ($user->isDomainAdmin()) {
            $domainIds = Domain::whereHas('administrators', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('role', 'domain_admin');
            })->pluck('domain_id');
            
            return $query->whereIn('domain_id', $domainIds);
        }
        
        return $query->whereRaw('1 = 0');
    }
    
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        
        if (!$user) {
            return null;
        }

        if ($user->isSystemAdmin()) {
            return (string) Group::count();
        }
        
        if ($user->isDomainAdmin()) {
            $domainIds = Domain::whereHas('administrators', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('role', 'domain_admin');
            })->pluck('domain_id');
            
            $count = Group::whereIn('domain_id', $domainIds)->count();
            return (string) $count;
        }
        
        // Domain-user sees nothing
        return null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
    
    public static function getNavigationBadgeTooltip(): ?string
    {
        $user = auth()->user();
        
        if (!$user) {
            return null;
        }
        
        if ($user->isSystemAdmin()) {
            return 'Total number of groups in the system';
        }
        
        if ($user->isDomainAdmin()) {
            return 'Total number of groups in domains you administer';
        }
        
        return null;
    }
    
    // Hide navigation item for domain-users
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (!$user || $user->isDomainUser()) {
            return false;
        }

        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGroups::route('/'),
            'create' => CreateGroup::route('/create'),
            'edit' => EditGroup::route('/{record}/edit'),
        ];
    }
}