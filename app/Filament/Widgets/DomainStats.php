<?php

namespace App\Filament\Widgets;

use App\Models\Domain;
use App\Models\EximUser;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DomainStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }
        
        $stats = [];
        
        if ($user->isSystemAdmin()) {
            // System admin sees everything
            $stats = [
                Stat::make('Total Domains', Domain::count())
                    ->description('All domains in system')
                    ->icon('heroicon-o-server-stack')
                    ->color('primary'),
                
                Stat::make('Email Accounts', EximUser::where('type', 'local')->count())
                    ->description('All local email accounts')
                    ->icon('heroicon-o-envelope')
                    ->color('success'),
                
                Stat::make('Email Aliases', EximUser::where('type', 'alias')->count())
                    ->description('All email forwarders')
                    ->icon('heroicon-o-arrow-uturn-right')
                    ->color('info'),
            ];
        } elseif ($user->isDomainAdmin()) {
            // Domain admin only sees their domains
            $domainIds = $user->domains()->pluck('domains.domain_id');
            $domainCount = Domain::whereIn('domain_id', $domainIds)->count();
            $emailCount = EximUser::where('type', 'local')->whereIn('domain_id', $domainIds)->count();
            $aliasCount = EximUser::where('type', 'alias')->whereIn('domain_id', $domainIds)->count();
            
            $stats = [
                Stat::make('Your Domains', $domainCount)
                    ->description('Domains you manage')
                    ->icon('heroicon-o-server-stack')
                    ->color('primary'),
                
                Stat::make('Email Accounts', $emailCount)
                    ->description('Email accounts in your domains')
                    ->icon('heroicon-o-envelope')
                    ->color('success'),
                
                Stat::make('Email Aliases', $aliasCount)
                    ->description('Forwarders in your domains')
                    ->icon('heroicon-o-arrow-uturn-right')
                    ->color('info'),
            ];
        }
        
        return $stats;
    }
}