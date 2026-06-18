<?php

namespace App\Filament\Resources\DomainAliases\Pages;

use App\Filament\Resources\DomainAliases\DomainAliasResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDomainAliases extends ListRecords
{
    protected static string $resource = DomainAliasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        $user = auth()->user();
        return parent::getTableQuery()->forUser($user);
    }
}