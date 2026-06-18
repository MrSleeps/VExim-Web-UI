<?php

namespace App\Filament\Resources\DomainAliases\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use App\Models\Domain;

class DomainAliasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('alias')
                    ->label('Alias')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('domain_id')
                    ->label('Domain')
                    ->options(function () {
                        $user = auth()->user();

                        // System-admin sees all domains
                        if ($user->isSystemAdmin()) {
                            return Domain::pluck('domain', 'domain_id');
                        }

                        // Domain-admin sees only their domains
                        if ($user->isDomainAdmin()) {
                            return $user->domains()->pluck('domains.domain', 'domains.domain_id');
                        }

                        // Domain-user can't create any aliases
                        return [];
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->optionsLimit(50)
                    ->disabled(fn () => auth()->user()->isDomainUser()),
            ]);
    }
}