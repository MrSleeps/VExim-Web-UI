<?php

namespace App\Filament\Resources\Whitelists\Schemas;

use App\Models\Domain;
use App\Models\EximUser;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Hidden;

class WhitelistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
            // Domain select - for system and domain admins only
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
                        return $user->domains()
                            ->pluck('domains.domain', 'domains.domain_id');
                    }

                    return [];
                })
                ->required()
                ->searchable()
                ->preload()
                ->optionsLimit(50)
                ->hidden(fn () => auth()->user()->isDomainUser())
                ->live()
                ->helperText('Select a domain to see available users'),

            // Hidden domain_id for domain users only
            Hidden::make('domain_id')
                ->default(function () {
                    $user = auth()->user();
                    if ($user->isDomainUser()) {
                        $eximUser = EximUser::where('username', $user->email)
                            ->whereIn('type', ['local', 'alias', 'catch'])
                            ->first();
                        return $eximUser?->domain_id;
                    }
                    return null;
                })
                ->dehydrated(true)
                ->hidden(fn () => !auth()->user()->isDomainUser()),

                Checkbox::make('domain_wide')
                    ->label('Apply to entire domain')
                    ->helperText('Allow this sender for all users in the domain')
                    ->visible(fn () => !auth()->user()->isDomainUser())
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $set('user_id', null);
                            $set('localpart', null);
                        }
                    }),

                // User select - for system and domain admins only
                // User select - hidden for domain users
                Select::make('user_id')
                    ->label('User')
                    ->options(function (Get $get) {

                        $user = auth()->user();
                        $domainId = $get('domain_id');

                        // System-admin
                        if ($user->isSystemAdmin()) {

                            $query = EximUser::query()
                                ->whereIn('type', ['local', 'alias', 'catch']);

                            if ($domainId) {
                                $query->where('domain_id', $domainId);
                            }

                            return $query->get()
                                ->mapWithKeys(fn ($eximUser) => [
                                    $eximUser->user_id =>
                                        $eximUser->type === 'catch' 
                                            ? 'Catchall Account (' . $eximUser->localpart . '@' . ($eximUser->domain->domain ?? 'unknown') . ')'
                                            : $eximUser->username . ' (' . $eximUser->localpart . ')',
                                ])
                                ->toArray();
                        }

                        // Domain-admin
                        if ($user->isDomainAdmin()) {

                            $domainIds = $user->domains()
                                ->pluck('domains.domain_id')
                                ->toArray();

                            $query = EximUser::whereIn('domain_id', $domainIds)
                                ->whereIn('type', ['local', 'alias', 'catch']);

                            if ($domainId && in_array($domainId, $domainIds)) {
                                $query->where('domain_id', $domainId);
                            }

                            return $query->get()
                                ->mapWithKeys(fn ($eximUser) => [
                                    $eximUser->user_id =>
                                        $eximUser->type === 'catch' 
                                            ? 'Catchall Account (' . $eximUser->localpart . '@' . ($eximUser->domain->domain ?? 'unknown') . ')'
                                            : $eximUser->username . ' (' . $eximUser->localpart . ')',
                                ])
                                ->toArray();
                        }

                        return [];
                    })
                    ->searchable()
                    ->preload()
                    ->optionsLimit(50)
                    ->hidden(fn (Get $get) => auth()->user()->isDomainUser() || $get('domain_wide'))
                    ->required(fn (Get $get) => !auth()->user()->isDomainUser() && !$get('domain_wide'))
                    ->disabled(fn (Get $get) => !auth()->user()->isDomainUser() && empty($get('domain_id')))
                    ->live(),

                // Hidden user_id for domain users (auto-set)
                Hidden::make('user_id')
                    ->default(function () {
                        $user = auth()->user();
                        
                        if ($user->isDomainUser()) {
                            $eximUser = EximUser::where('username', $user->email)
                                ->whereIn('type', ['local', 'alias', 'catch'])
                                ->first();
                            
                            return $eximUser?->user_id;
                        }
                        
                        return null;
                    })
                    ->dehydrated(true)
                    ->hidden(fn () => !auth()->user()->isDomainUser()),  // Only show for domain users

                // Sender field (this is the main value for whitelist)
                TextInput::make('sender')
                    ->label('Sender')
                    ->required()
                    ->placeholder('e.g., trusted@example.com or @trusteddomain.com')
                    ->helperText('Can be a full email address or domain (starting with @)'),

                // Comment field
                TextInput::make('comment')
                    ->label('Comment')
                    ->placeholder('Optional comment about this whitelist entry'),
            ]);
    }
}