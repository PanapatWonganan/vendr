<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class PendingPurchaseOrders extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;

    protected static ?string $title = 'รออนุมัติ PO';
    protected static ?string $breadcrumb = 'Pending Approvals';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('สร้างใบสั่งซื้อใหม่')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $user = auth()->user();

        return static::getResource()::getEloquentQuery()
            ->where('status', 'pending_approval')
            // Filter by current company
            ->where('company_id', session('company_id', 1))
            ->where(function ($query) use ($user) {
                // Admin and Procurement Managers can approve any PO
                if ($user->hasAnyRole(['admin', 'procurement_manager'])) {
                    return $query;
                }

                // Department heads can only approve POs from their department
                if ($user->hasRole('department_head') && $user->department_id) {
                    return $query->where('department_id', $user->department_id);
                }

                // Default: no access
                return $query->whereNull('id');
            })
            ->latest();
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->label('ดู')
                ->icon('heroicon-o-eye'),

            Tables\Actions\EditAction::make()
                ->label('แก้ไข')
                ->icon('heroicon-o-pencil'),

            // Approve PO
            Tables\Actions\Action::make('approve')
                ->label('อนุมัติ')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(fn ($record) => "อนุมัติ {$record->po_number}")
                ->modalDescription('คุณต้องการอนุมัติใบสั่งซื้อนี้หรือไม่?')
                ->modalSubmitActionLabel('ยืนยันอนุมัติ')
                ->form([
                    Textarea::make('approval_notes')
                        ->label('หมายเหตุ (ถ้ามี)')
                        ->rows(2),
                ])
                ->action(function ($record, array $data) {
                    $user = auth()->user();
                    $record->update([
                        'status' => 'approved',
                        'approved_by' => $user->id,
                        'approved_at' => now(),
                        'approval_notes' => $data['approval_notes'] ?? null,
                    ]);

                    event(new \App\Events\PurchaseOrderApproved($record, $user));

                    Notification::make()
                        ->title('อนุมัติเรียบร้อย')
                        ->body("อนุมัติ {$record->po_number} แล้ว")
                        ->success()
                        ->send();
                }),

            // Reject PO
            Tables\Actions\Action::make('reject')
                ->label('ปฏิเสธ')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(fn ($record) => "ปฏิเสธ {$record->po_number}")
                ->modalDescription('คุณต้องการปฏิเสธใบสั่งซื้อนี้หรือไม่?')
                ->modalSubmitActionLabel('ยืนยันปฏิเสธ')
                ->form([
                    Textarea::make('rejection_notes')
                        ->label('เหตุผลการปฏิเสธ')
                        ->required()
                        ->rows(3),
                ])
                ->action(function ($record, array $data) {
                    $user = auth()->user();
                    $record->update([
                        'status' => 'rejected',
                        'rejected_by' => $user->id,
                        'rejected_at' => now(),
                        'rejection_notes' => $data['rejection_notes'],
                    ]);

                    event(new \App\Events\PurchaseOrderRejected($record, $user));

                    Notification::make()
                        ->title('ปฏิเสธเรียบร้อย')
                        ->body("ปฏิเสธ {$record->po_number} แล้ว")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTableRecordUrlUsing(): ?\Closure
    {
        return fn ($record) => static::getResource()::getUrl('edit', ['record' => $record]);
    }
}