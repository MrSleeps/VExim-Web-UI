<?php

namespace App\Filament\Resources\DomainUsers;

use App\Filament\Resources\DomainUsers\Pages\EditDomainUser;
use App\Filament\Resources\DomainUsers\Pages\ListDomainUsers;
use App\Filament\Resources\DomainUsers\Schemas\DomainUserForm;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\ViewEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use VEximweb\Core\Data\Models\EximUser;

class DomainUserResource extends Resource
{
    protected static ?string $model = EximUser::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::UserCircle;

    protected static ?string $recordTitleAttribute = 'username';

    protected static ?string $navigationLabel = 'My Email Account';

    protected static ?string $label = 'My Email Account';

    protected static ?string $pluralLabel = 'My Email Account';

    protected static ?int $navigationSort = -1;

    protected static bool $isGloballySearchable = false;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof EximUser) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereKey($user->getKey());
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof EximUser;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user instanceof EximUser;
    }

    public static function canEdit($record): bool
    {
        return static::isCurrentUserRecord($record);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canView($record): bool
    {
        return static::isCurrentUserRecord($record);
    }

    public static function getRecord(): ?EximUser
    {
        $user = auth()->user();

        return $user instanceof EximUser ? $user : null;
    }

    public static function form(Schema $schema): Schema
    {
        return DomainUserForm::configure($schema);
    }

    /**
     * ✅ Filament v5 Schema-based infolist
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Account Information')
                ->schema([
                    //
                ]),

            Section::make('Activity Timeline')
                ->schema([
                    RepeatableEntry::make('activities')
                        ->label('')
                        ->schema([
                            ViewEntry::make('activity_summary')
                                ->view('filament.infolists.components.activity-summary'),
                        ])
                        ->contained(false),
                ]),
        ]);
    }

    public static function afterSave($record, $data): void
    {
        if (! empty($data['password'])) {
            Notification::make()
                ->title('Password updated successfully')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Account updated successfully')
                ->success()
                ->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDomainUsers::route('/'),
            'edit' => EditDomainUser::route('/{record}/edit'),
        ];
    }

    public static function getUrl(
        ?string $name = 'edit',
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?Model $tenant = null,
        bool $shouldGuessMissingParameters = false,
        ?string $configuration = null
    ): string {
        if ($name === 'edit' && empty($parameters)) {
            $user = auth()->user();

            if ($user instanceof EximUser) {
                $parameters = ['record' => $user->getKey()];
            }
        }

        return parent::getUrl(
            $name,
            $parameters,
            $isAbsolute,
            $panel,
            $tenant,
            $shouldGuessMissingParameters,
            $configuration
        );
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl('index');
    }

    private static function isCurrentUserRecord(mixed $record): bool
    {
        $user = auth()->user();

        return $user instanceof EximUser
            && $record instanceof EximUser
            && $user->getKey() === $record->getKey();
    }
}
