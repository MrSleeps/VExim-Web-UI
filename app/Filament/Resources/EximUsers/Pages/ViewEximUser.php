<?php

namespace App\Filament\Resources\EximUsers\Pages;

use App\Filament\Resources\EximUsers\EximUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEximUser extends ViewRecord
{
    protected static string $resource = EximUserResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}