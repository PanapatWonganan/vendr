<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DeliveryCalendarWidget;
use App\Filament\Widgets\UpcomingDeliveries;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function getWidgets(): array
    {
        return [
            DeliveryCalendarWidget::class,
            UpcomingDeliveries::class,
        ];
    }

    // ข้อ 19: ตัวเลือกปีบนหน้า Dashboard
    public function filtersForm(Form $form): Form
    {
        $currentYear = (int) date('Y');
        $years = collect(range($currentYear, $currentYear - 6))
            ->mapWithKeys(fn ($y) => [$y => (string) $y])
            ->all();

        return $form
            ->schema([
                Select::make('year')
                    ->label('Year (ปี)')
                    ->options($years)
                    ->default($currentYear)
                    ->selectablePlaceholder(false)
                    ->native(false),
            ]);
    }
}
