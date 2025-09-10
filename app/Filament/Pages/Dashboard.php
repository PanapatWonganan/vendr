<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DeliveryCalendarWidget;
use App\Filament\Widgets\UpcomingDeliveries;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            DeliveryCalendarWidget::class,
            UpcomingDeliveries::class,
        ];
    }
}