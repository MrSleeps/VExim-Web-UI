<?php

namespace App\Filament\Resources\Blocklists\Pages;

use App\Filament\Resources\Blocklists\BlocklistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlocklists extends ListRecords
{
    protected static string $resource = BlocklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
