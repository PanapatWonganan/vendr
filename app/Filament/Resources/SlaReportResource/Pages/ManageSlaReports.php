<?php

namespace App\Filament\Resources\SlaReportResource\Pages;

use App\Exports\SlaReportExport;
use App\Filament\Resources\SlaReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Maatwebsite\Excel\Facades\Excel;

class ManageSlaReports extends ManageRecords
{
    protected static string $resource = SlaReportResource::class;

    protected static ?string $title = 'SLA Performance Reports';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportExcel')
                ->label('Export Excel (SLA รับเรื่อง → PO)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => Excel::download(
                    new SlaReportExport(session('company_id')),
                    'sla-report-'.now()->format('Y-m-d').'.xlsx'
                )),
        ];
    }
}
