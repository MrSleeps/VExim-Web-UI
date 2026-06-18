<?php

namespace App\Filament\Resources\Whitelists\Pages;

use App\Filament\Resources\Whitelists\WhitelistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhitelists extends ListRecords
{
    protected static string $resource = WhitelistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
