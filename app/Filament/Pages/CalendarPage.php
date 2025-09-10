<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Http\Controllers\DashboardController;
use Illuminate\Contracts\View\View;

class CalendarPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'ปฏิทิน';
    protected static ?string $title = 'ปฏิทินกำหนดการส่งมอบงาน';
    protected static string $view = 'filament.pages.calendar-page';
    protected static ?int $navigationSort = 2;

    public function getViewData(): array
    {
        $dashboardController = new DashboardController();
        $calendarEvents = $dashboardController->getCalendarEvents();
        
        return [
            'calendarEvents' => $calendarEvents
        ];
    }
}