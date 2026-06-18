<?php

namespace App\Filament\Resources\Blocklists\Tables;

use App\Models\EximUser;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BlocklistsTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();
        
        $table = $table
            ->columns([
                // Domain column - only for system admin and domain admin
                TextColumn::make('domain.domain')
                    ->label('Domain')
                    ->searchable()
                    ->sortable()
                    ->visible($user->isSystemAdmin() || $user->isDomainAdmin()),
                
                // User column - only for system admin and domain admin
                TextColumn::make('user_id')
                    ->label('User')
                    ->formatStateUsing(function ($record) use ($user) {
                        if ($user->isSystemAdmin() || $user->isDomainAdmin()) {
                            $eximUser = EximUser::where('user_id', $record->user_id)->first();
                            if ($eximUser && $eximUser->domain) {
                                return $eximUser->localpart . '@' . $eximUser->domain->domain;
                            }
                        }
                        return $record->user_id;
                    })
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('user', function ($q) use ($search) {
                            $q->where('username', 'like', "%{$search}%")
                              ->orWhere('localpart', 'like', "%{$search}%");
                        });
                    })
                    ->sortable()
                    ->visible($user->isSystemAdmin() || $user->isDomainAdmin()),
                
                // Block method - everyone can see
                TextColumn::make('blockhdr')
                    ->label('Method')
                    ->searchable()
                    ->sortable(),
                
                // Block value - everyone can see
                TextColumn::make('blockval')
                    ->label('Value')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                
                // Color - only system admin
                TextColumn::make('color')
                    ->label('Color')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'red' => 'danger',
                        'green' => 'success',
                        'blue' => 'info',
                        'yellow' => 'warning',
                        default => 'gray',
                    })
                    ->visible($user->isSystemAdmin()),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(function ($record) use ($user) {
                        if ($user->isSystemAdmin()) return true;
                        if ($user->isDomainAdmin()) {
                            return $user->domains->contains('domain_id', $record->domain_id);
                        }
                        if ($user->isDomainUser()) {
                            $eximUser = EximUser::where('username', $user->email)
                                ->whereIn('type', ['local', 'alias', 'catch'])
                                ->first();
                            if ($eximUser) {
                                return $record->domain_id === $eximUser->domain_id && 
                                       $record->user_id === $eximUser->user_id;
                            }
                        }
                        return false;
                    }),
                DeleteAction::make()
                    ->visible(function ($record) use ($user) {
                        if ($user->isSystemAdmin()) return true;
                        if ($user->isDomainAdmin()) {
                            return $user->domains->contains('domain_id', $record->domain_id);
                        }
                        if ($user->isDomainUser()) {
                            $eximUser = EximUser::where('username', $user->email)
                                ->whereIn('type', ['local', 'alias', 'catch'])
                                ->first();
                            if ($eximUser) {
                                return $record->domain_id === $eximUser->domain_id && 
                                       $record->user_id === $eximUser->user_id;
                            }
                        }
                        return false;
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible($user->isSystemAdmin() || $user->isDomainAdmin()),
                ]),
            ]);
            
        return $table;
    }
}