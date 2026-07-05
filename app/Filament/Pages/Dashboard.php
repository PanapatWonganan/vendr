<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Illuminate\Contracts\View\View;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    // ข้อ 19: ตัวเลือกปีบนหน้า Dashboard — วางบนแถวเดียวกับหัวข้อ (มุมขวาบน)
    public function getHeader(): ?View
    {
        return view('filament.pages.dashboard-header', [
            'heading' => $this->getHeading(),
            'filtersForm' => $this->getFiltersForm(),
        ]);
    }

    public function filtersForm(Form $form): Form
    {
        $currentYear = (int) date('Y');
        $years = collect(range($currentYear, $currentYear - 6))
            ->mapWithKeys(fn ($y) => [$y => (string) $y])
            ->all();

        return $form
            // คอลัมน์เดียว เพื่อให้ dropdown แคบพอดี ไม่กินเต็มความกว้าง
            ->columns(1)
            ->schema([
                Select::make('year')
                    ->label('ปี (Year)')
                    ->options($years)
                    ->default($currentYear)
                    ->selectablePlaceholder(false)
                    ->native(false),
            ]);
    }
}
