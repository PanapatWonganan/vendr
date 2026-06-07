<?php

namespace App\Filament\Widgets;

use App\Models\ContractApproval;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Models\ValueAnalysis;
use App\Models\Vendor;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'procurement_officer', 'procurement_manager', 'department_head', 'auditor']) ?? false;
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $companyId = session('company_id');

        // ข้อ 19: filter ตามปีที่เลือกบน Dashboard (default = ปีปัจจุบัน)
        $year = $this->filters['year'] ?? (int) date('Y');

        // Reusable scope: company + year (on created_at)
        $scope = function ($q) use ($companyId, $year) {
            return $q
                ->when($companyId, fn ($qq) => $qq->where('company_id', $companyId))
                ->when($year, fn ($qq) => $qq->whereYear('created_at', $year));
        };

        // Purchase Requisition Stats - filter by company + year
        $totalPRs = PurchaseRequisition::tap($scope)->count();
        $pendingPRs = PurchaseRequisition::tap($scope)->where('status', 'pending_approval')->count();
        $directPurchasePRs = PurchaseRequisition::tap($scope)
            ->whereIn('pr_type', ['direct_small', 'direct_medium'])->count();

        // Purchase Order Stats - filter by company + year
        $totalPOs = PurchaseOrder::tap($scope)->count();
        $pendingPOs = PurchaseOrder::tap($scope)->where('status', 'pending_approval')->count();
        $approvedPOs = PurchaseOrder::tap($scope)->where('status', 'approved')->count();

        // Value Analysis Stats - filter by company (through PR) + year (on VA created_at)
        $totalVA = ValueAnalysis::when($companyId, fn ($q) => $q->whereHas('purchaseRequisition', fn ($query) => $query->where('company_id', $companyId)))
            ->when($year, fn ($q) => $q->whereYear('created_at', $year))
            ->count();
        $pendingVA = ValueAnalysis::when($companyId, fn ($q) => $q->whereHas('purchaseRequisition', fn ($query) => $query->where('company_id', $companyId)))
            ->when($year, fn ($q) => $q->whereYear('created_at', $year))
            ->where('status', 'in_progress')->count();

        // Contract Approval Stats - filter by company
        $totalContracts = ContractApproval::when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();
        $pendingContracts = ContractApproval::when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'pending')->count();

        // Vendor Stats - filter by company
        $totalVendors = Vendor::when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();
        $activeVendors = Vendor::when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'approved')->count();

        // User Stats
        $totalUsers = User::count();

        // My Pending Approvals
        $myPendingApprovals = 0;
        if ($user && ($user->isAdmin() ?? false)) {
            $myPendingApprovals = $pendingPRs + $pendingPOs;
        }

        return [
            Stat::make('Purchase Requisitions', $totalPRs)
                ->description("$pendingPRs pending approval")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),

            Stat::make('Purchase Orders', $totalPOs)
                ->description("$pendingPOs pending, $approvedPOs approved")
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->chart([3, 8, 5, 12, 7, 9, 14])
                ->color('info'),

            Stat::make('Direct Purchase', $directPurchasePRs)
                ->description('Small & Medium value')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('My Pending Approvals', $myPendingApprovals)
                ->description('Waiting for your action')
                ->descriptionIcon('heroicon-m-clock')
                ->color($myPendingApprovals > 0 ? 'danger' : 'gray'),

            Stat::make('Value Analysis', $totalVA)
                ->description("$pendingVA in progress")
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color('primary'),

            Stat::make('Active Vendors', $activeVendors.'/'.$totalVendors)
                ->description('Approved vendors')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),
        ];
    }
}
