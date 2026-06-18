<?php

namespace App\Filament\Resources\EximFails\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use App\Models\Domain;
use App\Models\Setting;

class EximFailForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $isSystemAdmin = $user->isSystemAdmin();
        $isDomainAdmin = $user->isDomainAdmin();
        $record = $schema->getRecord();
        $isCreating = $record === null;
        if (!$isCreating && $record) {
            $username = $record->username;
            if ($username && str_contains($username, '@')) {
                [$localpart, $domain] = explode('@', $username, 2);
                $domainRecord = Domain::where('domain', $domain)->first();
                if ($domainRecord) {
                    $schema->state([
                        'localpart' => $localpart,
                        'domain_id' => $domainRecord->domain_id,
                    ]);
                }
            }
        }
        
        return $schema
            ->components([
                Section::make('Fail Account Information')
                    ->schema([
                        TextInput::make('localpart')
                            ->label('Email Address')
                            ->placeholder('user')
                            ->required()
                            ->maxLength(64)
                            ->helperText('The part before the @')
                            ->regex('/^[a-zA-Z0-9._-]+$/')
                            ->validationMessages([
                                'regex' => 'Only letters, numbers, dots, underscores, and hyphens are allowed.',
                            ])
                            ->suffix('@')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $domainId = $get('domain_id');
                                if ($domainId && $state) {
                                    $domain = Domain::find($domainId);
                                    if ($domain) {
                                        $fullEmail = $state . '@' . $domain->domain;
                                        $set('username', $fullEmail);
                                    }
                                }
                            })
                            ->columnSpan(1),
                        Select::make('domain_id')
                            ->label('Domain')
                            ->options(function () use ($isSystemAdmin, $isDomainAdmin) {
                                if ($isSystemAdmin) {
                                    return Domain::where('enabled', true)->pluck('domain', 'domain_id');
                                }
                                if ($isDomainAdmin) {
                                    return auth()->user()->domains()
                                        ->where('enabled', true)
                                        ->pluck('domains.domain', 'domains.domain_id');
                                }
                                return [];
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('The part after @')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $localpart = $get('localpart');
                                if ($state && $localpart) {
                                    $domain = Domain::find($state);
                                    if ($domain) {
                                        $fullEmail = $localpart . '@' . $domain->domain;
                                        $set('username', $fullEmail);
                                    }
                                }
                            })
                            ->columnSpan(1),
                        
                        // Hidden username field (stores the full email)
                        Hidden::make('username')
                            ->default(function () use ($record) {
                                if ($record && $record->username) {
                                    return $record->username;
                                }
                                return null;
                            }),
                        

                        Toggle::make('enabled')
                            ->label('Enabled')
                            ->default(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                
                Hidden::make('type')
                    ->default('fail'),
                    
                Hidden::make('smtp')
                    ->default(':fail:'),
                    
                Hidden::make('pop')
                    ->default(':fail:'),
                
                Hidden::make('uid')
                    ->default(Setting::get('default_uid', 5000)),
                    
                Hidden::make('gid')
                    ->default(Setting::get('default_gid', 5000)),
            ]);
    }
}