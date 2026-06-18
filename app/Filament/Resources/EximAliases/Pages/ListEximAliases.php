<?php

namespace App\Filament\Resources\EximAliases\Pages;

use App\Filament\Resources\EximAliases\EximAliasResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ListRecords;

class ListEximAliases extends ListRecords
{
    protected static string $resource = EximAliasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
