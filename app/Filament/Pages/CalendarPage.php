<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\GoodsReceipt;
use Carbon\Carbon;

class CalendarPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'ปฏิทิน';
    protected static ?string $title = 'ปฏิทินการตรวจรับสินค้า (GR)';
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

        // Goods Receipts with receipt dates
        $goodsReceipts = GoodsReceipt::whereBetween('receipt_date', [$startDate, $endDate])
            ->where('company_id', $companyId)
            ->with(['vendor', 'purchaseOrder'])
            ->whereNotNull('receipt_date')
            ->get();

        foreach ($goodsReceipts as $gr) {
            $daysUntilReceipt = Carbon::now()->diffInDays($gr->receipt_date, false);
            $priority = $this->getPriority($daysUntilReceipt);

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

    private function getPriority(int $days): string
    {
        if ($days < 0) return 'gr_past';
        if ($days <= 3) return 'gr_recent';
        return 'gr_future';
    }

    private function getColor(string $priority): string
    {
        return match($priority) {
            'gr_past' => '#8b5cf6',
            'gr_recent' => '#a855f7',
            'gr_future' => '#c084fc',
            default => '#6b7280',
        };
    }

    private function getBorderColor(string $priority): string
    {
        return match($priority) {
            'gr_past' => '#7c3aed',
            'gr_recent' => '#9333ea',
            'gr_future' => '#a855f7',
            default => '#4b5563',
        };
    }
}