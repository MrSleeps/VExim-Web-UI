<?php

namespace App\Filament\Resources\Groups\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use App\Models\Domain;

class GroupForm
{
    public static function configure(Schema $schema): Schema
    {
        $record = $schema->getRecord();
        $isCreating = $record === null;
        
        return $schema
            ->components([
                Select::make('domain_id')
                    ->label('Domain')
                    ->placeholder('Select a domain')
                    ->options(function () use ($isCreating) {
                        $user = auth()->user();

                        if ($isCreating) {
                            $domainsQuery = $user->isSystemAdmin() ? Domain::query() : $user->domains();
                            $domainsQuery->whereNotExists(function ($query) {
                                $query->select('users.domain_id')
                                    ->from('users')
                                    ->whereColumn('domains.domain_id', 'users.domain_id')
                                    ->where('users.type', 'catch');
                            });
                            return $domainsQuery->pluck('domain', 'domains.domain_id');
                        } else {
                            $domainsQuery = $user->isSystemAdmin() ? Domain::query() : $user->domains();
                            return $domainsQuery->pluck('domain', 'domains.domain_id');
                        }
                    })
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->helperText('This forms part of the email, the part before the @')
                    ->regex('/^[a-zA-Z0-9._-]+$/')
                    ->validationMessages([
                        'regex' => 'Only letters, numbers, dots, underscores, and hyphens are allowed.',
                    ])                
                    ->required(),
                Toggle::make('is_public')
                    ->label('Public group')
                    ->helperText('Anyone can email the group')
                    ->required()
                    ->default(true),
                Toggle::make('enabled')
                    ->required(),
            ]);
    }
}