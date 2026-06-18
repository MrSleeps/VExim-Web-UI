<?php

namespace App\Filament\Resources\EximFails\Pages;

use App\Filament\Resources\EximFails\EximFailResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEximFails extends ListRecords
{
    protected static string $resource = EximFailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
