<?php
namespace App\Filament\Resources\SpamStats\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class SpamStatsForm
{
    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getSchema());
    }
    
    protected function getSchema(): array
    {
        return [
            Section::make('Statistics Information')
                ->description('View only - Statistics are automatically calculated')
                ->schema([
                    Forms\Components\Placeholder::make('domain')
                        ->label('Domain')
                        ->content(fn ($record) => $record?->domain ?? 'N/A'),
                        
                    Forms\Components\Placeholder::make('date')
                        ->label('Date')
                        ->content(fn ($record) => $record?->date?->format('Y-m-d') ?? 'N/A'),
                        
                    Forms\Components\Placeholder::make('action')
                        ->label('Action')
                        ->content(fn ($record) => ucfirst($record?->action ?? 'N/A')),
                        
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Placeholder::make('incoming_count')
                                ->label('Incoming Messages')
                                ->content(fn ($record) => number_format($record?->incoming_count ?? 0)),
                                
                            Forms\Components\Placeholder::make('spam_count')
                                ->label('Spam Messages')
                                ->content(fn ($record) => number_format($record?->spam_count ?? 0)),
                                
                            Forms\Components\Placeholder::make('virus_count')
                                ->label('Virus Messages')
                                ->content(fn ($record) => number_format($record?->virus_count ?? 0)),
                                
                            Forms\Components\Placeholder::make('spam_percentage')
                                ->label('Spam Percentage')
                                ->content(fn ($record) => $record?->spam_percentage . '%' ?? '0%'),
                        ]),
                        
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Placeholder::make('avg_spam_score')
                                ->label('Average Spam Score')
                                ->content(fn ($record) => number_format($record?->avg_spam_score ?? 0, 2)),
                                
                            Forms\Components\Placeholder::make('max_spam_score')
                                ->label('Maximum Spam Score')
                                ->content(fn ($record) => number_format($record?->max_spam_score ?? 0, 2)),
                                
                            Forms\Components\Placeholder::make('total_size_bytes')
                                ->label('Total Size')
                                ->content(fn ($record) => $this->formatBytes($record?->total_size_bytes ?? 0)),
                        ]),
                ]),
        ];
    }
    
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}