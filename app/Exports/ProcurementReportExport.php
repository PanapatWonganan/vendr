<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProcurementReportExport implements WithMultipleSheets
{
    protected $data;
    protected $reportType;

    public function __construct($data, $reportType)
    {
        $this->data = $data;
        $this->reportType = $reportType;
    }

    public function sheets(): array
    {
        $sheets = [];

        switch ($this->reportType) {
            case 'pr_summary':
                $sheets[] = new PRSummarySheet($this->data);
                break;
            case 'po_summary':
                $sheets[] = new POSummarySheet($this->data);
                break;
            case 'vendor_performance':
                $sheets[] = new VendorPerformanceSheet($this->data);
                break;
            case 'department_spending':
                $sheets[] = new DepartmentSpendingSheet($this->data);
                break;
            case 'approval_efficiency':
                $sheets[] = new ApprovalEfficiencySheet($this->data);
                break;
            case 'budget_utilization':
                $sheets[] = new BudgetUtilizationSheet($this->data);
                break;
        }

        return $sheets;
    }
}

class PRSummarySheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $result = [];
        
        // Overall summary
        $result[] = ['Report Type', 'Purchase Requisition Summary'];
        $result[] = ['Generated', now()->format('Y-m-d H:i:s')];
        $result[] = ['Total Count', $this->data['total_count']];
        $result[] = ['Total Amount', number_format($this->data['total_amount'], 2)];
        $result[] = [];
        
        // By Status
        $result[] = ['BY STATUS'];
        $result[] = ['Status', 'Count', 'Amount'];
        if (isset($this->data['by_status'])) {
            foreach ($this->data['by_status'] as $status) {
                $result[] = [$status->status, $status->count, number_format($status->amount, 2)];
            }
        }
        $result[] = [];
        
        // By Department
        $result[] = ['BY DEPARTMENT'];
        $result[] = ['Department', 'Count', 'Amount'];
        if (isset($this->data['by_department'])) {
            foreach ($this->data['by_department'] as $dept) {
                $result[] = [$dept->department, $dept->count, number_format($dept->amount, 2)];
            }
        }
        $result[] = [];
        
        // By Priority
        $result[] = ['BY PRIORITY'];
        $result[] = ['Priority', 'Count'];
        if (isset($this->data['by_priority'])) {
            foreach ($this->data['by_priority'] as $priority) {
                $result[] = [$priority->priority, $priority->count];
            }
        }
        
        return $result;
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'PR Summary';
    }
}

class POSummarySheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $result = [];
        
        // Overall summary
        $result[] = ['Report Type', 'Purchase Order Summary'];
        $result[] = ['Generated', now()->format('Y-m-d H:i:s')];
        $result[] = ['Total Count', $this->data['total_count']];
        $result[] = ['Total Amount', number_format($this->data['total_amount'], 2)];
        $result[] = [];
        
        // By Status
        $result[] = ['BY STATUS'];
        $result[] = ['Status', 'Count', 'Amount'];
        if (isset($this->data['by_status'])) {
            foreach ($this->data['by_status'] as $status) {
                $result[] = [$status->status, $status->count, number_format($status->amount, 2)];
            }
        }
        $result[] = [];
        
        // By Vendor
        $result[] = ['TOP 10 VENDORS'];
        $result[] = ['Vendor', 'Count', 'Amount'];
        if (isset($this->data['by_vendor'])) {
            foreach ($this->data['by_vendor'] as $vendor) {
                $result[] = [$vendor->vendor, $vendor->count, number_format($vendor->amount, 2)];
            }
        }
        
        return $result;
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'PO Summary';
    }
}

class VendorPerformanceSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $result = [];
        
        foreach ($this->data as $vendor) {
            $result[] = [
                $vendor['vendor_name'],
                $vendor['total_orders'],
                number_format($vendor['total_value'], 2),
                number_format($vendor['avg_order_value'], 2),
                $vendor['approved_orders'],
                $vendor['completed_orders'],
            ];
        }
        
        return $result;
    }

    public function headings(): array
    {
        return [
            'Vendor Name',
            'Total Orders',
            'Total Value',
            'Average Order Value',
            'Approved Orders',
            'Completed Orders',
        ];
    }

    public function title(): string
    {
        return 'Vendor Performance';
    }
}

class DepartmentSpendingSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $result = [];
        
        foreach ($this->data as $dept) {
            $result[] = [
                $dept['department_name'],
                $dept['total_orders'],
                number_format($dept['total_spending'], 2),
                number_format($dept['avg_order_value'], 2),
            ];
        }
        
        return $result;
    }

    public function headings(): array
    {
        return [
            'Department Name',
            'Total Orders',
            'Total Spending',
            'Average Order Value',
        ];
    }

    public function title(): string
    {
        return 'Department Spending';
    }
}

class ApprovalEfficiencySheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return [
            ['Report Type', 'Approval Efficiency'],
            ['Generated', now()->format('Y-m-d H:i:s')],
            [],
            ['PURCHASE REQUISITIONS'],
            ['Total Approved', $this->data['pr_efficiency']['total_approved']],
            ['Average Approval Hours', number_format($this->data['pr_efficiency']['avg_approval_hours'], 2)],
            [],
            ['PURCHASE ORDERS'],
            ['Total Approved', $this->data['po_efficiency']['total_approved']],
            ['Average Approval Hours', number_format($this->data['po_efficiency']['avg_approval_hours'], 2)],
        ];
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Approval Efficiency';
    }
}

class BudgetUtilizationSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $result = [
            ['Report Type', 'Budget Utilization'],
            ['Generated', now()->format('Y-m-d H:i:s')],
            [],
            ['Total Budget', number_format($this->data['total_budget'], 2)],
            ['Total Spent', number_format($this->data['total_spent'], 2)],
            ['Utilization Rate', number_format($this->data['utilization_rate'], 2) . '%'],
            ['Remaining Budget', number_format($this->data['remaining_budget'], 2)],
            [],
        ];
        
        if (isset($this->data['by_category']) && count($this->data['by_category']) > 0) {
            $result[] = ['BY CATEGORY'];
            $result[] = ['Category', 'Amount Spent'];
            foreach ($this->data['by_category'] as $category) {
                $result[] = [
                    $category->category ?: 'Uncategorized',
                    number_format($category->spent, 2)
                ];
            }
        }
        
        return $result;
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Budget Utilization';
    }
}