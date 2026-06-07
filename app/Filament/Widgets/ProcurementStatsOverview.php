<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Vendor;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProcurementStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'procurement_officer', 'procurement_manager', 'department_head', 'auditor']) ?? false;
    }

    protected function getStats(): array
    {
        $companyId = session('company_id');

        if (! $companyId) {
            return [
                Stat::make('Select Company', 'Please select a company to view statistics')
                    ->description('Use the company selector above')
                    ->descriptionIcon('heroicon-m-building-office')
                    ->color('warning'),
            ];
        }

        // ข้อ 19: filter ตามปีที่เลือกบน Dashboard
        $year = $this->filters['year'] ?? (int) date('Y');
        $scope = function ($q) use ($companyId, $year) {
            return $q
                ->when($companyId, fn ($qq) => $qq->where('company_id', $companyId))
                ->when($year, fn ($qq) => $qq->whereYear('created_at', $year));
        };

        // Purchase Requisitions Stats
        $totalPRs = PurchaseRequisition::tap($scope)->count();
        $pendingPRs = PurchaseRequisition::tap($scope)->where('status', 'pending_approval')->count();
        $approvedPRs = PurchaseRequisition::tap($scope)->where('status', 'approved')->count();

        // Purchase Orders Stats
        $totalPOs = PurchaseOrder::tap($scope)->count();
        $pendingPOs = PurchaseOrder::tap($scope)->where('status', 'pending_approval')->count();
        $approvedPOs = PurchaseOrder::tap($scope)->where('status', 'approved')->count();

        // Total Values (within selected year)
        $totalPOValue = PurchaseOrder::tap($scope)->sum('total_amount');

        $thisMonthPOValue = PurchaseOrder::tap($scope)
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');

        // Vendors count
        $totalVendors = Vendor::when($companyId, function ($query) use ($companyId) {
            return $query->where('company_id', $companyId);
        })->count();

        return [
            Stat::make('Purchase Requisitions', $totalPRs)
                ->description("{$pendingPRs} pending approval, {$approvedPRs} approved")
                ->descriptionIcon('heroicon-m-document-text')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color($pendingPRs > 0 ? 'warning' : 'success'),

            Stat::make('Purchase Orders', $totalPOs)
                ->description("{$pendingPOs} pending approval, {$approvedPOs} approved")
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->chart([2, 10, 3, 15, 4, 17, 7])
                ->color($pendingPOs > 0 ? 'warning' : 'success'),

            Stat::make('Total PO Value', '฿ '.number_format($totalPOValue, 2))
                ->description('฿ '.number_format($thisMonthPOValue, 2).' this month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([15, 4, 17, 7, 2, 10, 3])
                ->color('info'),

            Stat::make('Active Vendors', $totalVendors)
                ->description('Total registered vendors')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->chart([10, 3, 15, 4, 17, 7, 2])
                ->color('success'),
        ];
    }
}
