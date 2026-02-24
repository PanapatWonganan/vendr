<?php

namespace App\Filament\Actions;

use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Events\PurchaseOrderApproved;

class ApprovePurchaseOrderAction extends Action
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
            ->modalHeading('อนุมัติใบสั่งซื้อ')
            ->modalDescription(fn (Model $record) => "คุณต้องการอนุมัติ {$record->po_number} หรือไม่?")
            ->modalSubmitActionLabel('ยืนยันอนุมัติ')
            ->form([
                Textarea::make('approval_notes')
                    ->label('หมายเหตุการอนุมัติ')
                    ->placeholder('ระบุหมายเหตุ (ถ้ามี)')
                    ->rows(3),
            ])
            ->action(function (Model $record, array $data): void {
                $user = Auth::user();

                if (!$this->canApprove($record, $user)) {
                    Notification::make()
                        ->title('ไม่มีสิทธิ์อนุมัติ')
                        ->body('คุณไม่มีสิทธิ์อนุมัติใบสั่งซื้อนี้')
                        ->danger()
                        ->send();
                    return;
                }

                try {
                    $record->update([
                        'status' => 'approved',
                        'approved_by' => $user->id,
                        'approved_at' => now(),
                        'approval_notes' => $data['approval_notes'] ?? null,
                    ]);

                    event(new PurchaseOrderApproved($record, $user));

                    Notification::make()
                        ->title('อนุมัติเรียบร้อย')
                        ->body("อนุมัติ {$record->po_number} เรียบร้อยแล้ว")
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Log::error('PO approve failed', ['po_id' => $record->id, 'error' => $e->getMessage()]);

                    Notification::make()
                        ->title('เกิดข้อผิดพลาด')
                        ->body('ไม่สามารถอนุมัติได้: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
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

        // PO approver (if specified)
        if ($record->po_approver_id === $user->id) {
            return true;
        }

        return false;
    }
}