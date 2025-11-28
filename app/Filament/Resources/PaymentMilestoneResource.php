<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMilestoneResource\Pages;
use App\Filament\Resources\PaymentMilestoneResource\RelationManagers;
use App\Models\PaymentMilestone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentMilestoneResource extends Resource
{
    protected static ?string $model = PaymentMilestone::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'งวดการจ่ายเงิน';
    
    protected static ?string $modelLabel = 'งวดการจ่าย';
    
    protected static ?string $pluralModelLabel = 'งวดการจ่ายเงิน';
    
    protected static ?string $navigationGroup = 'Milestone Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ข้อมูลใบสั่งซื้อ')
                    ->schema([
                        Forms\Components\Select::make('purchase_order_id')
                            ->label('เลขที่ PO')
                            ->relationship('purchaseOrder', 'po_number')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                    
                Forms\Components\Section::make('ข้อมูลงวดการจ่าย')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('milestone_number')
                                ->label('งวดที่')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    if ($state && empty($get('milestone_title'))) {
                                        $set('milestone_title', 'งวดที่ ' . $state);
                                    }
                                }),
                                
                            Forms\Components\TextInput::make('milestone_title')
                                ->label('ชื่องวด')
                                ->maxLength(191)
                                ->placeholder('เช่น มัดจำ, งวดกลาง')
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    if (empty($state) && $get('milestone_number')) {
                                        $set('milestone_title', 'งวดที่ ' . $get('milestone_number'));
                                    }
                                })
                                ->required(),
                                
                            Forms\Components\TextInput::make('percentage')
                                ->label('เปอร์เซ็นต์')
                                ->numeric()
                                ->suffix('%')
                                ->step(0.01)
                                ->minValue(0)
                                ->maxValue(100),
                        ]),
                        
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('amount')
                                ->label('จำนวนเงิน')
                                ->numeric()
                                ->prefix('฿')
                                ->step(0.01)
                                ->required(),
                                
                            Forms\Components\DatePicker::make('due_date')
                                ->label('วันครบกำหนด')
                                ->native(false)
                                ->required()
                                ->default(now()->addDays(30)),
                        ]),
                        
                        Forms\Components\Textarea::make('payment_terms')
                            ->label('เงื่อนไขการจ่าย')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('สถานะและการจ่ายเงิน')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('status')
                                ->label('สถานะ')
                                ->options(PaymentMilestone::getStatusOptions())
                                ->default('pending')
                                ->required(),
                                
                            Forms\Components\DatePicker::make('paid_date')
                                ->label('วันที่จ่าย')
                                ->native(false),
                        ]),
                        
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('paid_amount')
                                ->label('จำนวนเงินที่จ่าย')
                                ->numeric()
                                ->prefix('฿')
                                ->step(0.01),
                                
                            Forms\Components\TextInput::make('payment_reference')
                                ->label('เลขที่อ้างอิง')
                                ->maxLength(191)
                                ->placeholder('เช่น เลขที่เช็ค, Transaction ID'),
                        ]),
                        
                        Forms\Components\Textarea::make('payment_notes')
                            ->label('หมายเหตุการจ่าย')
                            ->rows(2)
                            ->columnSpanFull(),
                            
                        Forms\Components\Select::make('paid_by')
                            ->label('ผู้จ่าย')
                            ->relationship('paidBy', 'name')
                            ->searchable()
                            ->preload(),
                    ]),
                    
                Forms\Components\Hidden::make('company_id')
                    ->default(fn () => session('selected_company_id')),
                Forms\Components\Hidden::make('created_by')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('เลขที่ PO')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('milestone_number')
                    ->label('งวดที่')
                    ->alignCenter()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('milestone_title')
                    ->label('ชื่องวด')
                    ->searchable()
                    ->limit(20),
                    
                Tables\Columns\TextColumn::make('percentage')
                    ->label('%')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->alignCenter()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('จำนวนเงิน')
                    ->formatStateUsing(fn ($state) => '฿' . number_format($state, 2))
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('สถานะ')
                    ->formatStateUsing(fn ($state) => PaymentMilestone::getStatusOptions()[$state] ?? $state)
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'due' => 'info',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray'
                    }),
                    
                Tables\Columns\TextColumn::make('due_date')
                    ->label('ครบกำหนด')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('paid_date')
                    ->label('วันที่จ่าย')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('จำนวนที่จ่าย')
                    ->formatStateUsing(fn ($state) => $state ? '฿' . number_format($state, 2) : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('payment_reference')
                    ->label('เลขที่อ้างอิง')
                    ->searchable()
                    ->limit(15)
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('paidBy.name')
                    ->label('ผู้จ่าย')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('วันที่สร้าง')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('สถานะ')
                    ->options([
                        'pending' => 'รอจ่าย',
                        'due' => 'ครบกำหนด',
                        'paid' => 'จ่ายแล้ว',
                        'overdue' => 'เกินกำหนด',
                        'cancelled' => 'ยกเลิก',
                    ])
                    ->default('pending'),
                    
                Tables\Filters\Filter::make('due_date')
                    ->label('ครบกำหนดในช่วง')
                    ->form([
                        Forms\Components\DatePicker::make('due_from')
                            ->label('จากวันที่'),
                        Forms\Components\DatePicker::make('due_until')
                            ->label('ถึงวันที่'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['due_from'], fn (Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date))
                            ->when($data['due_until'], fn (Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date));
                    }),
                    
                Tables\Filters\SelectFilter::make('purchase_order_id')
                    ->label('ใบสั่งซื้อ')
                    ->relationship('purchaseOrder', 'po_number')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('ดู'),
                Tables\Actions\EditAction::make()
                    ->label('แก้ไข'),
                    
                // Payment Actions
                Tables\Actions\Action::make('markAsPaid')
                    ->label('ทำเครื่องหมายว่าจ่ายแล้ว')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('บันทึกการจ่ายเงิน')
                    ->modalDescription('คุณต้องการทำเครื่องหมายงวดนี้ว่าจ่ายเงินแล้วหรือไม่?')
                    ->form([
                        Forms\Components\DatePicker::make('paid_date')
                            ->label('วันที่จ่าย')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('paid_amount')
                            ->label('จำนวนที่จ่าย')
                            ->numeric()
                            ->prefix('฿')
                            ->default(fn ($record) => $record->amount)
                            ->required(),
                        Forms\Components\TextInput::make('payment_reference')
                            ->label('เลขที่อ้างอิง')
                            ->placeholder('เช่น เลขที่เช็ค, Transfer Reference'),
                        Forms\Components\Textarea::make('payment_notes')
                            ->label('หมายเหตุการจ่าย')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'paid',
                            'paid_date' => $data['paid_date'],
                            'paid_amount' => $data['paid_amount'],
                            'payment_reference' => $data['payment_reference'],
                            'payment_notes' => $data['payment_notes'],
                            'paid_by' => auth()->id(),
                        ]);
                        
                        // Fire event for email notifications
                        $payer = \App\Models\User::find(auth()->id());
                        if ($payer) {
                            \App\Events\PaymentMilestonePaid::dispatch($record, $payer);
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('บันทึกการจ่ายเงินแล้ว')
                            ->body("งวดที่ {$record->milestone_number} - {$record->milestone_title}")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->status !== 'paid'),
                    
                Tables\Actions\Action::make('markAsOverdue')
                    ->label('ครบกำหนด')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('ทำเครื่องหมายเป็นเกินกำหนด')
                    ->modalDescription('คุณต้องการทำเครื่องหมายงวดนี้ว่าเกินกำหนดหรือไม่?')
                    ->action(function ($record) {
                        $record->update(['status' => 'overdue']);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('ทำเครื่องหมายเป็นเกินกำหนดแล้ว')
                            ->body("งวดที่ {$record->milestone_number} - {$record->milestone_title}")
                            ->warning()
                            ->send();
                    })
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'due']) && $record->due_date < now()),
                    
                Tables\Actions\Action::make('cancel')
                    ->label('ยกเลิก')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('ยกเลิกงวดการจ่าย')
                    ->modalDescription('คุณต้องการยกเลิกงวดการจ่ายนี้หรือไม่?')
                    ->action(function ($record) {
                        $record->update(['status' => 'cancelled']);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('ยกเลิกงวดการจ่ายแล้ว')
                            ->body("งวดที่ {$record->milestone_number} - {$record->milestone_title}")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => !in_array($record->status, ['paid', 'cancelled'])),
                    
                Tables\Actions\Action::make('sendNotification')
                    ->label('แจ้งเตือนการจ่าย')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('ส่งการแจ้งเตือนการจ่ายเงิน')
                    ->modalDescription('คุณต้องการส่งการแจ้งเตือนการจ่ายเงินงวดนี้ให้คณะกรรมการตรวจสอบหรือไม่?')
                    ->modalSubmitActionLabel('ส่งการแจ้งเตือน')
                    ->action(function ($record) {
                        $payer = \App\Models\User::find(auth()->id());
                        
                        if (!$record->purchaseOrder?->inspection_committee_id) {
                            \Filament\Notifications\Notification::make()
                                ->title('ไม่พบคณะกรรมการ')
                                ->body('กรุณาตรวจสอบว่า PO นี้มีคณะกรรมการตรวจสอบหรือไม่')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        try {
                            // Send email immediately (sync)
                            $paymentMilestone = \App\Models\PaymentMilestone::with([
                                'purchaseOrder.vendor', 
                                'purchaseOrder.inspectionCommittee'
                            ])->find($record->id);
                            
                            if ($paymentMilestone->purchaseOrder?->inspectionCommittee?->email) {
                                // Send to inspection committee
                                \Illuminate\Support\Facades\Mail::to($paymentMilestone->purchaseOrder->inspectionCommittee->email)
                                    ->send(new \App\Mail\PaymentMilestoneNotificationMail($paymentMilestone, $payer));
                                    
                                // Send copy to payer if different email
                                if ($payer->email !== $paymentMilestone->purchaseOrder->inspectionCommittee->email) {
                                    \Illuminate\Support\Facades\Mail::to($payer->email)
                                        ->send(new \App\Mail\PaymentMilestoneNotificationMail($paymentMilestone, $payer, true));
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('ส่งการแจ้งเตือนแล้ว')
                                ->body('ส่งการแจ้งเตือนการจ่ายเงินให้คณะกรรมการเรียบร้อยแล้ว')
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('เกิดข้อผิดพลาด')
                                ->body('ไม่สามารถส่งการแจ้งเตือนได้: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => $record->status === 'paid'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMilestones::route('/'),
            'create' => Pages\CreatePaymentMilestone::route('/create'),
            'edit' => Pages\EditPaymentMilestone::route('/{record}/edit'),
        ];
    }
}
