<?php

namespace App\Filament\Widgets;

use App\Models\VendorScore;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VendorPerformanceStats extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $companyId = session('company_id');
        
        if (!$companyId) {
            return [
                Stat::make('ผู้ขายที่ประเมินแล้ว', '0')
                    ->description('จำนวนผู้ขายที่มีการประเมิน')
                    ->descriptionIcon('heroicon-m-building-office-2')
                    ->color('primary'),
                    
                Stat::make('คะแนนเฉลี่ย', '0.00/4.00')
                    ->description('คะแนนเฉลี่ยของผู้ขายทั้งหมด')
                    ->descriptionIcon('heroicon-m-star')
                    ->color('success'),
                    
                Stat::make('ผู้ขายเกรด A', '0')
                    ->description('ผู้ขายที่มีผลงานดีเด่น')
                    ->descriptionIcon('heroicon-m-trophy')
                    ->color('success'),
                    
                Stat::make('ผู้ขายที่ควรปรับปรุง', '0')
                    ->description('ผู้ขายเกรด C และ D')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
            ];
        }

        // Get latest scores for each vendor in this company
        $latestScores = VendorScore::selectRaw('vendor_id, MAX(created_at) as latest_created')
            ->where('company_id', $companyId)
            ->whereNotNull('weighted_grade')
            ->groupBy('vendor_id')
            ->get();

        $vendorIds = $latestScores->pluck('vendor_id')->toArray();
        
        if (empty($vendorIds)) {
            return [
                Stat::make('ผู้ขายที่ประเมินแล้ว', '0')
                    ->description('จำนวนผู้ขายที่มีการประเมิน')
                    ->descriptionIcon('heroicon-m-building-office-2')
                    ->color('primary'),
                    
                Stat::make('คะแนนเฉลี่ย', '0.00/4.00')
                    ->description('คะแนนเฉลี่ยของผู้ขายทั้งหมด')
                    ->descriptionIcon('heroicon-m-star')
                    ->color('success'),
                    
                Stat::make('ผู้ขายเกรด A', '0')
                    ->description('ผู้ขายที่มีผลงานดีเด่น')
                    ->descriptionIcon('heroicon-m-trophy')
                    ->color('success'),
                    
                Stat::make('ผู้ขายที่ควรปรับปรุง', '0')
                    ->description('ผู้ขายเกรด C และ D')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
            ];
        }

        // Get the actual latest score records
        $scores = VendorScore::whereIn('vendor_id', $vendorIds)
            ->where('company_id', $companyId)
            ->whereIn('created_at', $latestScores->pluck('latest_created')->toArray())
            ->get();

        $totalVendors = $scores->count();
        $averageScore = $totalVendors > 0 ? round($scores->avg('weighted_average_score'), 2) : 0;
        $gradeACount = $scores->where('weighted_grade', 'A')->count();
        $needsImprovementCount = $scores->whereIn('weighted_grade', ['C', 'D'])->count();

        // Calculate trends (if there's previous data)
        $previousMonth = now()->subMonth();
        $previousScores = VendorScore::where('company_id', $companyId)
            ->whereYear('created_at', $previousMonth->year)
            ->whereMonth('created_at', $previousMonth->month)
            ->count();

        $vendorsTrend = $previousScores > 0 ? (($totalVendors - $previousScores) / $previousScores * 100) : 0;
        $vendorsTrendDescription = $vendorsTrend > 0 ? 'เพิ่มขึ้น ' . round(abs($vendorsTrend), 1) . '%' : 
                                  ($vendorsTrend < 0 ? 'ลดลง ' . round(abs($vendorsTrend), 1) . '%' : 'ไม่เปลี่ยนแปลง');

        return [
            Stat::make('ผู้ขายที่ประเมินแล้ว', number_format($totalVendors))
                ->description($vendorsTrendDescription)
                ->descriptionIcon($vendorsTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color('primary'),
                
            Stat::make('คะแนนเฉลี่ย', number_format($averageScore, 2) . '/4.00')
                ->description('คะแนนเฉลี่ยของผู้ขายทั้งหมด')
                ->descriptionIcon('heroicon-m-star')
                ->color($averageScore >= 3.5 ? 'success' : ($averageScore >= 2.5 ? 'warning' : 'danger')),
                
            Stat::make('ผู้ขายเกรด A', number_format($gradeACount))
                ->description(sprintf('%.1f%% ของผู้ขายทั้งหมด', $totalVendors > 0 ? ($gradeACount / $totalVendors * 100) : 0))
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success'),
                
            Stat::make('ผู้ขายที่ควรปรับปรุง', number_format($needsImprovementCount))
                ->description(sprintf('%.1f%% ผู้ขายเกรด C-D', $totalVendors > 0 ? ($needsImprovementCount / $totalVendors * 100) : 0))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($needsImprovementCount > 0 ? 'danger' : 'success'),
        ];
    }
}