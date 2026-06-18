<?php

namespace App\Filament\Resources\DomainAliases;

use App\Filament\Resources\DomainAliases\Pages\CreateDomainAlias;
use App\Filament\Resources\DomainAliases\Pages\EditDomainAlias;
use App\Filament\Resources\DomainAliases\Pages\ListDomainAliases;
use App\Filament\Resources\DomainAliases\Schemas\DomainAliasForm;
use App\Filament\Resources\DomainAliases\Tables\DomainAliasesTable;
use App\Models\DomainAlias;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DomainAliasResource extends Resource
{
    protected static ?string $model = DomainAlias::class;
    
    protected static ?string $slug = 'domain-alias';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Link;

    protected static ?string $recordTitleAttribute = 'alias';
    
    protected static string|\UnitEnum|null $navigationGroup = 'Domain Management';
    
    protected static ?string $navigationLabel = 'Aliases';
    
    protected static ?int $navigationSort = 2;
    
    public static function form(Schema $schema): Schema
    {
        return DomainAliasForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DomainAliasesTable::configure($table);
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
            'index' => ListDomainAliases::route('/'),
            'create' => CreateDomainAlias::route('/create'),
            'edit' => EditDomainAlias::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        
        // System-admin sees all
        if ($user->isSystemAdmin()) {
            return (string) DomainAlias::count();
        }
        
        // Domain-admin sees only their domains' aliases
        if ($user->isDomainAdmin()) {
            $domainIds = $user->domains()->pluck('domains.domain_id');
            return (string) DomainAlias::whereIn('domain_id', $domainIds)->count();
        }
        
        // Domain-user sees no badge
        return null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
    
    public static function getNavigationBadgeTooltip(): ?string
    {
        $user = auth()->user();
        
        if ($user->isSystemAdmin()) {
            return 'Total number of aliases across all domains';
        }
        
        if ($user->isDomainAdmin()) {
            return 'Total number of aliases in your domains';
        }
        
        return null;
    }
    
    // Hide navigation item for domain-users
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        
        // Hide for domain-user
        if ($user->isDomainUser()) {
            return false;
        }
        
        return true;
    }
    
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        return parent::getEloquentQuery()->forUser($user);
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['alias'];
    }    
}