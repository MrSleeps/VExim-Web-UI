<?php

namespace App\Filament\Resources\Settings\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use App\Filament\Resources\Settings\SettingResource;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('manage_all')
                ->label('Manage All Settings')
                ->icon('heroicon-o-cog')
                ->url(SettingResource::getUrl('manage'))
                ->color('success'),
                
            Action::make('create')
                ->label('New Setting')
                ->icon('heroicon-o-plus')
                ->url(SettingResource::getUrl('create')),
        ];
    }
}