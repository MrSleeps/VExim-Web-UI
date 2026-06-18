<?php

namespace App\Filament\Resources\EximCatchall\Pages;

use App\Filament\Resources\EximCatchall\EximCatchallResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEximCatchall extends ViewRecord
{
    protected static string $resource = EximCatchallResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}