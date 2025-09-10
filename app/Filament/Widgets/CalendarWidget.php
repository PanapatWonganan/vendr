<?php

namespace App\Filament\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Action;

class CalendarWidget extends FullCalendarWidget
{
    protected static ?int $sort = 2;
    
    // Remove model property to prevent default behaviors
    public Model | string | null $model = null;
    
    public function mount(): void
    {
        // Initialize any required properties here
    }
    
    public function getViewData(): array
    {
        return [];
    }
    
    // Disable view action
    protected function modalActions(): array
    {
        return [];
    }
    
    // Disable edit action  
    protected function viewAction(): Action
    {
        return Action::make('view')
            ->disabled()
            ->hidden();
    }
    
    // Disable all actions
    protected function getActions(): array
    {
        return [];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        $start = Carbon::parse($fetchInfo['start']);
        $end = Carbon::parse($fetchInfo['end']);

        $events = collect();
        
        // Get selected company from session
        $companyId = session('company_id');
        
        // If no company selected, return empty events
        if (!$companyId) {
            return [];
        }

        // Purchase Orders - filter by company
        $purchaseOrders = PurchaseOrder::whereBetween('expected_delivery_date', [$start, $end])
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
                'classNames' => ['non-clickable-event'],
                'extendedProps' => [
                    'entity_id' => $po->id,
                    'entity_type' => 'po',
                    'description' => 'จาก: ' . optional($po->vendor)->name . ' | จำนวน: ' . number_format($po->total_amount, 2) . ' บาท',
                    'priority' => $priority,
                ],
            ]);
        }

        // Purchase Requisitions - filter by company
        $purchaseRequisitions = PurchaseRequisition::whereBetween('required_date', [$start, $end])
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
                'classNames' => ['non-clickable-event'],
                'extendedProps' => [
                    'entity_id' => $pr->id,
                    'entity_type' => 'pr',
                    'description' => 'แผนก: ' . optional($pr->department)->name . ' | จำนวน: ' . number_format($pr->total_amount, 2) . ' บาท',
                    'priority' => $priority,
                ],
            ]);
        }

        return $events->toArray();
    }

    private function getPriority(int $days, string $type): string
    {
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
            default => '#4b5563',
        };
    }

    public function getConfig(): array
    {
        return [
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,listWeek'
            ],
            'initialView' => 'dayGridMonth',
            'locale' => 'th',
            'firstDay' => 1,
            'height' => 'auto',
            'dayMaxEvents' => 3,
            'moreLinkClick' => 'popover',
            'selectable' => false,
            'editable' => false,
        ];
    }

    // Override to disable all event clicks
    public function onEventClick(array $event): void
    {
        // Do nothing - prevent any action on event click
        return;
    }
    
    // Override to disable event URLs
    public function resolveEventRecord(array $data): Model
    {
        // Return empty model to prevent navigation
        return new PurchaseOrder();
    }
}