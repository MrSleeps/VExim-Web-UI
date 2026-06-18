<?php

namespace App\Filament\Resources\EximFails\Pages;

use App\Filament\Resources\EximFails\EximFailResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEximFail extends EditRecord
{
    protected static string $resource = EximFailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
