<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;

class PendingApprovalsChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Pending Approvals Overview';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'department_head', 'procurement_officer', 'procurement_manager', 'auditor']) ?? false;
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $companyId = session('company_id');
        $year = $this->filters['year'] ?? (int) date('Y');

        if (! $companyId) {
            return [
                'datasets' => [
                    [
                        'label' => 'No data available',
                        'data' => [0, 0, 0, 0],
                        'backgroundColor' => ['#ef4444'],
                    ],
                ],
                'labels' => ['Select Company'],
            ];
        }

        // Get pending PRs that current user can approve (กรองด้วยปีของเอกสาร ไม่ใช่ created_at)
        $pendingPRsQuery = PurchaseRequisition::where('company_id', $companyId)
            ->where('status', 'pending_approval')
            ->when($year, fn ($q) => $q->whereRaw('YEAR(COALESCE(request_date, created_at)) = ?', [$year]));

        if (! $user->hasRole('admin') && ! $user->hasRole('procurement_manager')) {
            if ($user->hasRole('department_head') && $user->department_id) {
                $pendingPRsQuery->where('department_id', $user->department_id);
            } else {
                $pendingPRsQuery->where('pr_approver_id', $user->id);
            }
        }

        $pendingPRs = $pendingPRsQuery->count();

        // Get pending POs that current user can approve (กรองด้วยปีของเอกสาร ไม่ใช่ created_at)
        $pendingPOsQuery = PurchaseOrder::where('company_id', $companyId)
            ->where('status', 'pending_approval')
            ->when($year, fn ($q) => $q->whereRaw('YEAR(COALESCE(order_date, created_at)) = ?', [$year]));

        if (! $user->hasRole('admin') && ! $user->hasRole('procurement_manager')) {
            if ($user->hasRole('department_head') && $user->department_id) {
                $pendingPOsQuery->where('department_id', $user->department_id);
            } else {
                // Temporarily disabled - po_approver_id column doesn't exist
                // $pendingPOsQuery->where('po_approver_id', $user->id);
            }
        }

        $pendingPOs = $pendingPOsQuery->count();

        // Get all pending items (for managers/admin)
        $allPendingPRs = PurchaseRequisition::where('company_id', $companyId)
            ->where('status', 'pending_approval')
            ->when($year, fn ($q) => $q->whereRaw('YEAR(COALESCE(request_date, created_at)) = ?', [$year]))
            ->count();

        $allPendingPOs = PurchaseOrder::where('company_id', $companyId)
            ->where('status', 'pending_approval')
            ->when($year, fn ($q) => $q->whereRaw('YEAR(COALESCE(order_date, created_at)) = ?', [$year]))
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'My Pending Approvals',
                    'data' => [$pendingPRs, $pendingPOs],
                    'backgroundColor' => ['#f59e0b', '#ef4444'],
                ],
                [
                    'label' => 'All Pending (Company)',
                    'data' => [$allPendingPRs, $allPendingPOs],
                    'backgroundColor' => ['#fbbf24', '#fca5a5'],
                ],
            ],
            'labels' => ['Purchase Requisitions', 'Purchase Orders'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
