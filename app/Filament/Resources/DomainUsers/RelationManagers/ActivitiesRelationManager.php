<?php

namespace App\Filament\Resources\DomainUsers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';
    
    protected static ?string $recordTitleAttribute = 'description';
    
    protected static ?string $title = 'Activity Timeline';
    
    public function isReadOnly(): bool
    {
        return true;
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\ViewColumn::make('summary')
                    ->label('Activity')
                    ->view('filament.infolists.components.activity-summary')
                    ->getStateUsing(function ($record) {
                        return $record;
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}