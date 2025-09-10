<?php

namespace App\Filament\Actions;

use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Events\PurchaseOrderRejected;

class RejectPurchaseOrderAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'reject';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('ปฏิเสธ')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('ปฏิเสธใบสั่งซื้อ')
            ->modalDescription('คุณแน่ใจหรือไม่ที่ต้องการปฏิเสธใบสั่งซื้อนี้?')
            ->modalSubmitActionLabel('ปฏิเสธ')
            ->form([
                Textarea::make('rejection_notes')
                    ->label('เหตุผลการปฏิเสธ')
                    ->placeholder('ระบุเหตุผลการปฏิเสธ')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (Model $record, array $data): void {
                $user = Auth::user();
                
                // Check permissions
                if (!$this->canReject($record, $user)) {
                    $this->failure();
                    return;
                }

                // Update PO status
                $record->update([
                    'status' => 'rejected',
                    'rejected_by' => $user->id,
                    'rejected_at' => now(),
                    'rejection_notes' => $data['rejection_notes'],
                ]);

                // Fire event for email notifications
                event(new PurchaseOrderRejected($record, $user));

                $this->success();
            })
            ->visible(function (Model $record): bool {
                return $record->status === 'pending_approval' && 
                       $this->canReject($record, Auth::user());
            });
    }

    private function canReject(Model $record, $user): bool
    {
        // Admin can reject all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Procurement manager can reject all
        if ($user->hasRole('procurement_manager')) {
            return true;
        }

        // Department head can reject from their department
        if ($user->hasRole('department_head') && 
            $user->department_id === $record->department_id) {
            return true;
        }

        // PO approver (if specified)
        if ($record->po_approver_id === $user->id) {
            return true;
        }

        return false;
    }
}