<?php

namespace App\Filament\Resources\ProcurementAttachmentResource\Pages;

use App\Filament\Resources\ProcurementAttachmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProcurementAttachment extends CreateRecord
{
    protected static string $resource = ProcurementAttachmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = auth()->id();
        
        // Get file information
        if (isset($data['file_path'])) {
            $file = request()->file('data.file_path');
            if ($file) {
                $data['original_name'] = $file->getClientOriginalName();
                $data['file_size'] = $file->getSize();
                $data['mime_type'] = $file->getMimeType();
            }
        }
        
        return $data;
    }
}