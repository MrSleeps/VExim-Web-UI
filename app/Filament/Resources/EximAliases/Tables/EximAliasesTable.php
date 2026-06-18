<?php

namespace App\Filament\Resources\EximAliases\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EximAliasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->label('Email Address')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                    
                TextColumn::make('smtp')
                    ->label('Forwards To')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                    
                TextColumn::make('domain.domain')
                    ->label('Domain')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                IconColumn::make('enabled')
                    ->label('Enabled')
                    ->boolean()
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('domain_id')
                    ->label('Domain')
                    ->relationship('domain', 'domain'),
                    
                TernaryFilter::make('enabled')
                    ->label('Enabled')
                    ->placeholder('All aliases')
                    ->trueLabel('Enabled only')
                    ->falseLabel('Disabled only'),
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