<?php

namespace App\Filament\Resources\EximFails;

use App\Filament\Resources\EximFails\Pages\CreateEximFail;
use App\Filament\Resources\EximFails\Pages\EditEximFail;
use App\Filament\Resources\EximFails\Pages\ListEximFails;
use App\Filament\Resources\EximFails\Schemas\EximFailForm;
use App\Filament\Resources\EximFails\Tables\EximFailsTable;
use App\Models\EximUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Icons\Heroicon;

class EximFailResource extends Resource
{
    protected static ?string $model = EximUser::class;
    
    protected static ?string $modelLabel = 'Fail Account';
    protected static ?string $pluralModelLabel = 'Fail Accounts';
    
    protected static ?string $slug = 'accounts/email-fail';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ExclamationTriangle;
    
    protected static ?string $navigationLabel = 'Fail';
    
    protected static ?string $pluralLabel = 'Fail Accounts';
    
    protected static string|\UnitEnum|null $navigationGroup = 'Account Management';
    
    protected static ?int $navigationSort = 4;

    /**
     * Filter the query to only show records where type = 'fail'
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Only show records with type 'fail'
        $query->where('type', 'fail');
        
        // Apply user permissions (system admin vs domain admin)
        $user = auth()->user();
        
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }
        
        if ($user->isSystemAdmin()) {
            // System admin sees all failed messages
            return $query;
        }
        
        if ($user->isDomainAdmin()) {
            // Domain admin only sees failed messages in their domains
            $domainIds = $user->domains()->pluck('domains.domain_id');
            return $query->whereIn('domain_id', $domainIds);
        }
        
        // Regular users see nothing
        return $query->whereRaw('1 = 0');
    }

    /**
     * Define which columns are shown in the table
     */
    public static function table(Table $table): Table
    {
        return EximFailsTable::configure($table);
    }

    /**
     * Define the form for creating/editing
     */
    public static function form(Schema $schema): Schema
    {
        return EximFailForm::configure($schema);
    }

    /**
     * Define the page routes
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEximFails::route('/'),
            'create' => Pages\CreateEximFail::route('/create'),
            'edit' => Pages\EditEximFail::route('/{record}/edit'),
        ];
    }
    
    /**
     * Only show navigation for authorized users
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSystemAdmin() || $user->isDomainAdmin());
    }
    
    /**
     * Show badge with count
     */
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        try {
            if ($user->isSystemAdmin()) {
                $count = static::getModel()::where('type', 'fail')->count();
            } elseif ($user->isDomainAdmin()) {
                $domainIds = $user->domains()->pluck('domains.domain_id');
                $count = static::getModel()::where('type', 'fail')
                    ->whereIn('users.domain_id', $domainIds)
                    ->count();
            } else {
                return null;
            }

            return $count > 0 ? (string) $count : null;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Navigation badge error: ' . $e->getMessage());
            return null;
        }
    }    
}