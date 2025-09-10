<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class UpcomingDeliveries extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = '📅 กำหนดการที่ใกล้เข้ามา';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('ประเภท')
                    ->formatStateUsing(fn (string $state): string => $state === 'PO' ? '📋 PO' : '📝 PR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('number')
                    ->label('เลขที่')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('title')
                    ->label('หัวข้อ')
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('supplier')
                    ->label('ผู้ขาย')
                    ->searchable()
                    ->limit(25),
                    
                Tables\Columns\TextColumn::make('delivery_date')
                    ->label('กำหนดส่งมอบ')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('days_until')
                    ->label('เหลือเวลา')
                    ->formatStateUsing(function ($state) {
                        if ($state < 0) return "เกิน " . abs($state) . " วัน";
                        if ($state == 0) return "วันนี้";
                        if ($state == 1) return "พรุ่งนี้";
                        return "อีก " . $state . " วัน";
                    })
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state < 0 => 'danger',
                        $state <= 1 => 'danger', 
                        $state <= 7 => 'warning',
                        default => 'success'
                    }),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('มูลค่า')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0) . ' บาท' : 'ไม่ระบุ'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('ดู')
                    ->icon('heroicon-m-eye')
                    ->url(function ($record) {
                        if ($record->type === 'PO') {
                            return "/admin/purchase-orders/{$record->id}";
                        }
                        return "/admin/purchase-requisitions/{$record->id}";
                    })
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('days_until', 'asc')
            ->paginated(false);
    }

    protected function getTableQuery(): Builder
    {
        $currentDate = now();
        $endDate = now()->addDays(30);
        
        // Get POs
        $poQuery = PurchaseOrder::whereNotNull('expected_delivery_date')
            ->where('expected_delivery_date', '>=', $currentDate->subDays(7))
            ->where('expected_delivery_date', '<=', $endDate)
            ->where('status', 'approved')
            ->selectRaw("
                id,
                'PO' as type,
                po_number as number,
                po_title as title,
                vendor_name as supplier,
                expected_delivery_date as delivery_date,
                total_amount as amount,
                DATEDIFF(expected_delivery_date, CURDATE()) as days_until
            ");

        // Get PRs  
        $prQuery = PurchaseRequisition::whereNotNull('required_date')
            ->where('required_date', '>=', $currentDate->subDays(7))
            ->where('required_date', '<=', $endDate)
            ->whereIn('status', ['approved', 'pending'])
            ->selectRaw("
                id,
                'PR' as type,
                pr_number as number,
                title,
                supplier_name as supplier,
                required_date as delivery_date,
                total_amount as amount,
                DATEDIFF(required_date, CURDATE()) as days_until
            ");
            
        // Union and return as Builder
        return $poQuery->union($prQuery)->orderBy('days_until');
    }
}