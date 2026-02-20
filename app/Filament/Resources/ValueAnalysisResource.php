<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ValueAnalysisResource\Pages;
use App\Filament\Resources\ValueAnalysisResource\RelationManagers;
use App\Models\ValueAnalysis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ValueAnalysisResource extends Resource
{
    protected static ?string $model = ValueAnalysis::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Vendor Approve';
    protected static ?string $pluralModelLabel = 'Vendor Approve';
    protected static ?string $navigationGroup = 'Procurement Management';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('va_number')
                    ->label('VA Number')
                    ->default(fn () => \App\Models\ValueAnalysis::generateVANumber())
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('purchase_requisition_id')
                    ->label('เลือก Purchase Requisition')
                    ->relationship(
                        name: 'purchaseRequisition',
                        titleAttribute: 'pr_number',
                        modifyQueryUsing: fn (Builder $query) => 
                            $query->when(
                                session('company_id'),
                                fn ($q, $companyId) => $q->where('company_id', $companyId)
                            )
                    )
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        $title = !empty($record->title) ? " - {$record->title}" : '';
                        return $record->pr_number . $title;
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $pr = \App\Models\PurchaseRequisition::with('items')->find($state);
                            if ($pr) {
                                $set('work_type', $pr->work_type);
                                $set('procurement_method', $pr->procurement_method);
                                $set('total_budget', $pr->total_amount ?: $pr->procurement_budget);

                                // Auto-copy PR items into VA items
                                $vaItems = $pr->items->map(function ($item, $index) {
                                    $estAmount = $item->estimated_amount ?? ($item->quantity * ($item->estimated_unit_price ?? 0));
                                    return [
                                        'purchase_requisition_item_id' => $item->id,
                                        'line_number' => $index + 1,
                                        'item_code' => $item->item_code,
                                        'description' => $item->description,
                                        'quantity' => $item->quantity,
                                        'unit_of_measure' => $item->unit_of_measure,
                                        'estimated_unit_price' => $item->estimated_unit_price ?? 0,
                                        'estimated_amount' => $estAmount,
                                        'agreed_unit_price' => $item->estimated_unit_price ?? 0,
                                        'agreed_amount' => $estAmount,
                                        'remarks' => null,
                                    ];
                                })->toArray();

                                $set('items', $vaItems);
                                $set('agreed_amount', collect($vaItems)->sum('agreed_amount'));
                            }
                        }
                    })
                    ->required(),
                Forms\Components\TextInput::make('work_type')
                    ->label('Work Type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('procurement_method')
                    ->label('Procurement Method')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Textarea::make('procured_from')
                    ->label('Procured From (Vendor/Supplier)')
                    ->columnSpanFull(),
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('total_budget')
                        ->label('งบประมาณจาก PR (THB)')
                        ->numeric()
                        ->prefix('฿')
                        ->readonly()
                        ->dehydrated(true)
                        ->helperText('ดึงอัตโนมัติจาก PR'),
                    Forms\Components\TextInput::make('agreed_amount')
                        ->label('วงเงินที่ตกลง (THB)')
                        ->numeric()
                        ->prefix('฿')
                        ->readonly()
                        ->dehydrated(true)
                        ->helperText('คำนวณจากรายการด้านล่าง'),
                    Forms\Components\TextInput::make('currency')
                        ->required()
                        ->maxLength(3)
                        ->default('THB'),
                ]),

                // VA Line Items — เปรียบเทียบราคาจาก PR กับราคาที่ตกลงได้
                Forms\Components\Section::make('รายการสินค้า/บริการ (เปรียบเทียบราคา)')
                    ->description('แก้ไข "ราคาที่ตกลง" ต่อรายการ เพื่อกำหนดราคาสุดท้ายก่อนสร้าง PO')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Grid::make(5)->schema([
                                    Forms\Components\TextInput::make('item_code')
                                        ->label('รหัส')
                                        ->maxLength(50)
                                        ->readOnly()
                                        ->dehydrated(true),
                                    Forms\Components\Textarea::make('description')
                                        ->label('รายละเอียด')
                                        ->required()
                                        ->rows(2)
                                        ->columnSpan(2),
                                    Forms\Components\TextInput::make('quantity')
                                        ->label('จำนวน')
                                        ->numeric()
                                        ->required()
                                        ->readOnly()
                                        ->dehydrated(true),
                                    Forms\Components\TextInput::make('unit_of_measure')
                                        ->label('หน่วย')
                                        ->required()
                                        ->readOnly()
                                        ->dehydrated(true),
                                ]),
                                Forms\Components\Grid::make(4)->schema([
                                    Forms\Components\TextInput::make('estimated_unit_price')
                                        ->label('ราคาประมาณการ (จาก PR)')
                                        ->numeric()
                                        ->prefix('฿')
                                        ->readOnly()
                                        ->dehydrated(true)
                                        ->helperText('จาก PR'),
                                    Forms\Components\TextInput::make('agreed_unit_price')
                                        ->label('ราคาที่ตกลง')
                                        ->numeric()
                                        ->prefix('฿')
                                        ->required()
                                        ->step(0.01)
                                        ->minValue(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            $qty = (float) ($get('quantity') ?? 0);
                                            $price = (float) ($state ?? 0);
                                            $set('agreed_amount', round($qty * $price, 2));
                                        })
                                        ->helperText('แก้ไขได้'),
                                    Forms\Components\TextInput::make('agreed_amount')
                                        ->label('รวม (ตกลง)')
                                        ->numeric()
                                        ->prefix('฿')
                                        ->readonly()
                                        ->dehydrated(true),
                                    Forms\Components\TextInput::make('remarks')
                                        ->label('หมายเหตุ')
                                        ->placeholder('เหตุผลการปรับราคา'),
                                ]),
                                Forms\Components\Hidden::make('line_number')->default(1),
                                Forms\Components\Hidden::make('purchase_requisition_item_id'),
                                Forms\Components\Hidden::make('estimated_amount'),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                                ($state['item_code'] ?? '') . ' ' . mb_substr($state['description'] ?? '', 0, 50)
                            ),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('recalculate_totals')
                                ->label('คำนวณยอดรวมใหม่')
                                ->icon('heroicon-o-calculator')
                                ->color('info')
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $items = $get('items') ?? [];
                                    $totalAgreed = 0;
                                    foreach ($items as $item) {
                                        $totalAgreed += (float) ($item['agreed_amount'] ?? 0);
                                    }
                                    $set('agreed_amount', round($totalAgreed, 2));
                                }),
                        ]),
                    ]),

                Forms\Components\Section::make('รายละเอียดการวิเคราะห์')
                    ->schema([
                        Forms\Components\Textarea::make('analysis_objective')
                            ->label('วัตถุประสงค์')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('analysis_scope')
                            ->label('ขอบเขต')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('evaluation_criteria')
                            ->label('เกณฑ์การประเมิน')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('alternatives')
                            ->label('ทางเลือก')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('comparison_matrix')
                            ->label('ตารางเปรียบเทียบ')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('recommendations')
                            ->label('ข้อเสนอแนะ')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('conclusion')
                            ->label('สรุปผล')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                Forms\Components\Section::make('สถานะ & ผู้รับผิดชอบ')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('status')
                                ->label('สถานะ')
                                ->options([
                                    'draft' => 'ร่าง',
                                    'in_progress' => 'กำลังวิเคราะห์',
                                    'completed' => 'วิเคราะห์เสร็จสิ้น',
                                    'approved' => 'อนุมัติแล้ว',
                                    'rejected' => 'ถูกปฏิเสธ',
                                ])
                                ->default('draft')
                                ->required(),
                            Forms\Components\DateTimePicker::make('analysis_date')
                                ->label('วันที่วิเคราะห์'),
                        ]),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('analyzed_by')
                                ->label('ผู้วิเคราะห์')
                                ->relationship('analyzer', 'name')
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('approved_by')
                                ->label('ผู้อนุมัติ')
                                ->relationship('approver', 'name')
                                ->searchable()
                                ->preload(),
                        ]),
                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('วันที่อนุมัติ'),
                        Forms\Components\Hidden::make('created_by')
                            ->default(fn () => auth()->id())
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $companyId = session('company_id');
                if ($companyId) {
                    $query->whereHas('purchaseRequisition', function ($q) use ($companyId) {
                        $q->where('company_id', $companyId);
                    });
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('va_number')
                    ->label('VA Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseRequisition.title')
                    ->label('Purchase Requisition')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('work_type')
                    ->label('Work Type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_budget')
                    ->label('Original Budget')
                    ->money('THB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('agreed_amount')
                    ->label('Negotiated Amount')
                    ->money('THB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('savings_percentage')
                    ->label('Savings %')
                    ->getStateUsing(function ($record) {
                        if ($record->total_budget && $record->agreed_amount && $record->total_budget > 0) {
                            $savings = (($record->total_budget - $record->agreed_amount) / $record->total_budget) * 100;
                            return round($savings, 2) . '%';
                        }
                        return 'N/A';
                    })
                    ->color(fn ($state) => $state !== 'N/A' && floatval($state) > 0 ? 'success' : 'gray')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'in_progress',
                        'info' => 'completed',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('analysis_date')
                    ->label('Analysis Date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListValueAnalyses::route('/'),
            'create' => Pages\CreateValueAnalysis::route('/create'),
            'edit' => Pages\EditValueAnalysis::route('/{record}/edit'),
        ];
    }
}
