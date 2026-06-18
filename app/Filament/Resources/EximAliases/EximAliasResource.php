<?php

namespace App\Filament\Resources\EximAliases;

use App\Filament\Resources\EximAliases\Pages\CreateEximAlias;
use App\Filament\Resources\EximAliases\Pages\EditEximAlias;
use App\Filament\Resources\EximAliases\Pages\ListEximAliases;
use App\Filament\Resources\EximAliases\Pages\ViewEximAlias;
use App\Filament\Resources\EximAliases\Schemas\EximAliasForm;
use App\Filament\Resources\EximAliases\Tables\EximAliasesTable;
use App\Models\EximUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Relaticle\ActivityLog\Filament\Infolists\Components\ActivityLog;

class EximAliasResource extends Resource
{
    protected static ?string $model = EximUser::class;
    
    protected static ?string $slug = 'accounts/email-forwarding';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowUturnRight;
    
    protected static ?string $navigationLabel = 'Forwarding';
    
    protected static ?string $label = 'Email Forwarder';
    
    protected static ?string $pluralLabel = 'Email Forwarding';
    
    protected static string|\UnitEnum|null $navigationGroup = 'Account Management';
    protected static ?int $navigationGroupSort = 2;
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'username';

    /**
     * Only show records where type = 'alias'
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Only show alias records
        $query->where('type', 'alias');

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // System admins see all aliases
        if ($user->isSystemAdmin()) {
            return $query;
        }

        // Domain admins only see aliases in their assigned domains
        if ($user->isDomainAdmin()) {
            $domainIds = $user->domains()->pluck('domains.domain_id');
            return $query->whereIn('users.domain_id', $domainIds);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Only system admins and domain admins can create
     */
    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSystemAdmin() || $user->isDomainAdmin());
    }

    /**
     * Only system admins and domain admins can edit
     */
    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if (!$user) return false;

        if ($user->isSystemAdmin()) return true;

        if ($user->isDomainAdmin()) {
            // Can only edit aliases in their assigned domains
            return $user->domains()->where('domains.domain_id', $record->domain_id)->exists();
        }

        return false;
    }

    /**
     * Only system admins can delete
     */
    public static function canDelete($record): bool
    {
        return auth()->user() && auth()->user()->isSystemAdmin();
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
                $count = static::getModel()::where('type', 'alias')->count();
            } elseif ($user->isDomainAdmin()) {
                $domainIds = $user->domains()->pluck('domains.domain_id');
                $count = static::getModel()::where('type', 'alias')
                    ->whereIn('users.domain_id', $domainIds)
                    ->count();
            } else {
                return null;
            }

            return $count > 0 ? (string) $count : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Only register navigation for authorized users
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSystemAdmin() || $user->isDomainAdmin());
    }

    public static function form(Schema $schema): Schema
    {
        return EximAliasForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EximAliasesTable::configure($table);
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
            'index' => ListEximAliases::route('/'),
            'create' => CreateEximAlias::route('/create'),
            'edit' => EditEximAlias::route('/{record}/edit'),
            'view' => ViewEximAlias::route('/{record}')
        ];
    }
    /**
     * This is where the timeline goes - in the infolist() method
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Details')
                    ->schema([
                        TextEntry::make('username')
                            ->label('Username'),
                        TextEntry::make('domain.domain')
                            ->label('Domain'),
                        TextEntry::make('localpart')
                            ->label('Local Part'),
                        TextEntry::make('quota')
                            ->label('Quota (MB)'),
                        TextEntry::make('enabled')
                            ->label('Enabled')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                    ])
                    ->columns(2),
                
                ActivityLog::make('timeline')
                    ->heading('Activity History')
                    ->groupByDate()
                    ->perPage(20)
                    ->columnSpanFull(),
            ]);
    }      
}