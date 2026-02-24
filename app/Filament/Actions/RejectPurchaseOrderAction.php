<?php

namespace App\Filament\Actions;

use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            ->modalDescription(fn (Model $record) => "คุณต้องการปฏิเสธ {$record->po_number} หรือไม่?")
            ->modalSubmitActionLabel('ยืนยันปฏิเสธ')
            ->form([
                Textarea::make('rejection_notes')
                    ->label('เหตุผลการปฏิเสธ')
                    ->placeholder('ระบุเหตุผลการปฏิเสธ')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (Model $record, array $data): void {
                $user = Auth::user();

                if (!$this->canReject($record, $user)) {
                    Notification::make()
                        ->title('ไม่มีสิทธิ์ปฏิเสธ')
                        ->body('คุณไม่มีสิทธิ์ปฏิเสธใบสั่งซื้อนี้')
                        ->danger()
                        ->send();
                    return;
                }

                try {
                    $record->update([
                        'status' => 'rejected',
                        'rejected_by' => $user->id,
                        'rejected_at' => now(),
                        'rejection_notes' => $data['rejection_notes'],
                    ]);

                    event(new PurchaseOrderRejected($record, $user));

                    Notification::make()
                        ->title('ปฏิเสธเรียบร้อย')
                        ->body("ปฏิเสธ {$record->po_number} เรียบร้อยแล้ว")
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Log::error('PO reject failed', ['po_id' => $record->id, 'error' => $e->getMessage()]);

                    Notification::make()
                        ->title('เกิดข้อผิดพลาด')
                        ->body('ไม่สามารถปฏิเสธได้: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
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