<?php

namespace App\Filament\Resources\EximUsers;

use App\Filament\Resources\EximUsers\Pages\CreateEximUser;
use App\Filament\Resources\EximUsers\Pages\EditEximUser;
use App\Filament\Resources\EximUsers\Pages\ListEximUsers;
use App\Filament\Resources\EximUsers\Pages\ViewEximUser;
use App\Filament\Resources\EximUsers\Schemas\EximUserForm;
use App\Filament\Resources\EximUsers\Tables\EximUsersTable;
use App\Models\EximUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\ViewEntry;

class EximUserResource extends Resource
{
    protected static ?string $model = EximUser::class;
    
    protected static ?string $slug = 'accounts/email';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Envelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Account Management';

    protected static ?string $navigationLabel = 'Local';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'username';
    
    protected static ?string $label = 'Email Account';
    protected static ?string $pluralLabel = 'Email Accounts';

    /**
     * Only show records where type = 'local'
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        $query->where('type', 'local');

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSystemAdmin()) {
            return $query;
        }

        if ($user->isDomainAdmin()) {
            $domainIds = $user->domains()->pluck('domains.domain_id');
            return $query->whereIn('domain_id', $domainIds);
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
            return $user->domains()->where('domain_id', $record->domain_id)->exists();
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
                $count = static::getModel()::where('type', 'local')->count();
            } elseif ($user->isDomainAdmin()) {
                $domainIds = $user->domains()->pluck('domains.domain_id');
                $count = static::getModel()::where('type', 'local')
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
        return EximUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EximUsersTable::configure($table);
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
            'index' => ListEximUsers::route('/'),
            'create' => CreateEximUser::route('/create'),
            'edit' => EditEximUser::route('/{record}/edit'),
            'view' => ViewEximUser::route('/{record}')
        ];
    }
    
    /**
     * This is where the timeline goes - using custom view instead of ActivityLog component
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
                
                Section::make('Activity History')
                    ->heading('Activity History')
                    ->schema([
                        RepeatableEntry::make('activities')
                            ->label('')
                            ->schema([
                                ViewEntry::make('activity_summary')
                                    ->view('filament.infolists.components.activity-summary')
                                    ->state(function ($record) {
                                        return $record;
                                    })
                            ])
                            ->contained(false)
                            ->columnSpanFull()
                            ->default(function ($record) {
                                return $record->activities()->latest()->get();
                            })
                    ])
                    ->columnSpanFull(),
            ]);
    }    
}