<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Models\Vendor;
use App\Models\ValueAnalysis;
use App\Models\ContractApproval;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $companyId = session('company_id');
        
        // Purchase Requisition Stats - filter by company
        $totalPRs = PurchaseRequisition::when($companyId, fn($q) => $q->where('company_id', $companyId))->count();
        $pendingPRs = PurchaseRequisition::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('status', 'pending_approval')->count();
        $directPurchasePRs = PurchaseRequisition::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereIn('pr_type', ['direct_small', 'direct_medium'])->count();
        
        // Purchase Order Stats - filter by company
        $totalPOs = PurchaseOrder::when($companyId, fn($q) => $q->where('company_id', $companyId))->count();
        $pendingPOs = PurchaseOrder::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('status', 'pending_approval')->count();
        $approvedPOs = PurchaseOrder::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('status', 'approved')->count();
        
        // Value Analysis Stats - filter by company through PR relationship
        $totalVA = ValueAnalysis::when($companyId, fn($q) => 
            $q->whereHas('purchaseRequisition', fn($query) => $query->where('company_id', $companyId))
        )->count();
        $pendingVA = ValueAnalysis::when($companyId, fn($q) => 
            $q->whereHas('purchaseRequisition', fn($query) => $query->where('company_id', $companyId))
        )->where('status', 'in_progress')->count();
        
        // Contract Approval Stats - filter by company
        $totalContracts = ContractApproval::when($companyId, fn($q) => $q->where('company_id', $companyId))->count();
        $pendingContracts = ContractApproval::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('status', 'pending')->count();
        
        // Vendor Stats - filter by company
        $totalVendors = Vendor::when($companyId, fn($q) => $q->where('company_id', $companyId))->count();
        $activeVendors = Vendor::when($companyId, fn($q) => $q->where('company_id', $companyId))
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
                
            Stat::make('Active Vendors', $activeVendors . '/' . $totalVendors)
                ->description('Approved vendors')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),
        ];
    }
}