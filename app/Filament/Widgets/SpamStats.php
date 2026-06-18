<?php

namespace App\Filament\Widgets;

use App\Services\PermissionAwareStatsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SpamStats extends StatsOverviewWidget
{
    protected static ?int $sort = 2;
	
    /**
     * Determine if the widget can be viewed
     * Only show when spam engine is rspamd
	 * Because SpamAssassin is rubbish
     */
    public static function canView(): bool
    {
        return strtolower(env('VEXIM_SPAM_ENGINE', 'rspamd')) === 'rspamd';
    }	
    
    protected function getStats(): array
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return [];
            }
            
            $statsService = app(PermissionAwareStatsService::class);
            $dashboard = $statsService->getDashboardStats();

            $domainStats = $dashboard['domain_stats'] ?? [];
            $todayStats = $domainStats['today'] ?? $this->getDefaultTodayStats();
            
            $emailStats = $dashboard['email_stats'] ?? [];
            $emailTodayStats = is_array($emailStats) && isset($emailStats['today']) 
                ? $emailStats['today'] 
                : (is_array($emailStats) ? $emailStats : $this->getDefaultEmailStats());
            
            $totalMessages = $todayStats['total_incoming'] ?? 0;
            $spamCount = $todayStats['total_spam'] ?? 0;
            $virusCount = $todayStats['total_virus'] ?? 0;
            
            $cleanRate = $this->calculateCleanRate($totalMessages, $spamCount);
            $spamPercentage = $this->calculatePercentage($totalMessages, $spamCount);
            
            if ($user->hasRole('system_admin')) {
                // System admin sees everything
                $totalEmailMessages = $emailTodayStats['total_messages'] ?? 0;
                
                return [
                    Stat::make('Total Messages Today', number_format($totalEmailMessages))
                        ->description('Emails processed')
                        ->icon('heroicon-o-envelope')
                        ->color('primary'),
                    
                    Stat::make('Spam Blocked', number_format($spamCount))
                        ->description($spamPercentage)
                        ->icon('heroicon-o-shield-exclamation')
                        ->color($totalMessages > 0 && $spamCount > 0 ? 'danger' : 'secondary'),
                    
                    Stat::make('Virus Detected', number_format($virusCount))
                        ->description('Malicious emails blocked')
                        ->icon('heroicon-o-x-circle')
                        ->color($virusCount > 0 ? 'warning' : 'secondary'),
                    
                    Stat::make('Clean Rate', $cleanRate)
                        ->description($totalMessages > 0 ? 'Successfully delivered' : 'No messages processed')
                        ->icon('heroicon-o-check-circle')
                        ->color($this->getCleanRateColor($totalMessages, $spamCount)),
                ];
            } elseif ($user->hasRole('domain_admin')) {
                // Domain admin only sees their domains' stats
                return [
                    Stat::make('Messages Today', number_format($totalMessages))
                        ->description('Emails to your domains')
                        ->icon('heroicon-o-envelope')
                        ->color('primary'),
                    
                    Stat::make('Spam Blocked', number_format($spamCount))
                        ->description($spamPercentage)
                        ->icon('heroicon-o-shield-exclamation')
                        ->color($totalMessages > 0 && $spamCount > 0 ? 'danger' : 'secondary'),
                    
                    Stat::make('Virus Detected', number_format($virusCount))
                        ->description('Blocked from your domains')
                        ->icon('heroicon-o-x-circle')
                        ->color($virusCount > 0 ? 'warning' : 'secondary'),
                    
                    Stat::make('Clean Rate', $cleanRate)
                        ->description($totalMessages > 0 ? 'Clean emails delivered' : 'No messages processed')
                        ->icon('heroicon-o-check-circle')
                        ->color($this->getCleanRateColor($totalMessages, $spamCount)),
                ];
            } elseif ($user->hasRole('domain_user')) {
                // Domain user only sees their own email stats
                try {
                    $recipientStats = $statsService->getRecipientHealthScore($user->email);
                } catch (\Exception $e) {
                    Log::error('Failed to get recipient health score', ['error' => $e->getMessage()]);
                    $recipientStats = $this->getDefaultRecipientStats();
                }
                
                $userTotalEmails = $recipientStats['total_emails'] ?? 0;
                $userSpamCount = $recipientStats['spam_count'] ?? 0;
                $userSpamRate = $recipientStats['spam_rate'] ?? 0;
                
                return [
                    Stat::make('Your Emails', number_format($userTotalEmails))
                        ->description('Total emails received')
                        ->icon('heroicon-o-envelope')
                        ->color('primary'),
                    
                    Stat::make('Spam Received', number_format($userSpamCount))
                        ->description($userSpamRate . '% of your emails')
                        ->icon('heroicon-o-shield-exclamation')
                        ->color($userSpamCount > 0 ? 'danger' : 'secondary'),
                    
                    Stat::make('Health Score', number_format($recipientStats['health_score'] ?? 100) . '/100')
                        ->description($recipientStats['risk_level'] ?? 'Unknown')
                        ->icon('heroicon-o-heart')
                        ->color($this->getHealthColor($recipientStats['health_score'] ?? 100)),
                    
                    Stat::make('Recommendation', $recipientStats['recommendation'] ?? 'No data')
                        ->description('Based on your spam rate')
                        ->icon('heroicon-o-information-circle')
                        ->color('info'),
                ];
            }
            
            return [];
            
        } catch (\Exception $e) {
            Log::error('SpamStats widget error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return [
                Stat::make('Error', 'Unable to load stats')
                    ->description('Please try again later')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger'),
            ];
        }
    }
    
    /**
     * Calculate clean rate percentage
     */
    protected function calculateCleanRate(int $totalMessages, int $spamCount): string
    {
        if ($totalMessages === 0) {
            return 'N/A';
        }
        
        $cleanMessages = $totalMessages - $spamCount;
        $cleanRate = ($cleanMessages / $totalMessages) * 100;
        
        return round($cleanRate, 1) . '%';
    }
    
    /**
     * Calculate percentage for display
     */
    protected function calculatePercentage(int $totalMessages, int $count): string
    {
        if ($totalMessages === 0) {
            return 'No messages';
        }
        
        $percentage = ($count / $totalMessages) * 100;
        
        return round($percentage, 1) . '% of total';
    }
    
    /**
     * Get color for clean rate based on value and if there's data
     */
    protected function getCleanRateColor(int $totalMessages, int $spamCount): string
    {
        if ($totalMessages === 0) {
            return 'secondary';
        }
        
        $cleanRate = (($totalMessages - $spamCount) / $totalMessages) * 100;
        
        if ($cleanRate >= 95) return 'success';
        if ($cleanRate >= 80) return 'warning';
        return 'danger';
    }
    
    /**
     * Get default today stats
     */
    protected function getDefaultTodayStats(): array
    {
        return [
            'total_incoming' => 0,
            'total_spam' => 0,
            'total_virus' => 0,
            'avg_spam_score' => 0,
            'max_spam_score' => 0,
            'total_size_bytes' => 0,
            'spam_percentage' => 0,
            'total_size_formatted' => '0 B',
            'period' => 'Today',
            'date_range' => [],
        ];
    }
    
    /**
     * Get default email stats
     */
    protected function getDefaultEmailStats(): array
    {
        return [
            'total_messages' => 0,
            'virus_messages' => 0,
            'unique_actions' => 0,
            'clean_messages' => 0,
        ];
    }
    
    /**
     * Get default recipient stats
     */
    protected function getDefaultRecipientStats(): array
    {
        return [
            'total_emails' => 0,
            'spam_count' => 0,
            'spam_rate' => 0,
            'health_score' => 100,
            'risk_level' => 'Unknown',
            'recommendation' => 'No data available'
        ];
    }
    
    /**
     * Get health color based on score
     */
    protected function getHealthColor(float $score): string
    {
        if ($score >= 80) return 'success';
        if ($score >= 50) return 'warning';
        return 'danger';
    }
}