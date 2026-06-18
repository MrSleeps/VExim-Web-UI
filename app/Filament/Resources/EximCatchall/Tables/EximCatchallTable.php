<?php

namespace App\Filament\Resources\EximCatchall\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EximCatchallTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain.domain')
                    ->label('Domain')
                    ->sortable(), 
                
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
                    ->placeholder('All catchall')
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