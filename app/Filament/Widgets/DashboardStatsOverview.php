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

        // กรองตาม "ปีของเอกสารจริง" (business date) ไม่ใช่ created_at ซึ่งเป็นวันที่ import
        // PR ใช้ request_date, PO ใช้ order_date, VA ใช้ analysis_date — มี fallback เป็น created_at
        $prScope = function ($q) use ($companyId, $year) {
            return $q
                ->when($companyId, fn ($qq) => $qq->where('company_id', $companyId))
                ->when($year, fn ($qq) => $qq->whereRaw('YEAR(COALESCE(request_date, created_at)) = ?', [$year]));
        };
        $poScope = function ($q) use ($companyId, $year) {
            return $q
                ->when($companyId, fn ($qq) => $qq->where('company_id', $companyId))
                ->when($year, fn ($qq) => $qq->whereRaw('YEAR(COALESCE(order_date, created_at)) = ?', [$year]));
        };

        // Purchase Requisition Stats - filter by company + business year
        $totalPRs = PurchaseRequisition::tap($prScope)->count();
        $pendingPRs = PurchaseRequisition::tap($prScope)->where('status', 'pending_approval')->count();
        $directPurchasePRs = PurchaseRequisition::tap($prScope)
            ->whereIn('pr_type', ['direct_small', 'direct_medium'])->count();

        // Purchase Order Stats - filter by company + business year
        $totalPOs = PurchaseOrder::tap($poScope)->count();
        $pendingPOs = PurchaseOrder::tap($poScope)->where('status', 'pending_approval')->count();
        $approvedPOs = PurchaseOrder::tap($poScope)->where('status', 'approved')->count();

        // Value Analysis Stats - filter by company (through PR) + business year (analysis_date)
        $vaScope = fn ($q) => $q
            ->when($companyId, fn ($query) => $query->whereHas('purchaseRequisition', fn ($sub) => $sub->where('company_id', $companyId)))
            ->when($year, fn ($query) => $query->whereRaw('YEAR(COALESCE(analysis_date, created_at)) = ?', [$year]));
        $totalVA = ValueAnalysis::query()->tap($vaScope)->count();
        $pendingVA = ValueAnalysis::query()->tap($vaScope)->where('status', 'in_progress')->count();

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
