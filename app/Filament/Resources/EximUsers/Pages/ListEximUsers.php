<?php

namespace App\Filament\Resources\EximUsers\Pages;

use App\Filament\Resources\EximUsers\EximUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEximUsers extends ListRecords
{
    protected static string $resource = EximUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
