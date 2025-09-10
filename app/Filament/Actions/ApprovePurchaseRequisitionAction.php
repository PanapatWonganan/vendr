<?php

namespace App\Filament\Actions;

use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Events\PurchaseRequisitionApproved;

class ApprovePurchaseRequisitionAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'approve';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('อนุมัติ')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('อนุมัติใบขอซื้อ')
            ->modalDescription('คุณแน่ใจหรือไม่ที่ต้องการอนุมัติใบขอซื้อนี้?')
            ->modalSubmitActionLabel('อนุมัติ')
            ->form([
                Textarea::make('approval_notes')
                    ->label('หมายเหตุการอนุมัติ')
                    ->placeholder('ระบุหมายเหตุ (ถ้ามี)')
                    ->rows(3),
            ])
            ->action(function (Model $record, array $data): void {
                $user = Auth::user();
                
                // Check permissions
                if (!$this->canApprove($record, $user)) {
                    $this->failure();
                    return;
                }

                // Update PR status
                $record->update([
                    'status' => 'approved',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'approval_notes' => $data['approval_notes'] ?? null,
                ]);

                // Fire event for email notifications
                event(new PurchaseRequisitionApproved($record, $user));

                $this->success();
            })
            ->visible(function (Model $record): bool {
                return $record->status === 'pending_approval' && 
                       $this->canApprove($record, Auth::user());
            });
    }

    private function canApprove(Model $record, $user): bool
    {
        // Admin can approve all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Procurement manager can approve all
        if ($user->hasRole('procurement_manager')) {
            return true;
        }

        // Department head can approve from their department
        if ($user->hasRole('department_head') && 
            $user->department_id === $record->department_id) {
            return true;
        }

        // PR approver (if specified)
        if ($record->pr_approver_id === $user->id) {
            return true;
        }

        return false;
    }
}