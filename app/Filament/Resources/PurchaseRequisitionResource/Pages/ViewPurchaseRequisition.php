<?php

namespace App\Filament\Resources\PurchaseRequisitionResource\Pages;

use App\Filament\Resources\PurchaseRequisitionResource;
use App\Events\PurchaseRequisitionSubmitted;
use App\Events\PurchaseRequisitionApproved;
use App\Events\PurchaseRequisitionRejected;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class ViewPurchaseRequisition extends ViewRecord
{
    protected static string $resource = PurchaseRequisitionResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        
        // Edit action - only if status is draft
        if ($this->record->status === 'draft') {
            $actions[] = Actions\EditAction::make()
                ->label('Edit')
                ->icon('heroicon-o-pencil');
        }
        
        // Submit for Approval - only if status is draft
        if ($this->record->status === 'draft' && $this->record->created_by === Auth::id()) {
            $actions[] = Actions\Action::make('submitForApproval')
                ->label('Submit for Approval')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Submit for Approval')
                ->modalDescription('Are you sure you want to submit this PR for approval? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Submit')
                ->action(function () {
                    $this->record->update(['status' => 'pending_approval']);
                    event(new PurchaseRequisitionSubmitted($this->record, Auth::user()));
                    
                    Notification::make()
                        ->title('PR Submitted for Approval')
                        ->body('PR ' . $this->record->pr_number . ' has been submitted for approval.')
                        ->success()
                        ->send();
                });
        }
        
        // Approve - only if user is approver and status is pending
        if ($this->record->status === 'pending_approval' && $this->canApprove()) {
            $actions[] = Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Purchase Requisition')
                ->modalDescription('Are you sure you want to approve this purchase requisition?')
                ->modalSubmitActionLabel('Yes, Approve')
                ->action(function () {
                    $this->record->update([
                        'status' => 'approved',
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ]);
                    
                    event(new PurchaseRequisitionApproved($this->record, Auth::user()));
                    
                    Notification::make()
                        ->title('PR Approved')
                        ->body('PR ' . $this->record->pr_number . ' has been approved.')
                        ->success()
                        ->send();
                });
        }
        
        // Reject - only if user is approver and status is pending
        if ($this->record->status === 'pending_approval' && $this->canApprove()) {
            $actions[] = Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reject Purchase Requisition')
                ->modalDescription('Please provide a reason for rejection.')
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(3),
                ])
                ->modalSubmitActionLabel('Reject')
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'rejected',
                        'rejected_by' => Auth::id(),
                        'rejected_date' => now(),
                        'rejection_reason' => $data['rejection_reason'],
                    ]);
                    event(new PurchaseRequisitionRejected($this->record, Auth::user()));
                    
                    Notification::make()
                        ->title('PR Rejected')
                        ->body('PR ' . $this->record->pr_number . ' has been rejected.')
                        ->warning()
                        ->send();
                });
        }
        
        // Create PO - only if approved
        if ($this->record->status === 'approved') {
            $actions[] = Actions\Action::make('createPurchaseOrder')
                ->label('Create Purchase Order')
                ->icon('heroicon-o-shopping-cart')
                ->color('primary')
                ->url(
                    fn () => route('filament.admin.resources.purchase-orders.create', [
                        'purchase_requisition_id' => $this->record->id
                    ])
                );
        }
        
        return $actions;
    }
    
    protected function canApprove(): bool
    {
        $user = Auth::user();
        
        // Check if user is the designated approver
        if ($this->record->pr_approver_id === $user->id) {
            return true;
        }
        
        // Check if user has approver role
        if ($user->roles()->where('name', 'approver')->exists()) {
            return true;
        }
        
        // Check if user is admin
        if ($user->roles()->where('name', 'admin')->exists()) {
            return true;
        }
        
        // Check if user is procurement manager
        if ($user->roles()->where('name', 'procurement_manager')->exists()) {
            return true;
        }
        
        return false;
    }
}