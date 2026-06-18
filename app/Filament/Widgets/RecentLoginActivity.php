<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\Activity;

class RecentLoginActivity extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    
    protected static ?int $sort = 20;
    
    public function table(Table $table): Table
    {
        $user = Auth::user();
        
        $query = Activity::query()
            ->whereIn('event', ['login', 'logout', 'failed_login'])
            ->latest();
        
        if (!$user->isSystemAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('causer_type', User::class)
                  ->where('causer_id', $user->id)
                  ->orWhereJsonContains('properties', ['email' => $user->email]);
            });
        }
        
        return $table
            ->query($query)
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'login' => 'success',
                        'logout' => 'gray',
                        'failed_login' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                    
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('User')
                    ->default(fn ($record) => $record->properties['email'] ?? 'Unknown'),
                    
                Tables\Columns\TextColumn::make('properties.ip')
                    ->label('IP Address')
                    ->default('N/A'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('Y-m-d H:i:s')
                    ->since(),
            ])
            ->paginated(false);
    }
    
    public static function canView(): bool
    {
        // Only show to admins and domain admins
        $user = Auth::user();
        return $user && ($user->isSystemAdmin() || $user->isDomainAdmin());
    }
}