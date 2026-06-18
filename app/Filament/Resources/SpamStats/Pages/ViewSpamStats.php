<?php
namespace App\Filament\Resources\SpamStats\Pages;

use App\Filament\Resources\SpamStats\SpamStatsResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewSpamStats extends ViewRecord
{
    protected static string $resource = SpamStatsResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(SpamStatsResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}