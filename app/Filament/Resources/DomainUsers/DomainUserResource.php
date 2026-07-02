<?php

namespace App\Filament\Resources\DomainUsers;

use VEximweb\Core\Data\Models\EximUser;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\RepeatableEntry;
use Filament\Schemas\Components\ViewEntry;
use Filament\Support\Icons\Heroicon;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\DomainUsers\Schemas\DomainUserForm;

class DomainUserResource extends Resource
{
    protected static ?string $model = EximUser::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::UserCircle;
    
    protected static ?string $recordTitleAttribute = 'username';

    protected static ?string $navigationLabel = 'My Email Account';

    protected static ?string $label = 'My Email Account';

    protected static ?string $pluralLabel = 'My Email Account';

    protected static ?int $navigationSort = -1;

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
        $user = auth()->user();
        return $user instanceof EximUser && $user->getKey() === $record->getKey();
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
        return true;
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
        if (!empty($data['password'])) {
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
            'index' => \App\Filament\Resources\DomainUsers\Pages\ListDomainUsers::route('/'),
            'edit' => \App\Filament\Resources\DomainUsers\Pages\EditDomainUser::route('/{record}/edit'),
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
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }     
}