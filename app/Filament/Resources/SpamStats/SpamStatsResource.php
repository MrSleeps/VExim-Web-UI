<?php
namespace App\Filament\Resources\SpamStats;

use App\Filament\Resources\SpamStats\Pages\ListSpamStats;
use App\Filament\Resources\SpamStats\Pages\ViewSpamStats;
use App\Filament\Resources\SpamStats\Tables\SpamStatsTable;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

    
class SpamStatsResource extends Resource
{
    protected static ?string $model = DomainStatsAggregated::class;
    
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static string|\UnitEnum|null $navigationGroup = 'Statistics';
    
    
    protected static ?string $navigationLabel = 'Spam Statistics';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $slug = 'spam-stats';
    
    public static function table(Table $table): Table
    {
        $tableBuilder = new SpamStatsTable();
        return $tableBuilder->table($table);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => ListSpamStats::route('/'),
        ];
    }
    
    public static function getPluralLabel(): string
    {
        return 'Spam Statistics';
    }
    
    public static function getLabel(): string
    {
        return 'Spam Statistics';
    }
}