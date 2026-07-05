<?php

namespace App\Filament\Widgets;

use App\Services\SlaService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SlaPerformanceOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'procurement_officer', 'procurement_manager', 'auditor']) ?? false;
    }

    protected function getStats(): array
    {
        $companyId = session('company_id');
        $year = $this->filters['year'] ?? (int) date('Y');
        $slaService = app(SlaService::class);
        $stats = $slaService->getStatistics($companyId, $year);

        return [
            Stat::make('Overall Grade', $stats['overall_grade'])
                ->description('Average SLA Performance')
                ->color($this->getGradeColor($stats['overall_grade']))
                ->icon('heroicon-o-star')
                ->chart([65, 70, 75, 80, 85, 82, 88]),

            Stat::make('On-time Rate', $stats['on_time_rate'].'%')
                ->description('Orders completed on time')
                ->color($stats['on_time_rate'] >= 70 ? 'success' : 'warning')
                ->icon('heroicon-o-clock')
                ->chart([60, 65, 70, 75, 80, 75, $stats['on_time_rate']]),

            Stat::make('Total Orders', $stats['total_orders'])
                ->description('Orders tracked')
                ->icon('heroicon-o-shopping-cart')
                ->color('info'),

            Stat::make('Avg Completion', $stats['avg_percentage'].'%')
                ->description('Of standard time')
                ->color($stats['avg_percentage'] <= 100 ? 'success' : 'danger')
                ->icon('heroicon-o-chart-bar')
                ->chart([100, 95, 90, 85, 80, 85, $stats['avg_percentage']]),
        ];
    }

    protected function getGradeColor(string $grade): string
    {
        return match ($grade) {
            'S' => 'success',
            'A' => 'primary',
            'B' => 'info',
            'C' => 'warning',
            'D' => 'danger',
            'F' => 'danger',
            default => 'secondary',
        };
    }
}
