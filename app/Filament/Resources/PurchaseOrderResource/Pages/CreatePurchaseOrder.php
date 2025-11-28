<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('company_id') ?? 1; // Default to company 1 if not set
        $data['po_number'] = PurchaseOrder::generatePoNumber();
        $data['created_by'] = Auth::id();
        $data['status'] = 'draft';
        $data['order_date'] = $data['order_date'] ?? now();
        $data['currency'] = $data['currency'] ?? 'THB';
        $data['exchange_rate'] = $data['exchange_rate'] ?? 1.0000;

        // Ensure items have required fields
        if (isset($data['items'])) {
            foreach ($data['items'] as $key => $item) {
                // Ensure status is set
                if (!isset($item['status']) || empty($item['status'])) {
                    $data['items'][$key]['status'] = 'ordered';
                }
                // Ensure line_number is set
                if (!isset($item['line_number']) || empty($item['line_number'])) {
                    $data['items'][$key]['line_number'] = $key + 1;
                }
            }
        }

        // Handle file uploads - populate metadata
        if (isset($data['files'])) {
            foreach ($data['files'] as $key => $fileData) {
                if (isset($fileData['file_path']) && $fileData['file_path']) {
                    // Get file info from storage
                    $filePath = $fileData['file_path'];
                    if (Storage::disk('public')->exists($filePath)) {
                        $data['files'][$key]['file_name'] = pathinfo($filePath, PATHINFO_FILENAME);
                        $data['files'][$key]['file_type'] = Storage::disk('public')->mimeType($filePath);
                        $data['files'][$key]['file_size'] = Storage::disk('public')->size($filePath);
                        $data['files'][$key]['uploaded_by'] = Auth::id();
                        
                        // If original_name is not set, use the filename
                        if (empty($data['files'][$key]['original_name'])) {
                            $data['files'][$key]['original_name'] = basename($filePath);
                        }
                    }
                }
            }
        }

        return $data;
    }
    
    protected function afterCreate(): void
    {
        // Update file metadata after creation
        if ($this->record->files) {
            foreach ($this->record->files as $file) {
                if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                    $file->update([
                        'file_type' => Storage::disk('public')->mimeType($file->file_path),
                        'file_size' => Storage::disk('public')->size($file->file_path),
                    ]);
                }
            }
        }
        
        // Update line numbers for items
        if ($this->record->items) {
            foreach ($this->record->items as $index => $item) {
                $item->update([
                    'line_number' => $index + 1,
                ]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    public function mount(): void
    {
        parent::mount();
        
        // Pre-fill form data if created from Purchase Requisition
        $prId = request()->get('purchase_requisition_id');
        if ($prId) {
            $pr = PurchaseRequisition::find($prId);
            if ($pr) {
                // Prepare form data
                $formData = [
                    'purchase_requisition_id' => $pr->id,
                    'po_title' => $pr->title,
                    'work_type' => $pr->work_type,
                    'form_category' => $pr->form_category,  // ✅ เพิ่ม form_category จาก PR
                    'procurement_method' => $pr->procurement_method,
                    'department_id' => $pr->department_id,
                    'expected_delivery_date' => $pr->expected_delivery_date,
                    'order_date' => now(),  // ✅ เพิ่ม order_date
                    'currency' => $pr->currency ?? 'THB',  // ✅ เพิ่ม currency จาก PR
                ];

                // ✅ Auto-match vendor จาก supplier information
                if ($pr->supplier_name) {
                    $vendor = \App\Models\Vendor::where('company_name', 'like', '%' . $pr->supplier_name . '%')->first();
                    if ($vendor) {
                        $formData['vendor_id'] = $vendor->id;
                        $formData['contact_name'] = $vendor->contact_name;
                        $formData['contact_email'] = $vendor->contact_email;
                    } else {
                        // ถ้าไม่เจอ vendor ให้ใช้ข้อมูลจาก PR
                        $formData['contact_name'] = $pr->supplier_contact;
                        $formData['contact_email'] = $pr->supplier_email;
                    }
                }

                // ✅ Copy items from PR if they exist  
                $formData['items'] = $pr->items->map(function ($item) {
                    return [
                        'item_code' => $item->item_code,
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit_of_measure' => $item->unit_of_measure,
                        'unit_price' => $item->estimated_unit_price ?? 0,
                        'total_price' => ($item->quantity * ($item->estimated_unit_price ?? 0)),
                        'status' => 'ordered',
                    ];
                })->toArray();

                // ✅ คำนวณ total amounts จาก PR
                $subtotal = $pr->total_amount ?? 0;
                $taxAmount = $subtotal * 0.07;  // VAT 7%
                $totalAmount = $subtotal + $taxAmount;

                $formData['subtotal'] = round($subtotal, 2);
                $formData['tax_amount'] = round($taxAmount, 2);
                $formData['total_amount'] = round($totalAmount, 2);

                // ✅ ลองหา delivery address จาก department
                if ($pr->department_id) {
                    $department = \App\Models\Department::find($pr->department_id);
                    if ($department && !empty($department->address)) {
                        $formData['delivery_address'] = $department->address;
                    }
                }

                $this->form->fill($formData);
            }
        }
    }
}
