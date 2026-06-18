<?php
namespace App\Filament\Resources\ActivityLog\Pages;

use App\Filament\Resources\ActivityLog\ActivityLogResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Infolists\Components\TextEntry;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        $record = $this->getRecord();
        return $schema
            ->components([
                Section::make('Activity Details')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Date/Time')
                            ->dateTime('Y-m-d H:i:s'),

                        SchemaView::make('filament.infolists.components.activity-summary')
                            ->viewData(['record' => $record]),
                    ])
                    ->columns(1),
            ]);
    }
}