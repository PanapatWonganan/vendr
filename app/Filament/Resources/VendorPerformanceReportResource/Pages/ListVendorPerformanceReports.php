<?php

namespace App\Filament\Resources\VendorPerformanceReportResource\Pages;

use App\Filament\Resources\VendorPerformanceReportResource;
use App\Exports\VendorPerformanceExport;
use App\Exports\SimpleVendorPerformanceExport;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;

class ListVendorPerformanceReports extends ListRecords
{
    use ExposesTableToWidgets;
    
    protected static string $resource = VendorPerformanceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export to Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    try {
                        $companyId = session('company_id');
                        $fileName = 'vendor-performance-report-' . date('Y-m-d-His') . '.xlsx';
                        
                        Notification::make()
                            ->title('กำลังสร้างไฟล์ Excel...')
                            ->info()
                            ->send();
                        
                        return Excel::download(
                            new SimpleVendorPerformanceExport($companyId),
                            $fileName
                        );
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('เกิดข้อผิดพลาด')
                            ->body('ไม่สามารถ export ข้อมูลได้: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Actions\Action::make('export_csv')
                ->label('Export to CSV')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->action(function () {
                    try {
                        $companyId = session('company_id');
                        $fileName = 'vendor-performance-report-' . date('Y-m-d-His') . '.csv';
                        
                        Notification::make()
                            ->title('กำลังสร้างไฟล์ CSV...')
                            ->info()
                            ->send();
                        
                        return Excel::download(
                            new SimpleVendorPerformanceExport($companyId),
                            $fileName,
                            \Maatwebsite\Excel\Excel::CSV
                        );
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('เกิดข้อผิดพลาด')
                            ->body('ไม่สามารถ export ข้อมูลได้: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
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