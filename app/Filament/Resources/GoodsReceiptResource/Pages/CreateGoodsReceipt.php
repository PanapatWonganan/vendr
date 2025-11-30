<?php

namespace App\Filament\Resources\GoodsReceiptResource\Pages;

use App\Events\GoodsReceiptCreated;
use App\Filament\Resources\GoodsReceiptResource;
use App\Models\GoodsReceiptAttachment;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateGoodsReceipt extends CreateRecord
{
    protected static string $resource = GoodsReceiptResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validate vendor_id is set
        if (empty($data['vendor_id'])) {
            throw new \Exception('กรุณาเลือก Purchase Order เพื่อดึงข้อมูลผู้ขาย');
        }

        // Add company_id from session (handled by BaseModel)
        // Add created_by and received_by from current user
        $data['created_by'] = auth()->id();
        $data['received_by'] = auth()->id();

        // Generate receipt number
        $model = new \App\Models\GoodsReceipt();
        $grNumber = $model->generateReceiptNumber();
        $data['gr_number'] = $grNumber;
        $data['receipt_number'] = $grNumber; // Use same value

        // Set default values
        $data['is_quality_checked'] = false;

        // Store uploaded files and their names temporarily
        if (isset($data['temp_attachments'])) {
            $this->tempAttachments = $data['temp_attachments'];
            $this->attachmentFileNames = $data['attachment_files'] ?? [];
            unset($data['temp_attachments']);
            unset($data['attachment_files']);
        }

        return $data;
    }
    
    protected $tempAttachments = [];
    protected $attachmentFileNames = [];
    
    protected function afterCreate(): void
    {
        // Process uploaded attachments
        if (!empty($this->tempAttachments)) {
            foreach ($this->tempAttachments as $index => $filePath) {
                // Get file info from storage
                $fullPath = storage_path('app/public/' . $filePath);
                if (file_exists($fullPath)) {
                    // Get original file name from attachmentFileNames array if available
                    $originalName = $this->attachmentFileNames[$index] ?? basename($filePath);
                    $mimeType = mime_content_type($fullPath);
                    $fileSize = filesize($fullPath);
                    
                    // Create attachment record
                    GoodsReceiptAttachment::create([
                        'goods_receipt_id' => $this->record->id,
                        'file_name' => $originalName,
                        'file_path' => $filePath,
                        'file_type' => $mimeType,
                        'file_size' => $fileSize,
                        'description' => null,
                        'uploaded_by' => auth()->id(),
                    ]);
                }
            }
        }
        
        // Fire the GoodsReceiptCreated event
        $creator = User::find(auth()->id());
        if ($creator) {
            GoodsReceiptCreated::dispatch($this->record, $creator);
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
