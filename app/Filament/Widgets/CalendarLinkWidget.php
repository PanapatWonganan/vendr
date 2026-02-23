<?php

namespace App\Filament\Widgets;

use App\Models\GoodsReceipt;
use Filament\Widgets\Widget;
use Carbon\Carbon;

class CalendarLinkWidget extends Widget
{
    protected static ?int $sort = 8;

    protected static string $view = 'filament.widgets.calendar-link-widget';

    protected int | string | array $columnSpan = 'full';

    public function getGrStats(): array
    {
        $companyId = session('company_id');

        if (!$companyId) {
            return ['total' => 0, 'recent' => 0];
        }

        $now = Carbon::now();

        $total = GoodsReceipt::where('company_id', $companyId)
            ->whereBetween('receipt_date', [$now->copy()->subDays(30), $now->copy()->addDays(60)])
            ->whereNotNull('receipt_date')
            ->count();

        $recent = GoodsReceipt::where('company_id', $companyId)
            ->whereBetween('receipt_date', [$now->copy()->subDays(3), $now->copy()->addDays(3)])
            ->whereNotNull('receipt_date')
            ->count();

        return ['total' => $total, 'recent' => $recent];
    }
}
