<?php

namespace App\Filament\Resources\VendorEvaluationResource\Pages;

use App\Filament\Resources\VendorEvaluationResource;
use App\Models\ProcurementAttachment;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateVendorEvaluation extends CreateRecord
{
    protected static string $resource = VendorEvaluationResource::class;

    /**
     * ข้อ 13: หลังสร้างการประเมิน ให้บันทึกไฟล์แนบที่อัปโหลดในฟอร์ม
     * เป็น ProcurementAttachment (polymorphic) ผูกกับ record ที่เพิ่งสร้าง
     * ใช้ pattern เดียวกับ AttachmentsRelationManager เพื่อความสอดคล้อง
     */
    protected function afterCreate(): void
    {
        // ฟิลด์ attachment_files ใช้ dehydrated(false) จึงอ่านจาก raw form state
        $files = $this->data['attachment_files'] ?? [];

        if (! is_array($files) || empty($files)) {
            return;
        }

        $stored = 0;

        foreach ($files as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }

            try {
                $originalName = $file->getClientOriginalName();
                $mimeType = $file->getMimeType() ?: 'application/octet-stream';
                $fileSize = $file->getSize();

                $filePath = $file->store('vendor-evaluations', 'public');
                if ($filePath === false) {
                    throw new \RuntimeException('Failed to persist uploaded file.');
                }

                $this->record->attachments()->create([
                    'company_id' => session('company_id'),
                    'file_path' => $filePath,
                    'file_name' => $originalName,
                    'original_name' => $originalName,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'category' => 'inspection_report',
                    'uploaded_by' => Auth::id(),
                    'is_public' => true,
                ]);

                $stored++;
            } catch (\Throwable $e) {
                Log::error('Vendor evaluation attachment upload failed on create', [
                    'evaluation_id' => $this->record->id ?? null,
                    'error' => $e->getMessage(),
                ]);

                Notification::make()
                    ->title('อัปโหลดไฟล์แนบบางไฟล์ไม่สำเร็จ')
                    ->body('บันทึกการประเมินแล้ว แต่มีไฟล์แนบที่อัปโหลดไม่สำเร็จ กรุณาลองแนบใหม่ในหน้าแก้ไข')
                    ->warning()
                    ->send();
            }
        }

        if ($stored > 0) {
            Notification::make()
                ->title("แนบไฟล์สำเร็จ {$stored} ไฟล์")
                ->success()
                ->send();
        }
    }
}
