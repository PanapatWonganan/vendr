<?php

namespace App\Filament\Resources\VendorPerformanceReportResource\Pages;

use App\Filament\Resources\VendorPerformanceReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListVendorPerformanceReports extends ListRecords
{
    use ExposesTableToWidgets;
    
    protected static string $resource = VendorPerformanceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export Report')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    // Export functionality can be implemented here
                    $this->notify('success', 'Export feature will be implemented soon!');
                }),
        ];
    }
    
    public function getTitle(): string
    {
        return 'Vendor Performance Report';
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            VendorPerformanceReportResource\Widgets\VendorPerformanceOverview::class,
        ];
    }
}