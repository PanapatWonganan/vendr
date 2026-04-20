<?php

namespace App\Filament\Widgets;

use App\Models\TermsOfReference;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TorStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $companyId = session('company_id');

        $query = TermsOfReference::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $thisMonth = (clone $query)->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);

        $totalThisMonth = (clone $thisMonth)->count();
        $pending = (clone $query)->whereIn('status', ['submitted', 'reviewing'])->count();
        $approvedNoPr = (clone $query)->where('status', 'approved')
            ->whereDoesntHave('purchaseRequisitions')
            ->count();
        $approvedBudget = (clone $query)->where('status', 'approved')
            ->sum('budget_estimate');

        return [
            Stat::make('TOR เดือนนี้', $totalThisMonth)
                ->description('จำนวน TOR ที่สร้างเดือนนี้')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('รอพิจารณา', $pending)
                ->description('TOR ที่ส่งพิจารณาแล้ว')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'success'),

            Stat::make('อนุมัติแล้ว (ยังไม่สร้าง PR)', $approvedNoPr)
                ->description('สามารถสร้าง PR ได้')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($approvedNoPr > 0 ? 'info' : 'gray'),

            Stat::make('มูลค่า TOR อนุมัติ', number_format($approvedBudget, 0) . ' ฿')
                ->description('มูลค่ารวม TOR ที่อนุมัติ')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
