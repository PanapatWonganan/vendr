<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Filament\Actions\ApprovePurchaseOrderAction;
use App\Filament\Actions\RejectPurchaseOrderAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
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

            // PO Approval Actions (ปุ่มอนุมัติ/ปฏิเสธ)
            new ApprovePurchaseOrderAction('approve'),
            new RejectPurchaseOrderAction('reject'),
        ];
    }

    public function getTableRecordUrlUsing(): ?\Closure
    {
        return fn ($record) => static::getResource()::getUrl('edit', ['record' => $record]);
    }
}