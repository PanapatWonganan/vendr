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
                            ->required(),
                        Forms\Components\Select::make('supplier_id')
                            ->label('ผู้ขาย')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
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
                Tables\Columns\TextColumn::make('supplier.name')
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
