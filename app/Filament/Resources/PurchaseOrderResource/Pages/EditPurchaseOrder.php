<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\DeleteAction::make(),
        ];

        // Add approve/reject actions if PO is pending approval
        $record = $this->getRecord();
        $user = auth()->user();
        
        if ($record->status === 'pending_approval' && 
            ($user->hasRole('admin') || 
             $user->hasRole('procurement_manager') ||
             ($user->hasRole('department_head') && $user->department_id === $record->department_id))) {
            
            // Approve Action
            $actions[] = Actions\Action::make('approve')
                ->label('อนุมัติ PO')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('อนุมัติใบสั่งซื้อ')
                ->modalDescription('คุณต้องการอนุมัติใบสั่งซื้อ ' . $record->po_number . ' หรือไม่?')
                ->modalSubmitActionLabel('อนุมัติ')
                ->action(function () use ($record, $user) {
                    $record->update([
                        'status' => 'approved',
                        'approved_by' => $user->id,
                        'approved_at' => now(),
                    ]);
                    
                    // Fire event for email notifications
                    event(new \App\Events\PurchaseOrderApproved($record, $user));
                    
                    \Filament\Notifications\Notification::make()
                        ->title('อนุมัติเรียบร้อย')
                        ->body('ได้อนุมัติ PO ' . $record->po_number . ' เรียบร้อยแล้ว')
                        ->success()
                        ->send();
                        
                    return redirect()->to('/admin/purchase-orders/pending-approvals');
                });
            
            // Reject Action  
            $actions[] = Actions\Action::make('reject')
                ->label('ปฏิเสธ PO')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('ปฏิเสธใบสั่งซื้อ')
                ->modalDescription('คุณต้องการปฏิเสธใบสั่งซื้อ ' . $record->po_number . ' หรือไม่?')
                ->modalSubmitActionLabel('ปฏิเสธ')
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_notes')
                        ->label('เหตุผลการปฏิเสธ')
                        ->placeholder('ระบุเหตุผลการปฏิเสธ')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) use ($record, $user) {
                    $record->update([
                        'status' => 'rejected',
                        'rejected_by' => $user->id,
                        'rejected_at' => now(),
                        'rejection_notes' => $data['rejection_notes'],
                    ]);
                    
                    // Fire event for email notifications  
                    event(new \App\Events\PurchaseOrderRejected($record, $user));
                    
                    \Filament\Notifications\Notification::make()
                        ->title('ปฏิเสธเรียบร้อย')
                        ->body('ได้ปฏิเสธ PO ' . $record->po_number . ' เรียบร้อยแล้ว')
                        ->success()
                        ->send();
                        
                    return redirect()->to('/admin/purchase-orders/pending-approvals');
                });
        }

        return $actions;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        
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
}
