<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class DeliveryCalendarWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getStats(): array
    {
        $currentMonth = now()->startOfMonth();
        $endMonth = now()->addMonths(2)->endOfMonth();
        
        // Get POs with delivery dates
        $pos = PurchaseOrder::whereNotNull('expected_delivery_date')
            ->whereBetween('expected_delivery_date', [$currentMonth, $endMonth])
            ->where('status', 'approved')
            ->get();
            
        // Get PRs with required dates
        $prs = PurchaseRequisition::whereNotNull('required_date')
            ->whereBetween('required_date', [$currentMonth, $endMonth])
            ->whereIn('status', ['approved', 'pending'])
            ->get();
        
        // Count urgent items (within 7 days)
        $urgentCount = $pos->filter(function($po) {
            return $po->expected_delivery_date && now()->diffInDays($po->expected_delivery_date, false) <= 7;
        })->count() + $prs->filter(function($pr) {
            return $pr->required_date && now()->diffInDays($pr->required_date, false) <= 7;
        })->count();
        
        return [
            Stat::make('Purchase Orders', $pos->count())
                ->description('ใบสั่งซื้อที่กำลังจะส่งมอบ')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info'),
                
            Stat::make('Purchase Requisitions', $prs->count())
                ->description('ใบขอซื้อที่รออนุมัติ')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),
                
            Stat::make('เร่งด่วน', $urgentCount)
                ->description('กำหนดส่งมอบใน 7 วัน')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($urgentCount > 0 ? 'danger' : 'success'),
                
            Stat::make('เดือนนี้', $currentMonth->format('M Y'))
                ->description('กำลังติดตามกำหนดการ')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
        ];
    }
}