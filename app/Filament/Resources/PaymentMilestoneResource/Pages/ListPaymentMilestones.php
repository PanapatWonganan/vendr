<?php

namespace App\Filament\Resources\PaymentMilestoneResource\Pages;

use App\Filament\Resources\PaymentMilestoneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentMilestones extends ListRecords
{
    protected static string $resource = PaymentMilestoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('สร้างงวดใหม่'),
                
            Actions\Action::make('createFromPO')
                ->label('สร้างจาก PO')
                ->icon('heroicon-o-document-duplicate')
                ->color('info')
                ->modal()
                ->modalHeading('สร้างงวดการจ่ายจาก Purchase Order')
                ->form([
                    \Filament\Forms\Components\Select::make('purchase_order_id')
                        ->label('เลือก Purchase Order')
                        ->relationship('purchaseOrder', 'po_number')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state) {
                                $po = \App\Models\PurchaseOrder::find($state);
                                if ($po) {
                                    $set('total_amount', $po->total_amount);
                                }
                            }
                        }),
                    \Filament\Forms\Components\TextInput::make('total_amount')
                        ->label('ยอดรวม PO')
                        ->disabled()
                        ->prefix('฿'),
                    \Filament\Forms\Components\TextInput::make('milestones_count')
                        ->label('จำนวนงวด')
                        ->numeric()
                        ->default(3)
                        ->minValue(1)
                        ->maxValue(10)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $po = \App\Models\PurchaseOrder::find($data['purchase_order_id']);
                    $count = $data['milestones_count'];
                    $amountPerMilestone = $po->total_amount / $count;
                    
                    for ($i = 1; $i <= $count; $i++) {
                        \App\Models\PaymentMilestone::create([
                            'company_id' => session('company_id', 1),
                            'purchase_order_id' => $po->id,
                            'milestone_number' => $i,
                            'milestone_title' => "งวดที่ {$i}",
                            'amount' => $amountPerMilestone,
                            'percentage' => (100 / $count),
                            'due_date' => now()->addDays(30 * $i)->format('Y-m-d'),
                            'status' => 'pending',
                            'created_by' => auth()->id(),
                        ]);
                    }
                    
                    \Filament\Notifications\Notification::make()
                        ->title('สร้างงวดการจ่ายแล้ว')
                        ->body("สร้าง {$count} งวดสำหรับ PO: {$po->po_number}")
                        ->success()
                        ->send();
                }),
                
            Actions\Action::make('paymentReport')
                ->label('รายงานการจ่าย')
                ->icon('heroicon-o-document-chart-bar')
                ->color('warning')
                ->action(function () {
                    // Simple payment summary
                    $stats = [
                        'total_pending' => \App\Models\PaymentMilestone::where('status', 'pending')->sum('amount'),
                        'total_paid' => \App\Models\PaymentMilestone::where('status', 'paid')->sum('amount'),
                        'total_overdue' => \App\Models\PaymentMilestone::where('status', 'overdue')->sum('amount'),
                        'count_pending' => \App\Models\PaymentMilestone::where('status', 'pending')->count(),
                        'count_paid' => \App\Models\PaymentMilestone::where('status', 'paid')->count(),
                        'count_overdue' => \App\Models\PaymentMilestone::where('status', 'overdue')->count(),
                    ];
                    
                    $message = "📊 สรุปการจ่ายเงิน:\n\n";
                    $message .= "💰 รอจ่าย: ฿" . number_format($stats['total_pending'], 2) . " ({$stats['count_pending']} งวด)\n";
                    $message .= "✅ จ่ายแล้ว: ฿" . number_format($stats['total_paid'], 2) . " ({$stats['count_paid']} งวด)\n";
                    $message .= "⚠️ เกินกำหนด: ฿" . number_format($stats['total_overdue'], 2) . " ({$stats['count_overdue']} งวด)";
                    
                    \Filament\Notifications\Notification::make()
                        ->title('รายงานสรุปการจ่ายเงิน')
                        ->body($message)
                        ->info()
                        ->persistent()
                        ->send();
                }),
        ];
    }
}
