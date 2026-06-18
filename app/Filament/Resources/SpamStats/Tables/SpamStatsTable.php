<?php
namespace App\Filament\Resources\SpamStats\Tables;

use App\Models\DomainStatsAggregated;
use App\Services\PermissionAwareStatsService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;

class SpamStatsTable
{
    public function table(Table $table): Table
    {
        $user = Auth::user();
        $statsService = app(PermissionAwareStatsService::class);
        
        if ($user->hasRole('system_admin')) {
            $query = DomainStatsAggregated::query();
        } elseif ($user->hasRole('domain_admin')) {
            $accessibleDomains = $statsService->getAccessibleDomains();
            
            if (empty($accessibleDomains)) {
                $query = DomainStatsAggregated::query()->whereRaw('1 = 0');
            } else {
                $query = DomainStatsAggregated::query()->whereIn('domain', $accessibleDomains);
            }
        } else {
            $query = DomainStatsAggregated::query()->whereRaw('1 = 0');
        }
        
        return $table
            ->query($query)
            ->columns($this->getColumns())
            ->filters($this->getFilters())
            ->actions($this->getActions())
            ->defaultSort('date', 'desc');
    }
    
    protected function getColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('domain')
                ->label('Domain')
                ->searchable()
                ->sortable()
                ->icon('heroicon-o-server'),
                
            Tables\Columns\TextColumn::make('date')
                ->label('Date')
                ->date('Y-m-d')
                ->sortable()
                ->toggleable(),
                
            Tables\Columns\TextColumn::make('incoming_count')
                ->label('Incoming')
                ->numeric()
                ->sortable()
                ->toggleable()
                ->alignRight(),
                
            Tables\Columns\TextColumn::make('spam_count')
                ->label('Spam')
                ->numeric()
                ->sortable()
                ->color('danger')
                ->toggleable()
                ->alignRight(),
                
            Tables\Columns\TextColumn::make('virus_count')
                ->label('Virus')
                ->numeric()
                ->sortable()
                ->color('warning')
                ->toggleable()
                ->alignRight(),
                
            Tables\Columns\TextColumn::make('spam_percentage')
                ->label('Spam Rate')
                ->formatStateUsing(fn ($state): string => round($state, 2) . '%')
                ->badge()
                ->color(fn ($state) => $this->getPercentageColor((float) $state))
                ->toggleable(),
                
            Tables\Columns\TextColumn::make('avg_spam_score')
                ->label('Avg Score')
                ->numeric(2)
                ->sortable()
                ->toggleable()
                ->alignRight(),
                
            Tables\Columns\TextColumn::make('max_spam_score')
                ->label('Max Score')
                ->numeric(2)
                ->sortable()
                ->toggleable()
                ->alignRight(),
                
            Tables\Columns\TextColumn::make('total_size_bytes')
                ->label('Size')
                ->formatStateUsing(fn ($state): string => $this->formatBytes($state))
                ->toggleable()
                ->alignRight(),
        ];
    }
    
    protected function getFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('domain')
                ->label('Domain')
                ->options(fn () => $this->getDomainOptions())
                ->searchable(),
                
            Tables\Filters\Filter::make('date_range')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('from')
                        ->label('From'),
                    \Filament\Forms\Components\DatePicker::make('until')
                        ->label('Until'),
                ])
                ->query(function ($query, array $data) {
                    return $query
                        ->when($data['from'], fn ($q) => $q->whereDate('date', '>=', $data['from']))
                        ->when($data['until'], fn ($q) => $q->whereDate('date', '<=', $data['until']));
                }),
                
            Tables\Filters\Filter::make('high_spam')
                ->label('High Spam (>50%)')
                ->query(fn ($query) => $query->where('spam_percentage', '>', 50)),
        ];
    }
    
    protected function getActions(): array
    {
        return [
            Action::make('view')
                ->label('View Details')
                ->icon('heroicon-o-eye')
                ->modalHeading(fn ($record): string => "Spam Statistics: {$record->domain}")
                ->modalSubheading(fn ($record): string => "Date: {$record->date}")
                ->infolist(fn ($record): array => $this->getInfolist())
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
        ];
    }
    
    protected function getInfolist(): array
    {
        return [
            Section::make('Message Statistics')
                ->schema([
                    Infolists\Components\TextEntry::make('domain')
                        ->label('Domain')
                        ->icon('heroicon-o-server'),
                    Infolists\Components\TextEntry::make('date')
                        ->label('Date')
                        ->date(),
                    Infolists\Components\TextEntry::make('incoming_count')
                        ->label('Total Incoming Messages')
                        ->numeric(),
                    Infolists\Components\TextEntry::make('spam_count')
                        ->label('Spam Messages')
                        ->numeric()
                        ->color('danger'),
                    Infolists\Components\TextEntry::make('virus_count')
                        ->label('Virus Messages')
                        ->numeric()
                        ->color('warning'),
                    Infolists\Components\TextEntry::make('clean_count')
                        ->label('Clean Messages')
                        ->state(fn ($record): int => $record->incoming_count - $record->spam_count)
                        ->numeric()
                        ->color('success'),
                ])->columns(2),
                
            Section::make('Spam Analysis')
                ->schema([
                    Infolists\Components\TextEntry::make('spam_percentage')
                        ->label('Spam Rate')
                        ->formatStateUsing(fn ($state): string => round($state, 2) . '%')
                        ->badge()
                        ->color(fn ($state) => $this->getPercentageColor((float) $state)),
                    Infolists\Components\TextEntry::make('avg_spam_score')
                        ->label('Average Spam Score')
                        ->numeric(2),
                    Infolists\Components\TextEntry::make('max_spam_score')
                        ->label('Maximum Spam Score')
                        ->numeric(2),
                    Infolists\Components\TextEntry::make('risk_level')
                        ->label('Risk Level')
                        ->state(fn ($record): string => $this->getRiskLevel($record->spam_percentage))
                        ->badge()
                        ->color(fn ($record) => $this->getRiskColor($record->spam_percentage)),
                ])->columns(2),
                
            Section::make('Storage Information')
                ->schema([
                    Infolists\Components\TextEntry::make('total_size_bytes')
                        ->label('Total Size')
                        ->formatStateUsing(fn ($state): string => $this->formatBytes($state)),
                    Infolists\Components\TextEntry::make('average_size')
                        ->label('Average Message Size')
                        ->state(fn ($record): float => $record->incoming_count > 0 
                            ? $record->total_size_bytes / $record->incoming_count 
                            : 0)
                        ->formatStateUsing(fn ($state): string => $this->formatBytes((int) $state)),
                ])->columns(2),
        ];
    }
    
    protected function getDomainOptions(): array
    {
        $user = Auth::user();
        
        if ($user->hasRole('system_admin')) {
            $domains = DomainStatsAggregated::distinct()->pluck('domain')->toArray();
        } elseif ($user->hasRole('domain_admin')) {
            $statsService = app(PermissionAwareStatsService::class);
            $accessibleDomains = $statsService->getAccessibleDomains();
            $domains = DomainStatsAggregated::whereIn('domain', $accessibleDomains)
                ->distinct()
                ->pluck('domain')
                ->toArray();
        } else {
            $domains = [];
        }
        
        return array_combine($domains, $domains);
    }
    
    protected function getPercentageColor(float $percentage): string
    {
        if ($percentage > 50) return 'danger';
        if ($percentage > 20) return 'warning';
        return 'success';
    }
    
    protected function getRiskLevel(float $percentage): string
    {
        if ($percentage < 5) return 'Low';
        if ($percentage < 15) return 'Moderate';
        if ($percentage < 30) return 'High';
        return 'Critical';
    }
    
    protected function getRiskColor(float $percentage): string
    {
        if ($percentage < 5) return 'success';
        if ($percentage < 15) return 'info';
        if ($percentage < 30) return 'warning';
        return 'danger';
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