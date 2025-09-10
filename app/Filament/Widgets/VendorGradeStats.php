<?php

namespace App\Filament\Widgets;

use App\Models\VendorScore;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VendorGradeStats extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $companyId = session('company_id') ?: 2; // Default to company ID 2 for testing
        
        if (!$companyId) {
            return [
                Stat::make('ผู้ขายที่ประเมิน', '0'),
                Stat::make('เกรด A', '0%'),
                Stat::make('เกรด B', '0%'),
                Stat::make('ต้องปรับปรุง', '0%'),
            ];
        }

        // Get latest scores for each vendor
        $scores = VendorScore::where('company_id', $companyId)
            ->whereNotNull('weighted_grade')
            ->get()
            ->groupBy('vendor_id')
            ->map(function ($vendorScores) {
                return $vendorScores->sortByDesc('created_at')->first();
            });

        $totalVendors = $scores->count();
        
        if ($totalVendors === 0) {
            return [
                Stat::make('ผู้ขายที่ประเมิน', '0'),
                Stat::make('เกรด A', '0%'),
                Stat::make('เกรด B', '0%'), 
                Stat::make('ต้องปรับปรุง', '0%'),
            ];
        }

        $gradeA = $scores->where('weighted_grade', 'A')->count();
        $gradeB = $scores->where('weighted_grade', 'B')->count();
        $gradeCandD = $scores->whereIn('weighted_grade', ['C', 'D'])->count();

        $percentA = round(($gradeA / $totalVendors) * 100, 1);
        $percentB = round(($gradeB / $totalVendors) * 100, 1);
        $percentCD = round(($gradeCandD / $totalVendors) * 100, 1);

        return [
            Stat::make('ผู้ขายที่ประเมิน', (string) $totalVendors)
                ->description('จำนวนผู้ขายที่มีการประเมิน')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),
                
            Stat::make('เกรด A', $percentA . '%')
                ->description($gradeA . ' ราย - ดีมาก')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success'),
                
            Stat::make('เกรด B', $percentB . '%') 
                ->description($gradeB . ' ราย - ดี')
                ->descriptionIcon('heroicon-m-star')
                ->color('info'),
                
            Stat::make('ต้องปรับปรุง', $percentCD . '%')
                ->description($gradeCandD . ' ราย - เกรด C,D')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}