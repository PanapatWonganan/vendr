<?php

namespace App\Filament\Resources\ProcurementAttachmentResource\Pages;

use App\Filament\Resources\ProcurementAttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProcurementAttachments extends ListRecords
{
    protected static string $resource = ProcurementAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}