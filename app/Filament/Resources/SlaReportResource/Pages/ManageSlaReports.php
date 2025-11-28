<?php

namespace App\Filament\Resources\SlaReportResource\Pages;

use App\Filament\Resources\SlaReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSlaReports extends ManageRecords
{
    protected static string $resource = SlaReportResource::class;
    protected static ?string $title = 'SLA Performance Reports';

    protected function getHeaderActions(): array
    {
        return [
            // No create action - SLA records are auto-generated
        ];
    }
}
