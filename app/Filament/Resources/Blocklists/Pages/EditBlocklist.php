<?php

namespace App\Filament\Resources\Blocklists\Pages;

use App\Filament\Resources\Blocklists\BlocklistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlocklist extends EditRecord
{
    protected static string $resource = BlocklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
