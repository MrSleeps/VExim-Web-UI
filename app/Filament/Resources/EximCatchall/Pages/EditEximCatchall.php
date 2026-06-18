<?php

namespace App\Filament\Resources\EximCatchall\Pages;

use App\Filament\Resources\EximCatchall\EximCatchallResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEximCatchall extends EditRecord
{
    protected static string $resource = EximCatchallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
