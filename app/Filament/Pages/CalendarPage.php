<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\GoodsReceipt;
use Carbon\Carbon;

class CalendarPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'ปฏิทิน';
    protected static ?string $title = 'ปฏิทินกำหนดการส่งมอบงาน';
    protected static string $view = 'filament.pages.calendar-page';
    protected static ?int $navigationSort = 2;

    public function getViewData(): array
    {
        $calendarEvents = $this->getCalendarEvents();

        return [
            'calendarEvents' => $calendarEvents
        ];
    }

    private function getCalendarEvents()
    {
        $events = collect();
        $now = Carbon::now();
        $startDate = $now->copy()->subDays(30); // Show past 30 days
        $endDate = $now->copy()->addDays(60); // Show next 60 days

        // Get selected company from session
        $companyId = session('company_id');

        // If no company selected, return empty events
        if (!$companyId) {
            return collect();
        }

        // Purchase Orders with delivery dates
        $purchaseOrders = PurchaseOrder::whereBetween('expected_delivery_date', [$startDate, $endDate])
            ->where('company_id', $companyId)
            ->with('vendor')
            ->whereNotNull('expected_delivery_date')
            ->get();

        foreach ($purchaseOrders as $po) {
            $daysUntilDelivery = Carbon::now()->diffInDays($po->expected_delivery_date, false);
            $priority = $this->getPriority($daysUntilDelivery, 'po');

            $events->push([
                'id' => 'po_' . $po->id,
                'title' => 'PO: ' . $po->po_number,
                'start' => $po->expected_delivery_date->format('Y-m-d'),
                'backgroundColor' => $this->getColor($priority),
                'borderColor' => $this->getBorderColor($priority),
                'extendedProps' => [
                    'entity_id' => $po->id,
                    'entity_type' => 'po',
                    'description' => 'Vendor: ' . optional($po->vendor)->name . ' | Amount: ฿' . number_format($po->total_amount, 2),
                    'priority' => $priority,
                ],
            ]);
        }

        // Purchase Requisitions with required dates
        $purchaseRequisitions = PurchaseRequisition::whereBetween('required_date', [$startDate, $endDate])
            ->where('company_id', $companyId)
            ->with('department')
            ->whereNotNull('required_date')
            ->get();

        foreach ($purchaseRequisitions as $pr) {
            $daysUntilRequired = Carbon::now()->diffInDays($pr->required_date, false);
            $priority = $this->getPriority($daysUntilRequired, 'pr');

            $events->push([
                'id' => 'pr_' . $pr->id,
                'title' => 'PR: ' . $pr->pr_number,
                'start' => $pr->required_date->format('Y-m-d'),
                'backgroundColor' => $this->getColor($priority),
                'borderColor' => $this->getBorderColor($priority),
                'extendedProps' => [
                    'entity_id' => $pr->id,
                    'entity_type' => 'pr',
                    'description' => 'Department: ' . optional($pr->department)->name . ' | Amount: ฿' . number_format($pr->total_amount, 2),
                    'priority' => $priority,
                ],
            ]);
        }

        // Goods Receipts with receipt dates
        $goodsReceipts = GoodsReceipt::whereBetween('receipt_date', [$startDate, $endDate])
            ->where('company_id', $companyId)
            ->with(['vendor', 'purchaseOrder'])
            ->whereNotNull('receipt_date')
            ->get();

        foreach ($goodsReceipts as $gr) {
            $daysUntilReceipt = Carbon::now()->diffInDays($gr->receipt_date, false);
            $priority = $this->getPriority($daysUntilReceipt, 'gr');

            $events->push([
                'id' => 'gr_' . $gr->id,
                'title' => 'GR: ' . $gr->gr_number,
                'start' => $gr->receipt_date->format('Y-m-d'),
                'backgroundColor' => $this->getColor($priority),
                'borderColor' => $this->getBorderColor($priority),
                'extendedProps' => [
                    'entity_id' => $gr->id,
                    'entity_type' => 'gr',
                    'description' => 'Vendor: ' . optional($gr->vendor)->name . ' | PO: ' . optional($gr->purchaseOrder)->po_number . ' | Status: ' . $gr->inspection_status_label,
                    'priority' => $priority,
                ],
            ]);
        }

        return $events;
    }

    private function getPriority(int $days, string $type): string
    {
        // For GR (Goods Receipt), use different logic since it's already completed
        if ($type === 'gr') {
            if ($days < 0) return 'gr_past';  // Past receipts
            if ($days <= 3) return 'gr_recent';  // Recent/upcoming
            return 'gr_future';  // Future receipts
        }

        if ($days < 0) return $type . '_overdue';
        if ($days <= 3) return $type . '_urgent';
        if ($days <= 7) return $type . '_high';
        return $type . '_normal';
    }

    private function getColor(string $priority): string
    {
        return match($priority) {
            'po_overdue' => '#ef4444',
            'po_urgent' => '#f97316',
            'po_high' => '#eab308',
            'po_normal' => '#3b82f6',
            'pr_overdue' => '#dc2626',
            'pr_urgent' => '#ea580c',
            'pr_high' => '#ca8a04',
            'pr_normal' => '#059669',
            'gr_past' => '#8b5cf6',      // Purple for past receipts
            'gr_recent' => '#a855f7',    // Light purple for recent
            'gr_future' => '#c084fc',    // Lighter purple for future
            default => '#6b7280',
        };
    }

    private function getBorderColor(string $priority): string
    {
        return match($priority) {
            'po_overdue' => '#dc2626',
            'po_urgent' => '#ea580c',
            'po_high' => '#ca8a04',
            'po_normal' => '#2563eb',
            'pr_overdue' => '#b91c1c',
            'pr_urgent' => '#c2410c',
            'pr_high' => '#a16207',
            'pr_normal' => '#047857',
            'gr_past' => '#7c3aed',
            'gr_recent' => '#9333ea',
            'gr_future' => '#a855f7',
            default => '#4b5563',
        };
    }
}