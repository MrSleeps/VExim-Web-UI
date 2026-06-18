<?php

namespace App\Filament\Resources\EximAliases\Pages;

use App\Filament\Resources\EximAliases\EximAliasResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEximAlias extends ViewRecord
{
    protected static string $resource = EximAliasResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}