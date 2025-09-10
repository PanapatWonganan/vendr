<?php

namespace App\Filament\Resources\PurchaseRequisitionResource\Pages;

use App\Filament\Resources\PurchaseRequisitionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;

class MyRequests extends ListRecords
{
    protected static string $resource = PurchaseRequisitionResource::class;
    
    protected static ?string $title = 'คำขอของฉัน';
    protected static ?string $breadcrumb = 'My Requests';

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
            ->where(function($query) {
                $query->where('requester_id', auth()->id())
                      ->orWhere('created_by', auth()->id());
            })
            ->latest();
    }

    public function getTableRecordUrlUsing(): ?\Closure
    {
        return fn ($record) => static::getResource()::getUrl('edit', ['record' => $record]);
    }
}
