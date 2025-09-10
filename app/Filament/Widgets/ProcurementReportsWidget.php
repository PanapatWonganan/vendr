<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseRequisition;
use App\Models\PurchaseOrder;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class ProcurementReportsWidget extends Widget
{
    protected static string $view = 'filament.widgets.procurement-reports-widget';
    protected static ?int $sort = 4;

    protected function getViewData(): array
    {
        $companyId = session('company_id');
        
        if (!$companyId) {
            return [
                'hasData' => false,
                'message' => 'Please select a company to view reports',
            ];
        }

        // Monthly procurement trends (last 6 months)
        $monthlyData = collect();
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            
            $prCount = PurchaseRequisition::where('company_id', $companyId)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
                
            $poCount = PurchaseOrder::where('company_id', $companyId)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
                
            $poValue = PurchaseOrder::where('company_id', $companyId)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('total_amount');
            
            $monthlyData->push([
                'month' => $month->format('M Y'),
                'pr_count' => $prCount,
                'po_count' => $poCount,
                'po_value' => $poValue,
            ]);
        }

        // Top spending departments (current year)
        $departmentSpending = PurchaseOrder::where('purchase_orders.company_id', $companyId)
            ->whereYear('purchase_orders.created_at', now()->year)
            ->join('departments', 'purchase_orders.department_id', '=', 'departments.id')
            ->groupBy('departments.id', 'departments.name')
            ->select(
                'departments.name as department_name',
                DB::raw('SUM(purchase_orders.total_amount) as total_spent'),
                DB::raw('COUNT(purchase_orders.id) as order_count')
            )
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get();

        // Approval efficiency (average time from creation to approval)
        $avgApprovalTime = PurchaseRequisition::where('company_id', $companyId)
            ->where('status', 'approved')
            ->whereNotNull('approved_at')
            ->select(
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, approved_at)) as avg_hours'),
                DB::raw('COUNT(*) as total_approved')
            )
            ->first();

        // Vendor performance (orders per vendor)
        $vendorPerformance = PurchaseOrder::where('purchase_orders.company_id', $companyId)
            ->whereYear('purchase_orders.created_at', now()->year)
            ->join('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
            ->groupBy('vendors.id', 'vendors.company_name')
            ->select(
                'vendors.company_name as vendor_name',
                DB::raw('COUNT(purchase_orders.id) as order_count'),
                DB::raw('SUM(purchase_orders.total_amount) as total_value')
            )
            ->orderByDesc('total_value')
            ->limit(5)
            ->get();

        return [
            'hasData' => true,
            'monthlyData' => $monthlyData,
            'departmentSpending' => $departmentSpending,
            'avgApprovalTime' => $avgApprovalTime,
            'vendorPerformance' => $vendorPerformance,
            'companyId' => $companyId,
        ];
    }
}