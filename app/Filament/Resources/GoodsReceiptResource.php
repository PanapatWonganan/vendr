<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoodsReceiptResource\Pages;
use App\Filament\Resources\GoodsReceiptResource\RelationManagers;
use App\Models\GoodsReceipt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GoodsReceiptResource extends Resource
{
    protected static ?string $model = GoodsReceipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationLabel = 'ตรวจรับงาน/วัสดุ (GR/MR)';
    
    protected static ?string $modelLabel = 'ใบตรวจรับ';
    
    protected static ?string $pluralModelLabel = 'ใบตรวจรับงาน/วัสดุ';
    
    protected static ?string $navigationGroup = 'Procurement Management';
    
    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ข้อมูลใบสั่งซื้อ')
                    ->schema([
                        Forms\Components\Select::make('purchase_order_id')
                            ->label('เลือกใบสั่งซื้อ (PO)')
                            ->relationship('purchaseOrder', 'po_number', function ($query) {
                                return $query->where('status', 'approved');
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    // Use company connection from session
                                    $connection = session('company_connection', 'mysql');

                                    $po = \App\Models\PurchaseOrder::on($connection)
                                        ->with(['vendor', 'supplier'])
                                        ->find($state);

                                    if ($po) {
                                        // Get vendor info from PO (prefer vendor over supplier for compatibility)
                                        $vendor = $po->vendor ?: $po->supplier;
                                        if ($vendor) {
                                            $set('vendor_id', $vendor->id);
                                        }
                                    }
                                }
                            })
                            ->required(),
                        Forms\Components\Select::make('vendor_id')
                            ->label('ผู้ขาย')
                            ->options(function () {
                                // Query vendors from current company database
                                $connection = session('company_connection', 'mysql');
                                return \App\Models\Vendor::on($connection)
                                    ->pluck('company_name', 'id')
                                    ->toArray();
                            })
                            ->disabled(fn ($get) => !empty($get('purchase_order_id')))
                            ->dehydrated(true)
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->helperText(fn ($get) => $get('purchase_order_id')
                                ? '✓ ดึงข้อมูลจาก PO อัตโนมัติ (ไม่สามารถแก้ไขได้)'
                                : 'เลือก PO ก่อนเพื่อดึงข้อมูลผู้ขายอัตโนมัติ'),
                        Forms\Components\Select::make('inspection_committee_id')
                            ->label('คณะกรรมการตรวจสอบ')
                            ->relationship('inspectionCommittee', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])->columns(3),
                    
                Forms\Components\Section::make('ข้อมูลการตรวจรับ')
                    ->schema([
                        Forms\Components\TextInput::make('gr_number')
                            ->label('เลขที่ GR')
                            ->disabled()
                            ->placeholder('จะถูกสร้างอัตโนมัติ')
                            ->dehydrated(false),
                        Forms\Components\DatePicker::make('receipt_date')
                            ->label('วันที่รับ')
                            ->required()
                            ->default(now()),
                        Forms\Components\TextInput::make('delivery_milestone')
                            ->label('งวดที่')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\TextInput::make('milestone_percentage')
                            ->label('เปอร์เซ็นต์')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(100),
                        Forms\Components\Select::make('inspection_status')
                            ->label('สถานะตรวจสอบ')
                            ->required()
                            ->options([
                                'pending' => 'รอตรวจสอบ',
                                'passed' => 'ผ่านการตรวจสอบ',
                                'failed' => 'ไม่ผ่านการตรวจสอบ',
                                'partial' => 'ผ่านบางส่วน',
                            ])
                            ->default('pending'),
                        Forms\Components\Select::make('status')
                            ->label('สถานะ')
                            ->required()
                            ->options([
                                'draft' => 'แบบร่าง',
                                'completed' => 'เสร็จสมบูรณ์',
                                'returned' => 'ส่งคืน',
                                'partially_returned' => 'ส่งคืนบางส่วน',
                                'cancelled' => 'ยกเลิก',
                            ])
                            ->default('draft'),
                    ])->columns(3),
                    
                Forms\Components\Section::make('หมายเหตุ')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('หมายเหตุ')
                            ->rows(3),
                        Forms\Components\Textarea::make('inspection_notes')
                            ->label('หมายเหตุการตรวจสอบ')
                            ->rows(3),
                    ])->columns(2),
                    
                Forms\Components\Section::make('เอกสารแนบ')
                    ->schema([
                        Forms\Components\FileUpload::make('temp_attachments')
                            ->label('อัปโหลดไฟล์แนบ')
                            ->multiple()
                            ->directory('goods-receipts')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->maxSize(10240) // 10MB
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->helperText('รองรับไฟล์ PDF, รูปภาพ (JPG, PNG), Word, Excel ขนาดสูงสุด 10MB ต่อไฟล์')
                            ->storeFileNamesIn('attachment_files'),
                    ])
                    ->description('สามารถอัปโหลดหลายไฟล์พร้อมกัน'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('gr_number')
                    ->label('เลขที่ GR')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('เลขที่ PO')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.company_name')
                    ->label('ผู้ขาย')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('inspectionCommittee.name')
                    ->label('คณะกรรมการตรวจสอบ')
                    ->searchable()
                    ->limit(25)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('receipt_date')
                    ->label('วันที่รับ')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('delivery_milestone')
                    ->label('งวดที่')
                    ->formatStateUsing(fn ($state) => "งวดที่ {$state}")
                    ->color('info'),
                Tables\Columns\TextColumn::make('milestone_percentage')
                    ->label('%')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->alignCenter(),
                Tables\Columns\BadgeColumn::make('inspection_status')
                    ->label('สถานะตรวจสอบ')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'รอตรวจสอบ',
                        'passed' => 'ผ่านการตรวจสอบ',
                        'failed' => 'ไม่ผ่านการตรวจสอบ',
                        'partial' => 'ผ่านบางส่วน',
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'passed' => 'success',
                        'failed' => 'danger',
                        'partial' => 'info',
                        default => 'secondary'
                    }),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('สถานะ')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'แบบร่าง',
                        'completed' => 'เสร็จสมบูรณ์',
                        'returned' => 'ส่งคืน',
                        'partially_returned' => 'ส่งคืนบางส่วน',
                        'cancelled' => 'ยกเลิก',
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'draft' => 'gray',
                        'completed' => 'success',
                        'returned' => 'warning',
                        'partially_returned' => 'info',
                        'cancelled' => 'danger',
                        default => 'secondary'
                    }),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('ผู้สร้าง')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('email_status')
                    ->label('สถานะ Email')
                    ->getStateUsing(function ($record) {
                        if ($record->reminder_sent_at) {
                            return 'sent-manual';
                        } elseif ($record->committee_notified_at) {
                            return 'sent-auto';
                        } else {
                            return 'not-sent';
                        }
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'sent-manual' => 'heroicon-o-bell',
                        'sent-auto' => 'heroicon-o-check-circle',
                        'not-sent' => 'heroicon-o-x-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'sent-manual' => 'info',
                        'sent-auto' => 'success',
                        'not-sent' => 'danger',
                    })
                    ->tooltip(fn (string $state): string => match ($state) {
                        'sent-manual' => 'ส่งเตือนด้วยตนเอง',
                        'sent-auto' => 'ส่งอัตโนมัติแล้ว',
                        'not-sent' => 'ยังไม่ได้ส่ง',
                    })
                    ->sortable(false),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('วันที่สร้าง')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('สถานะ')
                    ->options([
                        'draft' => 'แบบร่าง',
                        'completed' => 'เสร็จสมบูรณ์',
                        'returned' => 'ส่งคืน',
                        'partially_returned' => 'ส่งคืนบางส่วน',
                        'cancelled' => 'ยกเลิก',
                    ]),
                Tables\Filters\SelectFilter::make('inspection_status')
                    ->label('สถานะตรวจสอบ')
                    ->options([
                        'pending' => 'รอตรวจสอบ',
                        'passed' => 'ผ่านการตรวจสอบ',
                        'failed' => 'ไม่ผ่านการตรวจสอบ',
                        'partial' => 'ผ่านบางส่วน',
                    ]),
                Tables\Filters\Filter::make('receipt_date')
                    ->label('ช่วงวันที่รับ')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('จากวันที่'),
                        Forms\Components\DatePicker::make('until')
                            ->label('ถึงวันที่'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date): Builder => $query->whereDate('receipt_date', '>=', $date))
                            ->when($data['until'], fn (Builder $query, $date): Builder => $query->whereDate('receipt_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('ดู'),
                Tables\Actions\EditAction::make()
                    ->label('แก้ไข'),
                Tables\Actions\Action::make('sendNotification')
                    ->label(function ($record) {
                        if ($record->reminder_sent_at) {
                            return 'ส่งเตือนอีกครั้ง';
                        } elseif ($record->committee_notified_at) {
                            return 'ส่งเตือนอีกครั้ง';
                        } else {
                            return 'แจ้งเตือนคณะกรรมการ';
                        }
                    })
                    ->icon('heroicon-o-bell')
                    ->color(function ($record) {
                        if ($record->reminder_sent_at || $record->committee_notified_at) {
                            return 'warning';  // ส่งอีกครั้ง
                        } else {
                            return 'info';     // ส่งครั้งแรก
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('ส่งการแจ้งเตือนใบตรวจรับ')
                    ->modalDescription('คุณต้องการส่งการแจ้งเตือนใบตรวจรับนี้ให้คณะกรรมการตรวจสอบหรือไม่?')
                    ->modalSubmitActionLabel('ส่งการแจ้งเตือน')
                    ->action(function ($record) {
                        $creator = \App\Models\User::find(auth()->id());
                        
                        if (!$record->inspection_committee_id) {
                            \Filament\Notifications\Notification::make()
                                ->title('ไม่พบคณะกรรมการ')
                                ->body('กรุณาเลือกคณะกรรมการตรวจสอบก่อนส่งการแจ้งเตือน')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        try {
                            // Send email immediately (sync)
                            $goodsReceipt = \App\Models\GoodsReceipt::with(['purchaseOrder', 'vendor', 'inspectionCommittee'])->find($record->id);
                            
                            if ($goodsReceipt->inspectionCommittee && $goodsReceipt->inspectionCommittee->email) {
                                // Send to inspection committee
                                \Illuminate\Support\Facades\Mail::to($goodsReceipt->inspectionCommittee->email)
                                    ->send(new \App\Mail\GoodsReceiptNotificationMail($goodsReceipt, $creator));
                                    
                                // Send copy to creator if different email
                                if ($creator->email !== $goodsReceipt->inspectionCommittee->email) {
                                    \Illuminate\Support\Facades\Mail::to($creator->email)
                                        ->send(new \App\Mail\GoodsReceiptNotificationMail($goodsReceipt, $creator, true));
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('ส่งการแจ้งเตือนแล้ว')
                                ->body('ส่งการแจ้งเตือนใบตรวจรับให้คณะกรรมการเรียบร้อยแล้ว')
                                ->success()
                                ->send();
                                
                            // Update reminder timestamp
                            $record->update(['reminder_sent_at' => now()]);
                            
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('เกิดข้อผิดพลาด')
                                ->body('ไม่สามารถส่งการแจ้งเตือนได้: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => $record->inspection_committee_id !== null),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('ลบ'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoodsReceipts::route('/'),
            'create' => Pages\CreateGoodsReceipt::route('/create'),
            'view' => Pages\ViewGoodsReceipt::route('/{record}'),
            'edit' => Pages\EditGoodsReceipt::route('/{record}/edit'),
        ];
    }
}
