<?php

namespace App\Filament\Widgets;

use App\Models\EximUser;
use Elemind\FilamentECharts\Widgets\EChartWidget;
use Illuminate\Support\Facades\Auth;

class AccountTypesChart extends EChartWidget
{
    protected static ?string $heading = 'Account Types Distribution';
    protected static ?string $subheading = 'User accounts grouped by type';
    protected static ?int $sort = 10;

    protected function getOptions(): array
    {
        $user = Auth::user();
        
        // If no user is logged in, return empty chart
        if (!$user) {
            return $this->getEmptyChartOptions();
        }
        
        // Build query based on user role
        $query = EximUser::query();
        
        if ($user->isSystemAdmin()) {
            // System admin sees all users
            $accountTypeCounts = $query->select('type')
                ->selectRaw('count(*) as count')
                ->groupBy('type')
                ->get();
        } elseif ($user->isDomainAdmin()) {
            // Domain admin only sees users in their domains
            $domainIds = $user->domains()->pluck('domains.domain_id');
            $accountTypeCounts = $query->whereIn('domain_id', $domainIds)
                ->select('type')
                ->selectRaw('count(*) as count')
                ->groupBy('type')
                ->get();
        } else {
            // Regular users see nothing
            return $this->getEmptyChartOptions();
        }
        
        // If no data, show empty chart
        if ($accountTypeCounts->isEmpty()) {
            return $this->getEmptyChartOptions();
        }
        
        $data = [];
        $colors = ['#5470c6', '#fac858', '#ee6666', '#73c0de', '#3ba272', '#fc8452', '#9a60b4', '#ea7ccc'];
        $colorIndex = 0;
        
        foreach ($accountTypeCounts as $type) {
            $label = $type->type ?? 'Not Set';

            $displayLabel = match($label) {
                'local' => 'Local Accounts',
                'alias' => 'Aliases/Forwarders',
                default => ucfirst($label)
            };
            
            $data[] = [
                'name' => $displayLabel,
                'value' => $type->count,
                'itemStyle' => [
                    'color' => $colors[$colorIndex % count($colors)]
                ]
            ];
            $colorIndex++;
        }
        
        return [
            'tooltip' => [
                'trigger' => 'item',
                'formatter' => '{b}: {d}% ({c} users)'
            ],
            'legend' => [
                'orient' => 'vertical',
                'left' => 'left'
            ],
            'series' => [
                [
                    'name' => 'Account Types',
                    'type' => 'pie',
                    'radius' => '80%',
                    'data' => $data,
                    'label' => [
                        'show' => false,
                        'formatter' => '{b}: {d}%'
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Return empty chart options when user has no data to view
     */
    protected function getEmptyChartOptions(): array
    {
        return [
            'title' => [
                'show' => true,
                'text' => 'No data available',
                'left' => 'center',
                'top' => 'center',
                'textStyle' => [
                    'color' => '#999',
                    'fontSize' => 14
                ]
            ],
            'tooltip' => [
                'show' => false
            ],
            'series' => [
                [
                    'name' => 'Account Types',
                    'type' => 'pie',
                    'radius' => '80%',
                    'data' => [],
                    'label' => [
                        'show' => false
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Optional: Hide the widget entirely for users with no permission
     */
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && ($user->isSystemAdmin() || $user->isDomainAdmin());
    }
}