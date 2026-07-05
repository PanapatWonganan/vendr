<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class ProcurementReportsWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.procurement-reports-widget';

    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'procurement_officer', 'procurement_manager', 'auditor']) ?? false;
    }

    protected function getViewData(): array
    {
        $companyId = session('company_id');
        $year = $this->filters['year'] ?? (int) date('Y');

        if (! $companyId) {
            return [
                'hasData' => false,
                'message' => 'Please select a company to view reports',
            ];
        }

        // Anchor the 6-month trend window to the selected year:
        // current year -> ending this month; past year -> ending December of that year.
        $anchor = ($year && $year < (int) now()->year)
            ? now()->setDate($year, 12, 1)
            : now();

        // Monthly procurement trends (last 6 months up to the anchor)
        $monthlyData = collect();
        for ($i = 5; $i >= 0; $i--) {
            $month = (clone $anchor)->subMonths($i);

            $prCount = PurchaseRequisition::where('company_id', $companyId)
                ->whereRaw('YEAR(COALESCE(request_date, created_at)) = ?', [$month->year])
                ->whereRaw('MONTH(COALESCE(request_date, created_at)) = ?', [$month->month])
                ->count();

            $poCount = PurchaseOrder::where('company_id', $companyId)
                ->whereRaw('YEAR(COALESCE(order_date, created_at)) = ?', [$month->year])
                ->whereRaw('MONTH(COALESCE(order_date, created_at)) = ?', [$month->month])
                ->count();

            $poValue = PurchaseOrder::where('company_id', $companyId)
                ->whereRaw('YEAR(COALESCE(order_date, created_at)) = ?', [$month->year])
                ->whereRaw('MONTH(COALESCE(order_date, created_at)) = ?', [$month->month])
                ->sum('total_amount');

            $monthlyData->push([
                'month' => $month->format('M Y'),
                'pr_count' => $prCount,
                'po_count' => $poCount,
                'po_value' => $poValue,
            ]);
        }

        // Top spending departments (selected year, by PO order_date)
        $departmentSpending = PurchaseOrder::where('purchase_orders.company_id', $companyId)
            ->whereRaw('YEAR(COALESCE(purchase_orders.order_date, purchase_orders.created_at)) = ?', [$year])
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

        // Approval efficiency (average time from request to approval) for the selected year
        $avgApprovalTime = PurchaseRequisition::where('company_id', $companyId)
            ->where('status', 'approved')
            ->whereNotNull('approved_at')
            ->when($year, fn ($q) => $q->whereRaw('YEAR(COALESCE(request_date, created_at)) = ?', [$year]))
            ->select(
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, COALESCE(request_date, created_at), approved_at)) as avg_hours'),
                DB::raw('COUNT(*) as total_approved')
            )
            ->first();

        // Vendor performance (orders per vendor) for the selected year, by PO order_date
        $vendorPerformance = PurchaseOrder::where('purchase_orders.company_id', $companyId)
            ->whereRaw('YEAR(COALESCE(purchase_orders.order_date, purchase_orders.created_at)) = ?', [$year])
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
