<?php

namespace App\Filament\Resources\EximAliases\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use App\Models\Domain;

class EximAliasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Alias Information')
                    ->schema([
                        Grid::make(2) 
                            ->schema([
                                TextInput::make('localpart')
                                    ->label('Alias Address')
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
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $domainId = $get('domain_id');
                                        if ($domainId && $state) {
                                            $domain = Domain::find($domainId);
                                            if ($domain) {
                                                $set('username', $state . '@' . $domain->domain);
                                            }
                                        }
                                    })
                                    ->columnSpan(1),
                                
                                Select::make('domain_id')
                                    ->label('Domain')
                                    ->options(function () {
                                        $user = auth()->user();
                                        if ($user->isSystemAdmin()) {
                                            return Domain::pluck('domain', 'domain_id');
                                        }
                                        if ($user->isDomainAdmin()) {
                                            return $user->domains()->pluck('domains.domain', 'domains.domain_id');
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
                                                $set('username', $localpart . '@' . $domain->domain);
                                            }
                                        }
                                    })
                                    ->columnSpan(1),
                            ]),
                        
                        TextInput::make('username')
                            ->label('Full Alias Address (Auto-generated)')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabled()
                            ->dehydrated(true)
                            ->helperText('This is the complete alias that will be saved')
                            ->columnSpanFull(),
                    ]),
                    
                Section::make('Forwarding Destination')
                    ->schema([
                        TextInput::make('smtp')
                            ->label('Forwards To')
                            ->required()
                            ->email()
                            ->helperText('Where should this alias forward to? (e.g., user@example.com) - multiple email addresses should be seperated by a comma')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('pop', $state);
                            }),
                            
                        TextInput::make('pop')
                            ->hidden()
                            ->default(fn ($get) => $get('smtp'))
                            ->dehydrated(true),
                    ]),
                    
                Section::make('Status')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Alias Enabled')
                            ->default(true),
                    ]),
                    
                Hidden::make('type')
                    ->default('alias'),
                    
                Hidden::make('on_forward')
                    ->default(true),
            ]);
    }
}