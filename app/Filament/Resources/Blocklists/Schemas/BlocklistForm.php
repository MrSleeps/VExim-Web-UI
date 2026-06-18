<?php

namespace App\Filament\Resources\Blocklists\Schemas;

use App\Models\Domain;
use App\Models\EximUser;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Hidden;

class BlocklistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('color')
                    ->default('black'),
                Select::make('domain_id')
                    ->label('Domain')
                    ->options(function () {
                        $user = auth()->user();
                        if ($user->isSystemAdmin()) {
                            return Domain::pluck('domain', 'domain_id');
                        }
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
                    ->live(),

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
                    ->helperText('Block this rule for all users in the domain')
                    ->hidden(fn (Get $get) => auth()->user()->isDomainUser())
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $set('user_id', null);
                        }
                    }),

                Select::make('user_id')
                    ->label('User')
                    ->options(function (Get $get) {
                        $user = auth()->user();
                        $domainId = $get('domain_id');
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
                    ->hidden(fn () => !auth()->user()->isDomainUser()),

                Select::make('blockhdr')
                    ->label('Method')
                    ->options([
                        'From' => 'From',
                        'To' => 'To',
                        'Subject' => 'Subject',
                        'X-Mailer' => 'X-Mailer',
                    ])
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live(),

                TextInput::make('blockval')
                    ->label(fn (Get $get): string => match ($get('blockhdr')) {
                        'From', 'To' => 'Email Address',
                        'Subject' => 'Subject Text',
                        'X-Mailer' => 'X-Mailer Value',
                        default => 'Block Value',
                    })

                    ->placeholder(fn (Get $get): string => match ($get('blockhdr')) {
                        'From' =>
                            'e.g., spammer@example.com or @spamdomain.com',

                        'To' =>
                            'e.g., user@example.com or @exampledomain.com',

                        'Subject' =>
                            'e.g., Viagra, Lottery, Spammity spam',

                        'X-Mailer' =>
                            'e.g., JavaMail, Python, Nodemailer',

                        default =>
                            'Enter value to block',
                    })

                    ->helperText(fn (Get $get): string => match ($get('blockhdr')) {

                        'From', 'To' =>
                            'Can be a full email address or domain (starting with @)',

                        'Subject' =>
                            'Partial matches will be blocked (case insensitive)',

                        'X-Mailer' =>
                            'Blocks emails with matching X-Mailer header',

                        default =>
                            'Enter the exact value to match',
                    })

                    ->required(),
                    
                TextInput::make('localpart')
                    ->hidden()
                    ->default(null)
                    ->dehydrated(true),
            ]);
    }
}