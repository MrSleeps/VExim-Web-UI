<?php

namespace App\Filament\Resources\EximUsers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use App\Models\Domain;
use App\Models\Setting;
use Rawilk\FilamentPasswordInput\Password;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class EximUserForm
{
	
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $isSystemAdmin = $user->isSystemAdmin();
        $isDomainAdmin = $user->isDomainAdmin();
        $isCreating = $schema->getRecord() === null;
        $mailRoot = Setting::get('mail_root', '/var/mail/vexim');
        $mailRoot = rtrim($mailRoot, '/');
        
        return $schema
            ->columns(2)
            ->components([
                Section::make('Account Information')
                    ->schema([
                        TextInput::make('localpart')
                            ->label('Email Address')
                            ->placeholder('sales')
                            ->required()
                            ->maxLength(64)
                            ->helperText('The part before the @')
                            ->regex('/^[a-zA-Z0-9._-]+$/')
                            ->validationMessages([
                                'regex' => 'Only letters, numbers, dots, underscores, and hyphens are allowed.',
                            ])
                            ->suffix('@')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) use ($mailRoot) {
                                $domainId = $get('domain_id');
                                if ($domainId && $state) {
                                    $domain = Domain::find($domainId);
                                    if ($domain) {
                                        $fullEmail = $state . '@' . $domain->domain;
                                        $set('username', $fullEmail);
                                        $smtpPath = $mailRoot . '/' . $domain->domain . '/' . $state . '/Maildir';
                                        $set('smtp', $smtpPath);
                                        $popPath = $mailRoot . '/' . $domain->domain . '/' . $state;
                                        $set('pop', $popPath);
                                    }
                                }
                            })
                            ->columnSpan(1),
                        
                        Select::make('domain_id')
                            ->label('Domain')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()

                            ->options(function () use ($isSystemAdmin, $isDomainAdmin) {

                                $limitService = app(\App\Services\DomainAdminLimitService::class);

                                $getDomains = function () use ($isSystemAdmin, $isDomainAdmin) {

                                    if ($isSystemAdmin) {
                                        return Domain::where('enabled', true)->get();
                                    }

                                    if ($isDomainAdmin) {
                                        return auth()->user()->domains()
                                            ->where('enabled', true)
                                            ->get();
                                    }

                                    return collect();
                                };

                                return $getDomains()
                                    ->mapWithKeys(function ($domain) use ($limitService, $isDomainAdmin) {

                                        $stats = $limitService->getDomainAccountStats($domain->domain_id);

                                        $label = $domain->domain;

                                        if ($stats['is_full'] && $isDomainAdmin) {
                                            $label .= ' (account limit reached - ' . $stats['current_accounts'] . '/' . $stats['max_accounts'] . ')';
                                        }

                                        return [$domain->domain_id => $label];
                                    });
                            })

                            /**
                             * Prevent selecting full domains
                             */
                            ->rule(function () {

                                return function ($attribute, $value, $fail) {

                                    $user = auth()->user();

                                    if (! $user || ! $user->isDomainAdmin() || $user->isSystemAdmin()) {
                                        return;
                                    }

                                    $limitService = app(\App\Services\DomainAdminLimitService::class);

                                    $result = $limitService->checkDomainAccountLimit($value);

                                    if (! $result['allowed']) {
                                        $fail($result['message']);
                                    }
                                };
                            })

                            /**
                             * UX safety: block selection immediately if user still manages to pick it
                             */
                            ->afterStateUpdated(function ($state, callable $set) {

                                if (! $state) {
                                    return;
                                }

                                $user = auth()->user();

                                if (! $user || ! $user->isDomainAdmin() || $user->isSystemAdmin()) {
                                    return;
                                }

                                $limitService = app(\App\Services\DomainAdminLimitService::class);

                                $result = $limitService->checkDomainAccountLimit($state);

                                if (! $result['allowed']) {

                                    Notification::make()
                                        ->title('Domain unavailable')
                                        ->body($result['message'])
                                        ->danger()
                                        ->send();
                                    $set('domain_id', null);
                                }
                            }),
                        
                        Hidden::make('username'),
                        Hidden::make('type')->default('local'),
                        Hidden::make('smtp'),                    
                        Hidden::make('pop'),
                        Password::make('crypt')
                            ->label('Password')
                            ->copyable()
                            ->regeneratePassword(color: 'success')                      
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),                              
                        TextInput::make('realname')
                            ->label('Real Name')
                            ->required()
                            ->default(null)
                            ->columnSpanFull(),
                        Toggle::make('enabled')
                            ->label('Account enabled')
                            ->required()
                            ->default(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpan(2),
                
                Section::make('System Settings')
                    ->schema([
                        TextInput::make('uid')
                            ->label('UID')
                            ->required()
                            ->numeric()
                            ->default(Setting::get('default_uid', 65534))
                            ->helperText('Default UID from system settings')
                            ->disabled($isDomainAdmin),

                        TextInput::make('gid')
                            ->label('GID')
                            ->required()
                            ->numeric()
                            ->default(Setting::get('default_gid', 65534))
                            ->helperText('Default GID from system settings')
                            ->disabled($isDomainAdmin),
                    ])
                    ->columns(2)
                    ->columnSpan(2),
                
                Section::make('Security & Features')
                    ->schema([
                        Toggle::make('on_avscan')
                            ->label('Allow AV Scan')
                            ->required(),
                            
                        Toggle::make('on_blocklist')
                            ->label('Allow blocklist')
                            ->required(),
                        
                        Toggle::make('on_whitelist')
                            ->label('Allow whitelist')
                            ->required(),                        
                    ])
                    ->columns(3)
                    ->columnSpan(2),
                
                Section::make('Forwarding')
                    ->schema([
                        Toggle::make('on_forward')
                            ->label('Forward the message')
                            ->reactive()
                            ->required(),
                        TextInput::make('forward')
                            ->label('Forward the message to')
                            ->default(null)
                            ->columnSpanFull()
                            ->hidden(fn ($get) => !$get('on_forward'))
                            ->required(fn ($get) => $get('on_forward')),

                        Toggle::make('unseen')
                            ->label('Store a copy of the message locally when forwarding')
                            ->required()
                            ->hidden(fn ($get) => !$get('on_forward')),
                    ])
                    ->columnSpan(2),
                
                Section::make('Limits & Quotas')
                    ->schema([
                        TextInput::make('maxmsgsize')
                            ->label('Maximum Message Size')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('0 = unlimited'),
                            
                        TextInput::make('quota')
                            ->label('Mailbox Quota')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('0 = unlimited'),
                    ])
                    ->columns(2)
                    ->columnSpan(2),

                Section::make(spamFilterName() . " Spam filtering")
                    ->schema([
                        Toggle::make('on_spamassassin')
                            ->label('Allow Spam filtering via '.spamFilterName())
                            ->required()
                            ->columnSpanFull()
                            ->reactive(),

                        TextInput::make('sa_tag')
                            ->label(spamFilterName().' Tag Score')
                            ->required(fn ($get) => $get('on_spamassassin'))
                            ->numeric()
                            ->default(Setting::get('spam_tag_threshold', 2))
                            ->helperText('Score at which to tag messages as spam')
                            ->hidden(fn ($get) => !$get('on_spamassassin')),

                        TextInput::make('sa_refuse')
                            ->label(spamFilterName().' Refuse Score')
                            ->required(fn ($get) => $get('on_spamassassin'))
                            ->numeric()
                            ->default(Setting::get('spam_refuse_threshold', 5))
                            ->helperText('Score at which to refuse messages')
                            ->hidden(fn ($get) => !$get('on_spamassassin')),

                        TextInput::make('tagline')
                            ->label('Tagline')
                            ->default(null)
                            ->hidden(fn ($get) => !$get('on_spamassassin')),

                        Toggle::make('spam_drop')
                            ->label('Drop all spam')
                            ->columnSpanFull()
                            ->default(Setting::get('default_spam_drop', false))
                            ->hidden(fn ($get) => !$get('on_spamassassin')),
                    ])
                    ->columns(2)
                    ->columnSpan(2),
                
                Section::make('Vacation Message')
                    ->schema([
                        Toggle::make('on_vacation')
                            ->label('Vacation Mode')
                            ->required(),                        
                        Textarea::make('vacation')
                            ->label('Vacation Auto-Reply Message')
                            ->default(null)
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(2),
            ]);
    }
}