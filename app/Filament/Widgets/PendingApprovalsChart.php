<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseRequisition;
use App\Models\PurchaseOrder;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class PendingApprovalsChart extends ChartWidget
{
    protected static ?string $heading = 'Pending Approvals Overview';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $user = Auth::user();
        $companyId = session('company_id');
        
        if (!$companyId) {
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

        // Get pending PRs that current user can approve
        $pendingPRsQuery = PurchaseRequisition::where('company_id', $companyId)
            ->where('status', 'pending_approval');
            
        if (!$user->hasRole('admin') && !$user->hasRole('procurement_manager')) {
            if ($user->hasRole('department_head') && $user->department_id) {
                $pendingPRsQuery->where('department_id', $user->department_id);
            } else {
                $pendingPRsQuery->where('pr_approver_id', $user->id);
            }
        }
        
        $pendingPRs = $pendingPRsQuery->count();

        // Get pending POs that current user can approve
        $pendingPOsQuery = PurchaseOrder::where('company_id', $companyId)
            ->where('status', 'pending_approval');
            
        if (!$user->hasRole('admin') && !$user->hasRole('procurement_manager')) {
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
            ->count();
            
        $allPendingPOs = PurchaseOrder::where('company_id', $companyId)
            ->where('status', 'pending_approval')
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