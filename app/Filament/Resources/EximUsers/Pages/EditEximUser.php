<?php

namespace App\Filament\Resources\EximUsers\Pages;

use App\Filament\Resources\EximUsers\EximUserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEximUser extends EditRecord
{
    protected static string $resource = EximUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
