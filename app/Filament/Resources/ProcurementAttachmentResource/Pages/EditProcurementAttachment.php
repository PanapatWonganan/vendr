<?php

namespace App\Filament\Resources\ProcurementAttachmentResource\Pages;

use App\Filament\Resources\ProcurementAttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProcurementAttachment extends EditRecord
{
    protected static string $resource = ProcurementAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}