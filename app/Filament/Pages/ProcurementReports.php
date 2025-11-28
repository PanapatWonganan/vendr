<?php

namespace App\Filament\Pages;

use App\Models\PurchaseRequisition;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use App\Exports\ProcurementReportExport;
use Maatwebsite\Excel\Facades\Excel;

class ProcurementReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Reports (รายงาน)';
    protected static string $view = 'filament.pages.procurement-reports';
    protected static ?string $navigationGroup = 'Reports & Analytics';
    protected static ?int $navigationSort = 1;

    public ?array $data = [];
    public $reportData = [];
    public $reportType = '';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Report Generator')
                    ->description('Generate custom procurement reports')
                    ->schema([
                        Select::make('report_type')
                            ->label('Report Type')
                            ->options([
                                'pr_summary' => 'Purchase Requisition Summary',
                                'po_summary' => 'Purchase Order Summary',
                                'vendor_performance' => 'Vendor Performance Report',
                                'department_spending' => 'Department Spending Analysis',
                                'approval_efficiency' => 'Approval Efficiency Report',
                                'budget_utilization' => 'Budget Utilization Report',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\Grid::make(2)->schema([
                            DatePicker::make('date_from')
                                ->label('From Date')
                                ->default(now()->startOfMonth())
                                ->required(),

                            DatePicker::make('date_to')
                                ->label('To Date')
                                ->default(now()->endOfMonth())
                                ->required(),
                        ]),

                        Select::make('departments')
                            ->label('Departments (Optional)')
                            ->multiple()
                            ->options(Department::pluck('name', 'id'))
                            ->searchable(),

                        Select::make('vendors')
                            ->label('Vendors (Optional)')
                            ->multiple()
                            ->options(Vendor::pluck('company_name', 'id'))
                            ->searchable()
                            ->visible(fn ($get) => in_array($get('report_type'), ['po_summary', 'vendor_performance'])),

                        CheckboxList::make('statuses')
                            ->label('Status Filter')
                            ->options([
                                'draft' => 'Draft',
                                'pending_approval' => 'Pending Approval',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'cancelled' => 'Cancelled',
                                'completed' => 'Completed',
                            ])
                            ->default(['approved', 'completed'])
                            ->columns(3),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateReport')
                ->label('Generate Report')
                ->icon('heroicon-o-chart-bar')
                ->color('success')
                ->action('generateReport'),

            Action::make('exportCSV')
                ->label('Export to CSV')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->action('exportCSV')
                ->visible(fn () => !empty($this->reportData)),
                
            Action::make('exportExcel')
                ->label('Export to Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action('exportExcel')
                ->visible(fn () => !empty($this->reportData)),
        ];
    }

    public function generateReport(): void
    {
        $data = $this->form->getState();
        $companyId = session('company_id');

        if (!$companyId) {
            Notification::make()
                ->title('Error')
                ->body('Please select a company first.')
                ->danger()
                ->send();
            return;
        }

        $this->reportType = $data['report_type'];
        $this->reportData = [];

        switch ($data['report_type']) {
            case 'pr_summary':
                $this->reportData = $this->generatePRSummary($data, $companyId);
                break;
            case 'po_summary':
                $this->reportData = $this->generatePOSummary($data, $companyId);
                break;
            case 'vendor_performance':
                $this->reportData = $this->generateVendorPerformance($data, $companyId);
                break;
            case 'department_spending':
                $this->reportData = $this->generateDepartmentSpending($data, $companyId);
                break;
            case 'approval_efficiency':
                $this->reportData = $this->generateApprovalEfficiency($data, $companyId);
                break;
            case 'budget_utilization':
                $this->reportData = $this->generateBudgetUtilization($data, $companyId);
                break;
        }

        Notification::make()
            ->title('Success')
            ->body('Report generated successfully!')
            ->success()
            ->send();
    }

    private function generatePRSummary($data, $companyId): array
    {
        $baseQuery = PurchaseRequisition::where('company_id', $companyId)
            ->whereBetween('purchase_requisitions.created_at', [$data['date_from'], $data['date_to']]);

        if (!empty($data['departments'])) {
            $baseQuery->whereIn('department_id', $data['departments']);
        }

        if (!empty($data['statuses'])) {
            $baseQuery->whereIn('status', $data['statuses']);
        }

        // Clone queries for different aggregations to avoid conflicts
        $totalQuery = clone $baseQuery;
        $statusQuery = clone $baseQuery;
        $departmentQuery = clone $baseQuery;
        $priorityQuery = clone $baseQuery;

        return [
            'total_count' => $totalQuery->count(),
            'total_amount' => $totalQuery->sum('total_amount'),
            'by_status' => $statusQuery->groupBy('status')
                ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as amount'))
                ->get(),
            'by_department' => $departmentQuery->join('departments', 'purchase_requisitions.department_id', '=', 'departments.id')
                ->groupBy('status', 'departments.name')
                ->select('departments.name as department', DB::raw('count(*) as count'), DB::raw('sum(purchase_requisitions.total_amount) as amount'))
                ->get(),
            'by_priority' => $priorityQuery->groupBy('priority')
                ->select('priority', DB::raw('count(*) as count'))
                ->get(),
        ];
    }

    private function generatePOSummary($data, $companyId): array
    {
        $query = PurchaseOrder::where('purchase_orders.company_id', $companyId)
            ->whereBetween('purchase_orders.created_at', [$data['date_from'], $data['date_to']]);

        if (!empty($data['departments'])) {
            $query->whereIn('purchase_orders.department_id', $data['departments']);
        }

        if (!empty($data['vendors'])) {
            $query->whereIn('purchase_orders.vendor_id', $data['vendors']);
        }

        if (!empty($data['statuses'])) {
            $query->whereIn('purchase_orders.status', $data['statuses']);
        }

        return [
            'total_count' => $query->count(),
            'total_amount' => $query->sum('purchase_orders.total_amount'),
            'by_status' => $query->groupBy('purchase_orders.status')
                ->select('purchase_orders.status as status', DB::raw('count(*) as count'), DB::raw('sum(purchase_orders.total_amount) as amount'))
                ->get(),
            'by_vendor' => $query->join('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
                ->groupBy('vendors.company_name')
                ->select('vendors.company_name as vendor', DB::raw('count(purchase_orders.id) as count'), DB::raw('sum(purchase_orders.total_amount) as amount'))
                ->orderByDesc('amount')
                ->limit(10)
                ->get(),
        ];
    }

    private function generateVendorPerformance($data, $companyId): array
    {
        $query = PurchaseOrder::where('purchase_orders.company_id', $companyId)
            ->whereBetween('purchase_orders.created_at', [$data['date_from'], $data['date_to']]);

        if (!empty($data['vendors'])) {
            $query->whereIn('purchase_orders.vendor_id', $data['vendors']);
        }

        return $query->join('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
            ->groupBy('vendors.id', 'vendors.company_name')
            ->select(
                'vendors.company_name as vendor_name',
                DB::raw('COUNT(purchase_orders.id) as total_orders'),
                DB::raw('SUM(purchase_orders.total_amount) as total_value'),
                DB::raw('AVG(purchase_orders.total_amount) as avg_order_value'),
                DB::raw('COUNT(CASE WHEN purchase_orders.status = "approved" THEN 1 END) as approved_orders'),
                DB::raw('COUNT(CASE WHEN purchase_orders.status = "completed" THEN 1 END) as completed_orders')
            )
            ->orderByDesc('total_value')
            ->get()
            ->toArray();
    }

    private function generateDepartmentSpending($data, $companyId): array
    {
        $query = PurchaseOrder::where('purchase_orders.company_id', $companyId)
            ->whereBetween('purchase_orders.created_at', [$data['date_from'], $data['date_to']]);

        if (!empty($data['departments'])) {
            $query->whereIn('purchase_orders.department_id', $data['departments']);
        }

        return $query->join('departments', 'purchase_orders.department_id', '=', 'departments.id')
            ->groupBy('departments.id', 'departments.name')
            ->select(
                'departments.name as department_name',
                DB::raw('COUNT(purchase_orders.id) as total_orders'),
                DB::raw('SUM(purchase_orders.total_amount) as total_spending'),
                DB::raw('AVG(purchase_orders.total_amount) as avg_order_value')
            )
            ->orderByDesc('total_spending')
            ->get()
            ->toArray();
    }

    private function generateApprovalEfficiency($data, $companyId): array
    {
        $prQuery = PurchaseRequisition::where('purchase_requisitions.company_id', $companyId)
            ->whereBetween('purchase_requisitions.created_at', [$data['date_from'], $data['date_to']])
            ->whereNotNull('purchase_requisitions.approved_at');

        $poQuery = PurchaseOrder::where('purchase_orders.company_id', $companyId)
            ->whereBetween('purchase_orders.created_at', [$data['date_from'], $data['date_to']])
            ->whereNotNull('purchase_orders.approved_at');

        return [
            'pr_efficiency' => [
                'total_approved' => $prQuery->count(),
                'avg_approval_hours' => $prQuery->selectRaw('AVG(TIMESTAMPDIFF(HOUR, purchase_requisitions.created_at, purchase_requisitions.approved_at)) as avg_hours')->first()->avg_hours ?? 0,
            ],
            'po_efficiency' => [
                'total_approved' => $poQuery->count(),
                'avg_approval_hours' => $poQuery->selectRaw('AVG(TIMESTAMPDIFF(HOUR, purchase_orders.created_at, purchase_orders.approved_at)) as avg_hours')->first()->avg_hours ?? 0,
            ],
        ];
    }

    private function generateBudgetUtilization($data, $companyId): array
    {
        $baseQuery = PurchaseOrder::where('purchase_orders.company_id', $companyId)
            ->whereBetween('purchase_orders.created_at', [$data['date_from'], $data['date_to']])
            ->where('purchase_orders.status', 'approved');

        $budgetQuery = clone $baseQuery;
        $spentQuery = clone $baseQuery;
        $categoryQuery = clone $baseQuery;

        $totalBudget = $budgetQuery->sum('procurement_budget');
        $totalSpent = $spentQuery->sum('total_amount');

        return [
            'total_budget' => $totalBudget,
            'total_spent' => $totalSpent,
            'utilization_rate' => $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0,
            'remaining_budget' => $totalBudget - $totalSpent,
            'by_category' => $categoryQuery->groupBy('category')
                ->select('category', DB::raw('sum(total_amount) as spent'))
                ->get(),
        ];
    }

    public function exportCSV()
    {
        if (empty($this->reportData)) {
            Notification::make()
                ->title('Error')
                ->body('No data to export. Generate a report first.')
                ->danger()
                ->send();
            return;
        }

        // Generate CSV export
        $filename = 'procurement_report_' . $this->reportType . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        return response()->streamDownload(function () {
            $csv = $this->generateCSV($this->reportData, $this->reportType);
            echo $csv;
        }, $filename);
    }

    private function generateCSV($data, $reportType): string
    {
        $csv = '';
        
        switch ($reportType) {
            case 'pr_summary':
                // Create a flat CSV structure with all data
                $csv = "Type,Category,Subcategory,Value1,Value2,Value3\n";
                
                // Overall summary
                $csv .= "Summary,Overall,Total Count,{$data['total_count']},,\n";
                $csv .= "Summary,Overall,Total Amount," . number_format($data['total_amount'], 2, '.', '') . ",,\n";
                
                // By Status
                if (isset($data['by_status'])) {
                    foreach ($data['by_status'] as $status) {
                        $csv .= "Status,{$status->status},Count,{$status->count},,\n";
                        $csv .= "Status,{$status->status},Amount," . number_format($status->amount, 2, '.', '') . ",,\n";
                    }
                }
                
                // By Department
                if (isset($data['by_department'])) {
                    foreach ($data['by_department'] as $dept) {
                        $deptName = str_replace('"', '""', $dept->department);
                        $csv .= "Department,\"{$deptName}\",Count,{$dept->count},,\n";
                        $csv .= "Department,\"{$deptName}\",Amount," . number_format($dept->amount, 2, '.', '') . ",,\n";
                    }
                }
                
                // By Priority
                if (isset($data['by_priority'])) {
                    foreach ($data['by_priority'] as $priority) {
                        $csv .= "Priority,{$priority->priority},Count,{$priority->count},,\n";
                    }
                }
                break;
                
            case 'po_summary':
                // Create a flat CSV structure
                $csv = "Type,Category,Subcategory,Value1,Value2,Value3\n";
                
                // Overall summary
                $csv .= "Summary,Overall,Total Count,{$data['total_count']},,\n";
                $csv .= "Summary,Overall,Total Amount," . number_format($data['total_amount'], 2, '.', '') . ",,\n";
                
                // By Status
                if (isset($data['by_status'])) {
                    foreach ($data['by_status'] as $status) {
                        $csv .= "Status,{$status->status},Count,{$status->count},,\n";
                        $csv .= "Status,{$status->status},Amount," . number_format($status->amount, 2, '.', '') . ",,\n";
                    }
                }
                
                // By Vendor (Top 10)
                if (isset($data['by_vendor'])) {
                    foreach ($data['by_vendor'] as $vendor) {
                        $vendorName = str_replace('"', '""', $vendor->vendor);
                        $csv .= "Vendor,\"{$vendorName}\",Count,{$vendor->count},,\n";
                        $csv .= "Vendor,\"{$vendorName}\",Amount," . number_format($vendor->amount, 2, '.', '') . ",,\n";
                    }
                }
                break;
                
            case 'vendor_performance':
                // Standard table format for vendor performance
                $csv = "Vendor Name,Total Orders,Total Value,Average Order Value,Approved Orders,Completed Orders\n";
                foreach ($data as $vendor) {
                    $vendorName = str_replace('"', '""', $vendor['vendor_name']);
                    $csv .= "\"{$vendorName}\"," . 
                            $vendor['total_orders'] . "," .
                            number_format($vendor['total_value'], 2, '.', '') . "," .
                            number_format($vendor['avg_order_value'], 2, '.', '') . "," .
                            $vendor['approved_orders'] . "," .
                            $vendor['completed_orders'] . "\n";
                }
                break;
                
            case 'department_spending':
                // Standard table format for department spending
                $csv = "Department Name,Total Orders,Total Spending,Average Order Value\n";
                foreach ($data as $dept) {
                    $deptName = str_replace('"', '""', $dept['department_name']);
                    $csv .= "\"{$deptName}\"," .
                            $dept['total_orders'] . "," .
                            number_format($dept['total_spending'], 2, '.', '') . "," .
                            number_format($dept['avg_order_value'], 2, '.', '') . "\n";
                }
                break;
                
            case 'approval_efficiency':
                // Structured format for approval efficiency
                $csv = "Type,Metric,Value\n";
                $csv .= "Purchase Requisitions,Total Approved," . $data['pr_efficiency']['total_approved'] . "\n";
                $csv .= "Purchase Requisitions,Average Approval Hours," . number_format($data['pr_efficiency']['avg_approval_hours'], 2, '.', '') . "\n";
                $csv .= "Purchase Orders,Total Approved," . $data['po_efficiency']['total_approved'] . "\n";
                $csv .= "Purchase Orders,Average Approval Hours," . number_format($data['po_efficiency']['avg_approval_hours'], 2, '.', '') . "\n";
                break;
                
            case 'budget_utilization':
                // Structured format for budget utilization
                $csv = "Category,Metric,Value\n";
                $csv .= "Overall,Total Budget," . number_format($data['total_budget'], 2, '.', '') . "\n";
                $csv .= "Overall,Total Spent," . number_format($data['total_spent'], 2, '.', '') . "\n";
                $csv .= "Overall,Utilization Rate," . number_format($data['utilization_rate'], 2, '.', '') . "%\n";
                $csv .= "Overall,Remaining Budget," . number_format($data['remaining_budget'], 2, '.', '') . "\n";
                
                if (isset($data['by_category']) && count($data['by_category']) > 0) {
                    foreach ($data['by_category'] as $category) {
                        $catName = str_replace('"', '""', $category->category ?: 'Uncategorized');
                        $csv .= "\"{$catName}\",Amount Spent," . number_format($category->spent, 2, '.', '') . "\n";
                    }
                }
                break;
                
            default:
                $csv = "Report data\n";
                $csv .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n\n";
                $csv .= json_encode($data, JSON_PRETTY_PRINT);
                break;
        }
        
        return $csv;
    }
    
    public function exportExcel()
    {
        if (empty($this->reportData)) {
            Notification::make()
                ->title('Error')
                ->body('No data to export. Generate a report first.')
                ->danger()
                ->send();
            return;
        }

        $filename = 'procurement_report_' . $this->reportType . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(
            new ProcurementReportExport($this->reportData, $this->reportType),
            $filename
        );
    }
}