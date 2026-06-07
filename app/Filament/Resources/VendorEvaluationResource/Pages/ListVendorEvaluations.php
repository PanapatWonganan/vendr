<?php

namespace App\Filament\Resources\VendorEvaluationResource\Pages;

use App\Exports\VendorEvaluationExport;
use App\Filament\Resources\VendorEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListVendorEvaluations extends ListRecords
{
    protected static string $resource = VendorEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            // ข้อ 18: ดาวน์โหลดข้อมูลการประเมินเป็นไฟล์ Excel
            Actions\Action::make('exportExcel')
                ->label('ดาวน์โหลด Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $companyId = session('company_id');
                    $fileName = 'vendor-evaluations-'.now()->format('Ymd_His').'.xlsx';

                    return Excel::download(
                        new VendorEvaluationExport($companyId),
                        $fileName
                    );
                }),
        ];
    }
}
