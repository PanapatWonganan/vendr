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
    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'procurement_officer', 'procurement_manager', 'department_head', 'auditor']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ข้อมูลหลัก')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('va_number')
                                ->label('VA Number')
                                ->default(fn () => \App\Models\ValueAnalysis::generateVANumber())
                                ->readOnly()
                                ->dehydrated(true)
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
                                        $pr = \App\Models\PurchaseRequisition::find($state);
                                        if ($pr) {
                                            $set('work_type', $pr->work_type);
                                            $set('procurement_method', $pr->procurement_method);
                                            $budget = $pr->total_amount ?: $pr->procurement_budget ?: 0;
                                            $set('total_budget', $budget);
                                            $set('agreed_amount', $budget);
                                        }
                                    }
                                })
                                ->required(),
                        ]),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('work_type')
                                ->label('ประเภทงาน')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('procurement_method')
                                ->label('วิธีการจัดซื้อ')
                                ->maxLength(255),
                        ]),
                        Forms\Components\Textarea::make('procured_from')
                            ->label('จัดซื้อจาก (Vendor/Supplier)')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('สรุปวงเงิน')
                    ->description('กรอกวงเงินที่ตกลงได้ ระบบคำนวณส่วนต่างให้อัตโนมัติ')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('total_budget')
                                ->label('งบประมาณจาก PR')
                                ->numeric()
                                ->prefix('฿')
                                ->readOnly()
                                ->dehydrated(true)
                                ->helperText('ดึงอัตโนมัติจาก PR'),
                            Forms\Components\TextInput::make('agreed_amount')
                                ->label('วงเงินที่ตกลง')
                                ->numeric()
                                ->prefix('฿')
                                ->required()
                                ->live(debounce: 500)
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    $budget = (float) ($get('total_budget') ?? 0);
                                    $agreed = (float) ($state ?? 0);
                                    if ($budget > 0) {
                                        $diff = $budget - $agreed;
                                        $pct = round(($diff / $budget) * 100, 2);
                                        $set('savings_display', ($pct >= 0 ? 'ประหยัด ' : 'เกินงบ ') . abs($pct) . '% (฿' . number_format(abs($diff), 2) . ')');
                                    } else {
                                        $set('savings_display', '-');
                                    }
                                })
                                ->helperText('กรอกยอดที่ตกลงได้จริง'),
                            Forms\Components\TextInput::make('currency')
                                ->label('สกุลเงิน')
                                ->required()
                                ->maxLength(3)
                                ->default('THB'),
                        ]),
                        Forms\Components\TextInput::make('savings_display')
                            ->label('ส่วนต่าง')
                            ->readOnly()
                            ->dehydrated(false)
                            ->default('-')
                            ->helperText('คำนวณอัตโนมัติ'),
                    ]),

                Forms\Components\Section::make('รายละเอียดการวิเคราะห์')
                    ->schema([
                        Forms\Components\Textarea::make('recommendations')
                            ->label('เหตุผล / ข้อเสนอแนะ')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('conclusion')
                            ->label('สรุปผล')
                            ->rows(3)
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
