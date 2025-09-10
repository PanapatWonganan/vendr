<?php

namespace App\Filament\Resources\PurchaseRequisitionResource\Pages;

use App\Filament\Resources\PurchaseRequisitionResource;
use App\Models\PurchaseRequisition;
use App\Events\PurchaseRequisitionSubmitted;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class CreatePurchaseRequisition extends CreateRecord
{
    protected static string $resource = PurchaseRequisitionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('company_id') ?? 1; // Default to company 1 if not set
        $data['pr_number'] = PurchaseRequisition::generatePRNumber();
        $data['created_by'] = Auth::id();
        $data['status'] = 'draft';
        $data['request_date'] = now();
        $data['currency'] = $data['currency'] ?? 'THB';

        return $data;
    }

    protected function afterCreate(): void
    {
        // Calculate and update total amount from items
        $totalAmount = $this->record->items()->sum('estimated_amount');
        $this->record->update(['total_amount' => $totalAmount]);
        
        // If status is not draft, fire the submitted event
        if ($this->record->status === 'pending_approval' || $this->record->status === 'submitted') {
            event(new PurchaseRequisitionSubmitted($this->record, Auth::user()));
            
            Notification::make()
                ->title('Purchase Requisition Submitted')
                ->body('PR ' . $this->record->pr_number . ' has been submitted for approval and notifications have been sent.')
                ->success()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Purchase Requisition Created Successfully';
    }
}
