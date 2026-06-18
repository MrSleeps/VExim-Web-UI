<?php

namespace App\Filament\Resources\EximCatchall;

use App\Filament\Resources\EximCatchall\Pages\CreateEximCatchall;
use App\Filament\Resources\EximCatchall\Pages\EditEximCatchall;
use App\Filament\Resources\EximCatchall\Pages\ListEximCatchall;
use App\Filament\Resources\EximCatchall\Pages\ViewEximCatchall;
use App\Filament\Resources\EximCatchall\Schemas\EximCatchallForm;
use App\Filament\Resources\EximCatchall\Tables\EximCatchallTable;
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

class EximCatchallResource extends Resource
{
    protected static ?string $model = EximUser::class;
    
    protected static ?string $slug = 'accounts/email-catchall';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Funnel;
    
    protected static ?string $navigationLabel = 'Catch all';
    
    protected static ?string $label = 'Catchall';
    
    protected static ?string $pluralLabel = 'Catch all addresses';
    
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

        // Only show catchall records
        $query->where('type', 'catch');

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // System admins see all aliases
        if ($user->isSystemAdmin()) {
            return $query;
        }

        // Domain admins only see catchalls in their assigned domains
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
            return $user->domains()->where('domains.domain_id', $record->domain_id)->exists();
        }

        return false;
    }

    /**
     * Only system admins can delete
     */
    public static function canDelete($record): bool
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
                $count = static::getModel()::where('type', 'catch')->count();
            } elseif ($user->isDomainAdmin()) {
                $domainIds = $user->domains()->pluck('domains.domain_id');
                $count = static::getModel()::where('type', 'catch')
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
        return EximCatchallForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EximCatchallTable::configure($table);
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
            'index' => ListEximCatchall::route('/'),
            'create' => CreateEximCatchall::route('/create'),
            'edit' => EditEximCatchall::route('/{record}/edit'),
            'view' => ViewEximCatchall::route('/{record}')
        ];
    }

    /**
     * Process forwarding destination before saving
     */
    private static function processForwardingDestination(array $data): array
    {
        if ($data['smtp_selection'] === 'other') {
            $data['smtp'] = $data['custom_smtp'];
            $data['pop'] = $data['custom_smtp'];
        } else {
            $data['smtp'] = $data['smtp_selection'];
            $data['pop'] = $data['smtp_selection'];
        }

        unset($data['smtp_selection'], $data['custom_smtp']);
        
        return $data;
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        return static::processForwardingDestination($data);
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        return static::processForwardingDestination($data);
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
                        TextEntry::make('domain.domain')
                            ->label('Domain')
                            ->helperText('Catchall address for this domain (anything@domain will be caught)'),

                        TextEntry::make('smtp')
                            ->label('Forwards To'),

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

    public static function getRecordTitle($record): string
    {
        return $record->domain->domain ." catchall" ?? 'Catchall';
    }    
}