<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseRequisition;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ProcurementStatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected function getStats(): array
    {
        $companyId = session('company_id');
        
        if (!$companyId) {
            return [
                Stat::make('Select Company', 'Please select a company to view statistics')
                    ->description('Use the company selector above')
                    ->descriptionIcon('heroicon-m-building-office')
                    ->color('warning'),
            ];
        }

        // Purchase Requisitions Stats
        $totalPRs = PurchaseRequisition::when($companyId, function ($query) use ($companyId) {
            return $query->where('company_id', $companyId);
        })->count();

        $pendingPRs = PurchaseRequisition::when($companyId, function ($query) use ($companyId) {
            return $query->where('company_id', $companyId);
        })->where('status', 'pending_approval')->count();

        $approvedPRs = PurchaseRequisition::when($companyId, function ($query) use ($companyId) {
            return $query->where('company_id', $companyId);
        })->where('status', 'approved')->count();

        // Purchase Orders Stats
        $totalPOs = PurchaseOrder::when($companyId, function ($query) use ($companyId) {
            return $query->where('company_id', $companyId);
        })->count();

        $pendingPOs = PurchaseOrder::when($companyId, function ($query) use ($companyId) {
            return $query->where('company_id', $companyId);
        })->where('status', 'pending_approval')->count();

        $approvedPOs = PurchaseOrder::when($companyId, function ($query) use ($companyId) {
            return $query->where('company_id', $companyId);
        })->where('status', 'approved')->count();

        // Total Values
        $totalPOValue = PurchaseOrder::when($companyId, function ($query) use ($companyId) {
            return $query->where('company_id', $companyId);
        })->sum('total_amount');

        $thisMonthPOValue = PurchaseOrder::when($companyId, function ($query) use ($companyId) {
            return $query->where('company_id', $companyId);
        })->whereMonth('created_at', now()->month)
          ->whereYear('created_at', now()->year)
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

            Stat::make('Total PO Value', '฿ ' . number_format($totalPOValue, 2))
                ->description('฿ ' . number_format($thisMonthPOValue, 2) . ' this month')
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