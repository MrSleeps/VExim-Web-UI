<?php

namespace App\Filament\Resources\DomainAliases\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DomainAliasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain.domain')
                    ->label('Domain Name')
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('domain', function ($q) use ($search) {
                            $q->where('domain', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('alias')
                    ->label('Alias')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime(),
                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}