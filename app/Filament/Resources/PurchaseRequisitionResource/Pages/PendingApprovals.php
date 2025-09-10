<?php

namespace App\Filament\Resources\PurchaseRequisitionResource\Pages;

use App\Filament\Resources\PurchaseRequisitionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class PendingApprovals extends ListRecords
{
    protected static string $resource = PurchaseRequisitionResource::class;
    
    protected static ?string $title = 'รออนุมัติ';
    protected static ?string $breadcrumb = 'Pending Approvals';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('สร้างใบขอซื้อใหม่')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return static::getResource()::getEloquentQuery()
            ->where('status', 'pending_approval')
            ->latest();
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->label('ดู')
                ->icon('heroicon-o-eye'),
            Tables\Actions\Action::make('approve')
                ->label('อนุมัติ')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('อนุมัติใบขอซื้อ')
                ->modalDescription('คุณต้องการอนุมัติใบขอซื้อนี้หรือไม่?')
                ->modalSubmitActionLabel('อนุมัติ')
                ->action(function ($record) {
                    $record->update(['status' => 'approved']);
                    $this->notify('success', 'อนุมัติใบขอซื้อเรียบร้อยแล้ว');
                }),
            Tables\Actions\Action::make('reject')
                ->label('ปฏิเสธ')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('ปฏิเสธใบขอซื้อ')
                ->modalDescription('คุณต้องการปฏิเสธใบขอซื้อนี้หรือไม่?')
                ->modalSubmitActionLabel('ปฏิเสธ')
                ->action(function ($record) {
                    $record->update(['status' => 'rejected']);
                    $this->notify('success', 'ปฏิเสธใบขอซื้อเรียบร้อยแล้ว');
                }),
        ];
    }

    public function getTableRecordUrlUsing(): ?\Closure
    {
        return fn ($record) => static::getResource()::getUrl('edit', ['record' => $record]);
    }
}
