<?php

namespace App\Filament\Resources\EximAliases\Pages;

use App\Filament\Resources\EximAliases\EximAliasResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEximAlias extends EditRecord
{
    protected static string $resource = EximAliasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
