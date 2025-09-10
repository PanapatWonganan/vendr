<?php

namespace App\Filament\Resources\VendorPerformanceReportResource\Widgets;

use App\Models\Vendor;
use App\Models\VendorScore;
use App\Models\VendorEvaluation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VendorPerformanceOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = session('company_id');
        
        $totalVendors = Vendor::where('company_id', $companyId)->count();
        
        $evaluatedVendors = Vendor::where('company_id', $companyId)
            ->whereHas('evaluations', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->count();
            
        $avgScore = VendorScore::where('company_id', $companyId)
            ->whereNull('quarter')
            ->whereNull('month')
            ->avg('average_score');
            
        $gradeACount = VendorScore::where('company_id', $companyId)
            ->whereNull('quarter')
            ->whereNull('month')
            ->where('grade', 'A')
            ->count();
            
        $gradeBCount = VendorScore::where('company_id', $companyId)
            ->whereNull('quarter')
            ->whereNull('month')
            ->where('grade', 'B')
            ->count();
            
        $gradeCCount = VendorScore::where('company_id', $companyId)
            ->whereNull('quarter')
            ->whereNull('month')
            ->where('grade', 'C')
            ->count();
            
        $gradeDCount = VendorScore::where('company_id', $companyId)
            ->whereNull('quarter')
            ->whereNull('month')
            ->where('grade', 'D')
            ->count();
            
        $recentEvaluations = VendorEvaluation::where('company_id', $companyId)
            ->where('evaluation_date', '>=', now()->subMonth())
            ->count();

        return [
            Stat::make('Total Vendors', $totalVendors)
                ->description('Registered vendors')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),
                
            Stat::make('Evaluated Vendors', $evaluatedVendors)
                ->description($totalVendors > 0 ? round(($evaluatedVendors / $totalVendors) * 100, 1) . '% of total' : '0%')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Average Score', $avgScore ? number_format($avgScore, 2) : 'N/A')
                ->description('Company average')
                ->descriptionIcon('heroicon-m-star')
                ->color($avgScore >= 3.5 ? 'success' : ($avgScore >= 2.5 ? 'warning' : 'danger')),
                
            Stat::make('Grade A Vendors', $gradeACount)
                ->description('Top performers')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success'),
                
            Stat::make('Grade Distribution', "A:{$gradeACount} B:{$gradeBCount} C:{$gradeCCount} D:{$gradeDCount}")
                ->description('Performance grades')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
                
            Stat::make('Recent Evaluations', $recentEvaluations)
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
        ];
    }
}