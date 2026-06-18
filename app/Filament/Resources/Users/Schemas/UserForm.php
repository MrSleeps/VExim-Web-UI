<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\Domain;
use App\Models\Setting;
use Rawilk\FilamentPasswordInput\Password;

class UserForm
{
    protected function getSettingValue($key, $default = 0)
    {
        return cache()->remember("setting.{$key}", 3600, function () use ($key, $default) {
            return \App\Models\Setting::where('key', $key)->value('value') ?? $default;
        });
    }    
    
    public static function configure(Schema $schema): Schema
    { 
        return $schema
            ->components([
                Section::make('User Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Toggle::make('email_verified')
                            ->label('Email Verified')
                            ->visible(fn (string $context): bool => $context === 'edit')
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $record) {
                                if ($state && !$record?->email_verified_at) {
                                    $set('email_verified_at', now());
                                } elseif (!$state && $record?->email_verified_at) {
                                    $set('email_verified_at', null);
                                }
                            })
                            ->formatStateUsing(fn ($record): bool => !is_null($record?->email_verified_at))
                            ->helperText('Toggle to manually verify or unverify this email address'),

                        DateTimePicker::make('email_verified_at')
                            ->hidden()
                            ->dehydrated(true),
                        Password::make('password')
                            ->copyable()
                            ->regeneratePassword(color: 'success')                      
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->columnSpanFull(), 
                        Toggle::make('active')
                            ->label('Account enabled')
                            ->required()
                            ->default(true)
                            ->columnSpanFull(),                        
                    ])->columns(2),
                    
                Section::make('Role Assignment')
                    ->schema([
                        Select::make('roles')
                            ->label('User Level / Role')
                            ->options(function () {
                                $user = auth()->user();
                                
                                // System admins see all roles
                                if ($user && $user->isSystemAdmin()) {
                                    return [
                                        'system_admin' => 'System Admin',
                                        'domain_admin' => 'Domain Admin'
                                    ];
                                }
                                
                                // Domain admins can only assign domain_admin role
                                if ($user && $user->isDomainAdmin()) {
                                    return [
                                        'domain_admin' => 'Domain Admin',
                                    ];
                                }
                                
                                return [];
                            })
                            ->required()
                            ->native(false)
                            ->live()
                            ->reactive()
                            ->helperText(function () {
                                $user = auth()->user();
                                if ($user && $user->isSystemAdmin()) {
                                    return 'System Admin: Full access to everything, Domain Admin: Access only to their assigned domains';
                                }
                                return 'domain_admin: Access only to their assigned domains';
                            })
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Force a refresh when role changes
                                $set('roles', $state);
                            })
                            ->loadStateFromRelationshipsUsing(function (Select $component, $record) {
                                if ($record) {
                                    $component->state($record->roles->first()?->name);
                                }
                            })
                            ->saveRelationshipsUsing(function ($record, $state) {
                                if ($record && filled($state)) {
                                    $record->syncRoles([$state]);
                                }
                            }),
                    ]),
                    
                Section::make('Domain Administration')
                    ->schema([
                        TextInput::make('max_domains')
                            ->label('Maximum number of domains')
                            ->default(function () {
                                return Setting::where('key', 'default_max_domains')->value('value') ?? 0;
                            })
                            ->required()
                            ->numeric()
                            ->helperText(' 0 for unlimited'),    
                        TextInput::make('max_alias_domains')
                            ->label('Maximum number of alias domains')
                            ->default(function () {
                                return Setting::where('key', 'default_max_alias_domains')->value('value') ?? 0;
                            })
                            ->required()
                            ->numeric()
                            ->helperText('0 for unlimited'),   
                        TextInput::make('max_accounts')
                            ->label('Maximum number of local email accounts')
                            ->default(function () {
                                return Setting::where('key', 'default_max_email_accounts')->value('value') ?? 0;
                            })
                            ->required()
                            ->numeric()
                            ->helperText('0 for unlimited'),   
                        TextInput::make('max_alias_accounts')
                            ->label('Maximum number of alias email accounts')
                            ->default(function () {
                                return Setting::where('key', 'default_max_alias_accounts')->value('value') ?? 0;
                            })
                            ->required()
                            ->numeric()
                            ->helperText('0 for unlimited'),   
                        TextInput::make('max_quota')
                            ->label('Maximum overall quota for all mailboxes in MB')
                            ->default(function () {
                                return Setting::where('key', 'default_max_quota')->value('value') ?? 0;
                            })
                            ->required()
                            ->numeric()
                            ->helperText('0 for unlimited'),                           
                        Select::make('domains')
                            ->label('Assigned Domains')
                            ->options(function () {
                                $user = auth()->user();
                                
                                // System admins can assign any domain
                                if ($user && $user->isSystemAdmin()) {
                                    return Domain::pluck('domain', 'domain_id');
                                }
                                
                                // Domain admins can only assign domains they manage
                                if ($user && $user->isDomainAdmin()) {
                                    return $user->domains()->pluck('domain', 'domain_id');
                                }
                                
                                return [];
                            })
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText(function () {
                                $user = auth()->user();
                                if ($user && $user->isSystemAdmin()) {
                                    return 'Select which domains this domain_admin can administer';
                                }
                                return 'Select which domains this domain_admin can manage (only domains you manage)';
                            })
                            ->loadStateFromRelationshipsUsing(function ($component, $record) {
                                if ($record) {
                                    $component->state($record->domains->pluck('domain_id')->toArray());
                                }
                            })
                            ->saveRelationshipsUsing(function ($record, $state) {
                                if ($record) {
                                    // When saving, attach with the correct pivot role
                                    $record->domains()->syncWithPivotValues(
                                        $state ?? [],
                                        ['role' => 'domain_admin']
                                    );
                                }
                            })->columnSpan(2),
                    ])
                    ->columns(2)
                    ->visible(fn ($get) => $get('roles') === 'domain_admin')
                    ->live()
                    ->reactive(),
            ]);
    }
}