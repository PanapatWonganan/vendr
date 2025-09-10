<?php

namespace App\Filament\Widgets;

use App\Models\ValueAnalysis;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ValueAnalysisStats extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $companyId = session('company_id') ?: 2;

        // Get Purchase Requisitions for this company to filter VAs
        $prIds = \App\Models\PurchaseRequisition::where('company_id', $companyId)->pluck('id');

        // Get all approved VA records with valid budget and agreed amount for this company
        $approvedVAs = ValueAnalysis::where('status', 'approved')
            ->whereIn('purchase_requisition_id', $prIds)
            ->whereNotNull('total_budget')
            ->whereNotNull('agreed_amount')
            ->where('total_budget', '>', 0)
            ->get();

        // Get all VA records for counting for this company
        $allVAs = ValueAnalysis::whereIn('purchase_requisition_id', $prIds)->get();
        $completedVAs = ValueAnalysis::whereIn('purchase_requisition_id', $prIds)->where('status', 'completed')->count();
        $inProgressVAs = ValueAnalysis::whereIn('purchase_requisition_id', $prIds)->where('status', 'in_progress')->count();

        if ($approvedVAs->isEmpty()) {
            return [
                Stat::make('โครงการ VA ทั้งหมด', number_format($allVAs->count()))
                    ->description('จำนวนโครงการ Value Analysis')
                    ->descriptionIcon('heroicon-m-document-text')
                    ->color('primary'),

                Stat::make('VA ที่อนุมัติแล้ว', '0')
                    ->description('ยังไม่มีข้อมูลการประหยัด')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),

                Stat::make('ประหยัดเฉลี่ย', '0.00%')
                    ->description('ยังไม่มีข้อมูลการต่อรอง')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->color('success'),

                Stat::make('VA กำลังดำเนินการ', number_format($inProgressVAs))
                    ->description('รอการวิเคราะห์และอนุมัติ')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),
            ];
        }

        // Calculate savings
        $totalBudget = $approvedVAs->sum('total_budget');
        $totalAgreed = $approvedVAs->sum('agreed_amount');
        $totalSavings = $totalBudget - $totalAgreed;
        $avgSavingsPercent = $totalBudget > 0 ? ($totalSavings / $totalBudget) * 100 : 0;

        // Calculate individual project savings for max/min
        $savingsPercentages = $approvedVAs->map(function ($va) {
            return (($va->total_budget - $va->agreed_amount) / $va->total_budget) * 100;
        });

        $maxSavings = $savingsPercentages->max();
        $minSavings = $savingsPercentages->min();

        // Count projects by savings range
        $excellentSavings = $savingsPercentages->filter(fn($s) => $s >= 15)->count();
        $goodSavings = $savingsPercentages->filter(fn($s) => $s >= 5 && $s < 15)->count();

        return [
            Stat::make('โครงการ VA ทั้งหมด', number_format($allVAs->count()))
                ->description('อนุมัติแล้ว ' . $approvedVAs->count() . ' โครงการ')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('ประหยัดรวม', number_format($totalSavings / 1000000, 2) . ' ล้านบาท')
                ->description('จากงบ ' . number_format($totalBudget / 1000000, 1) . ' ล้านบาท')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('ประหยัดเฉลี่ย', number_format($avgSavingsPercent, 2) . '%')
                ->description('สูงสุด ' . number_format($maxSavings, 1) . '% | ต่ำสุด ' . number_format($minSavings, 1) . '%')
                ->descriptionIcon('heroicon-m-calculator')
                ->color($avgSavingsPercent >= 10 ? 'success' : ($avgSavingsPercent >= 5 ? 'warning' : 'danger')),

            Stat::make('โครงการดีเด่น', number_format($excellentSavings))
                ->description('ประหยัดได้มากกว่า 15% (' . number_format($excellentSavings > 0 ? ($excellentSavings / $approvedVAs->count()) * 100 : 0, 1) . '%)')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success'),
        ];
    }
}