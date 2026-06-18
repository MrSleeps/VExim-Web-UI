<?php

namespace App\Filament\Resources\DomainAliases\Pages;

use App\Filament\Resources\DomainAliases\DomainAliasResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDomainAlias extends EditRecord
{
    protected static string $resource = DomainAliasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
