<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorEvaluationResource\Pages;
use App\Filament\Resources\VendorEvaluationResource\RelationManagers;
use App\Models\VendorEvaluation;
use App\Models\VendorEvaluationItem;
use App\Models\Vendor;
use App\Models\User;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Forms\Components\Placeholder;

class VendorEvaluationResource extends Resource
{
    protected static ?string $model = VendorEvaluation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationLabel = 'ประเมินผู้ขาย';
    
    protected static ?string $modelLabel = 'การประเมินผู้ขาย';
    
    protected static ?string $pluralModelLabel = 'การประเมินผู้ขาย';
    
    protected static ?string $navigationGroup = 'Master Data';
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $companyId = session('company_id');
        
        if ($companyId) {
            return $query->where('company_id', $companyId);
        }
        
        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('สรุปการประเมินคู่ค้า')
                    ->schema([
                        Select::make('purchase_order_id')
                            ->label('เลขที่สัญญา PO')
                            ->options(function () {
                                $companyId = session('company_id');
                                return PurchaseOrder::when($companyId, fn($q) => $q->where('company_id', $companyId))
                                    ->where('status', 'approved')
                                    ->with('vendor')
                                    ->get()
                                    ->mapWithKeys(function ($po) {
                                        return [$po->id => $po->po_number . ' - ' . ($po->vendor->company_name ?? 'N/A')];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                if ($state) {
                                    $po = PurchaseOrder::with(['vendor', 'inspectionCommittee'])->find($state);
                                    if ($po) {
                                        // Auto-fill vendor from PO
                                        $set('vendor_id', $po->vendor_id);
                                        
                                        // Auto-fill project name from PO
                                        $set('project_name', $po->po_title);
                                        
                                        // Set committee members
                                        $committeeMembers = [];
                                        if ($po->inspection_committee_id) {
                                            $committeeMembers[] = [
                                                'user_id' => $po->inspection_committee_id,
                                                'name' => $po->inspectionCommittee->name ?? '',
                                                'position' => 'ประธานกรรมการ',
                                                'evaluation_date' => now()->format('Y-m-d')
                                            ];
                                        }
                                        $set('committee_members', $committeeMembers);
                                        
                                        // Parse payment terms if available
                                        if ($po->payment_terms) {
                                            $set('payment_term_description', $po->payment_terms);
                                        }
                                    }
                                } else {
                                    // Clear all fields when PO is deselected
                                    $set('vendor_id', null);
                                    $set('project_name', null);
                                    $set('committee_members', []);
                                    $set('payment_term_description', null);
                                }
                            }),
                            
                        Select::make('payment_term_number')
                            ->label('งวดที่ชำระ')
                            ->options([
                                1 => 'งวดที่ 1',
                                2 => 'งวดที่ 2',
                                3 => 'งวดที่ 3',
                                4 => 'งวดที่ 4',
                                5 => 'งวดที่ 5',
                            ])
                            ->required(),
                            
                        TextInput::make('project_name')
                            ->label('ชื่องาน')
                            ->disabled()
                            ->dehydrated(),
                            
                        Placeholder::make('vendor_display')
                            ->label('รายชื่อผู้ค้า')
                            ->content(function ($record, $get) {
                                $vendorId = $get('vendor_id') ?? $record?->vendor_id;
                                if ($vendorId) {
                                    $vendor = Vendor::find($vendorId);
                                    return $vendor ? $vendor->company_name : 'ไม่พบข้อมูล';
                                }
                                return 'ยังไม่ได้เลือก';
                            }),
                            
                        Hidden::make('vendor_id')
                            ->dehydrated(),
                    ])->columns(2),
                    
                Section::make('คณะกรรมการตรวจรับ')
                    ->schema([
                        Repeater::make('committee_members')
                            ->label('รายชื่อผู้ประเมิน')
                            ->schema([
                                Select::make('user_id')
                                    ->label('ชื่อ-นามสกุล')
                                    ->options(function () {
                                        // Simply get all users for now
                                        // You can add more filtering based on your needs
                                        return \App\Models\User::pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        if ($state) {
                                            $user = \App\Models\User::find($state);
                                            if ($user) {
                                                $set('name', $user->name);
                                                // Set position based on user's department or role
                                                $position = 'กรรมการ';
                                                if ($user->department) {
                                                    $position = $user->department->name ?? 'กรรมการ';
                                                }
                                                $set('position', $position);
                                            }
                                        }
                                    })
                                    ->required(),
                                
                                Hidden::make('name')
                                    ->dehydrated(),
                                    
                                Select::make('position')
                                    ->label('ตำแหน่ง')
                                    ->options([
                                        'ประธานกรรมการ' => 'ประธานกรรมการ',
                                        'กรรมการ' => 'กรรมการ',
                                        'กรรมการและเลขานุการ' => 'กรรมการและเลขานุการ',
                                        'ผู้สังเกตการณ์' => 'ผู้สังเกตการณ์',
                                    ])
                                    ->default('กรรมการ')
                                    ->required(),
                                    
                                DatePicker::make('evaluation_date')
                                    ->label('วันที่ประเมิน')
                                    ->required()
                                    ->default(now()),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('เพิ่มกรรมการ'),
                            
                        DatePicker::make('evaluation_date')
                            ->label('วันที่ประเมินรวม')
                            ->required()
                            ->default(now()),
                    ]),
                    
                Section::make('ข้อมูลการประเมิน')
                    ->schema([
                        Hidden::make('company_id')
                            ->default(fn () => session('company_id')),
                            
                        Hidden::make('evaluator_id')
                            ->default(fn () => auth()->id()),
                            
                        TextInput::make('evaluation_period')
                            ->label('รอบการประเมิน')
                            ->placeholder('เช่น Q1-2024, Q2-2024')
                            ->default(fn() => 'Q' . ceil(date('n')/3) . '-' . date('Y')),
                            
                        DatePicker::make('period_start')
                            ->label('วันที่เริ่มต้นรอบประเมิน')
                            ->default(now()->startOfQuarter()),
                            
                        DatePicker::make('period_end')
                            ->label('วันที่สิ้นสุดรอบประเมิน')
                            ->default(now()->endOfQuarter()),
                    ])->columns(3)->collapsed(),
                    
                Section::make('หัวข้อการประเมิน')
                    ->schema([
                        Forms\Components\Actions::make([
                            Action::make('loadDefaultCriteria')
                                ->label('โหลดเกณฑ์มาตรฐาน')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->action(function (Set $set, Get $get) {
                                    $defaultCriteria = VendorEvaluationItem::getDefaultCriteria();
                                    $items = [];
                                    
                                    foreach ($defaultCriteria as $category => $data) {
                                        foreach ($data['items'] as $item) {
                                            $items[] = [
                                                'criteria_category' => $category,
                                                'criteria_name' => $data['name'],
                                                'criteria_description' => $item,
                                                'score' => null,
                                                'is_applicable' => true,
                                                'comments' => '',
                                                'evidence' => '',
                                                'weight' => 1.00
                                            ];
                                        }
                                    }
                                    
                                    $set('evaluationItems', $items);
                                }),
                        ]),
                        
                        Repeater::make('evaluationItems')
                            ->relationship('evaluationItems')
                            ->schema([
                                Select::make('criteria_category')
                                    ->label('หมวดหมู่')
                                    ->options(VendorEvaluationItem::getCategoryOptions())
                                    ->required(),
                                    
                                TextInput::make('criteria_name')
                                    ->label('หัวข้อหลัก')
                                    ->placeholder('เช่น คุณภาพสินค้า/บริการ')
                                    ->required(),
                                    
                                Textarea::make('criteria_description')
                                    ->label('หัวข้อประเมิน')
                                    ->placeholder('รายละเอียดเกณฑ์การประเมิน')
                                    ->required()
                                    ->rows(2),
                                    
                                Toggle::make('is_applicable')
                                    ->label('สามารถประเมินได้')
                                    ->default(true)
                                    ->live(),
                                    
                                Radio::make('score')
                                    ->label('คะแนนประเมิน')
                                    ->options([
                                        '4' => '4 - ดีมาก',
                                        '3' => '3 - ดี',
                                        '2' => '2 - พอใช้',
                                        '1' => '1 - ควรปรับปรุง',
                                    ])
                                    ->inline()
                                    ->visible(fn (Get $get) => $get('is_applicable'))
                                    ->required(fn (Get $get) => $get('is_applicable')),
                                    
                                Textarea::make('comments')
                                    ->label('ความคิดเห็น/หมายเหตุ')
                                    ->rows(2),
                                    
                                Textarea::make('evidence')
                                    ->label('หลักฐาน/ตัวอย่าง')
                                    ->rows(2),
                                    
                                TextInput::make('weight')
                                    ->label('ระดับความสำคัญ (1-5)')
                                    ->helperText('1=สำคัญน้อย, 3=สำคัญปานกลาง, 5=สำคัญมาก')
                                    ->numeric()
                                    ->default(3.00)
                                    ->step(1)
                                    ->minValue(1)
                                    ->maxValue(5),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('เพิ่มเกณฑ์การประเมิน')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['criteria_name'] ?? 'เกณฑ์ใหม่')
                    ]),
                    
                Section::make('สรุปผลการประเมิน')
                    ->schema([
                        Forms\Components\Placeholder::make('grade_summary')
                            ->label('สรุปเกรดการประเมิน')
                            ->content(function ($record) {
                                if (!$record || !$record->overall_score) {
                                    return 'ยังไม่ได้คำนวณคะแนน - กรุณาบันทึกและคำนวณคะแนนก่อน';
                                }
                                
                                $avgScore = $record->average_score;
                                $grade = $record->score_grade;
                                $detail = $record->score_grade_detail;
                                
                                return new \Illuminate\Support\HtmlString(
                                    "<div class='space-y-2'>
                                        <div class='text-2xl font-bold'>เกรด: {$grade}</div>
                                        <div>คะแนนเฉลี่ย: {$avgScore}/4.00</div>
                                        <div class='text-sm text-gray-600'>{$detail}</div>
                                        <div class='mt-2 p-2 bg-gray-100 rounded'>
                                            <div class='font-semibold'>เกณฑ์การตัดเกรด:</div>
                                            <div class='text-sm'>
                                                A: 3.50-4.00 (ดีมาก)<br>
                                                B: 2.50-3.49 (ดี)<br>
                                                C: 1.50-2.49 (พอใช้)<br>
                                                D: น้อยกว่า 1.50 (ควรปรับปรุง)
                                            </div>
                                        </div>
                                    </div>"
                                );
                            }),
                    ]),
                    
                Section::make('สรุปและความคิดเห็น')
                    ->schema([
                        Textarea::make('general_comments')
                            ->label('ความคิดเห็นทั่วไป')
                            ->rows(3),
                            
                        Textarea::make('recommendations')
                            ->label('ข้อเสนอแนะ')
                            ->rows(3),
                            
                        Textarea::make('areas_for_improvement')
                            ->label('จุดที่ควรพัฒนา')
                            ->rows(3),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purchaseOrder.po_number')
                    ->label('เลขที่ PO')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('vendor.company_name')
                    ->label('ผู้ขาย')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('payment_term_number')
                    ->label('งวดที่')
                    ->formatStateUsing(fn ($state) => $state ? "งวดที่ $state" : '-')
                    ->sortable(),
                    
                TextColumn::make('evaluation_period')
                    ->label('รอบประเมิน')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('evaluation_date')
                    ->label('วันที่ประเมิน')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                TextColumn::make('average_score')
                    ->label('คะแนนเฉลี่ย')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . '/4.00' : 'ยังไม่คำนวณ')
                    ->sortable(),
                    
                BadgeColumn::make('score_grade')
                    ->label('เกรด')
                    ->colors([
                        'success' => 'A',
                        'info' => 'B',
                        'warning' => 'C',
                        'danger' => 'D',
                        'gray' => 'N/A',
                    ])
                    ->formatStateUsing(fn ($state) => $state),
                    
                TextColumn::make('score_grade_detail')
                    ->label('ผลการประเมิน')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                BadgeColumn::make('status')
                    ->label('สถานะ')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'submitted',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'ร่าง',
                        'submitted' => 'ส่งแล้ว',
                        'approved' => 'อนุมัติแล้ว',
                        'rejected' => 'ปฏิเสธ',
                        default => $state
                    }),
                    
                TextColumn::make('evaluator.name')
                    ->label('ผู้ประเมิน')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('created_at')
                    ->label('วันที่สร้าง')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('vendor_id')
                    ->label('ผู้ขาย')
                    ->options(function () {
                        $companyId = session('company_id');
                        return Vendor::when($companyId, fn($q) => $q->where('company_id', $companyId))
                            ->pluck('company_name', 'id');
                    })
                    ->searchable(),
                    
                SelectFilter::make('status')
                    ->label('สถานะ')
                    ->options([
                        'draft' => 'ร่าง',
                        'submitted' => 'ส่งแล้ว',
                        'approved' => 'อนุมัติแล้ว',
                        'rejected' => 'ปฏิเสธ',
                    ]),
                    
                SelectFilter::make('evaluation_period')
                    ->label('รอบประเมิน')
                    ->options(function () {
                        $companyId = session('company_id');
                        return VendorEvaluation::when($companyId, fn($q) => $q->where('company_id', $companyId))
                            ->distinct()
                            ->pluck('evaluation_period', 'evaluation_period');
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (VendorEvaluation $record) => $record->canBeEdited()),
                Tables\Actions\Action::make('calculate_score')
                    ->label('คำนวณคะแนน')
                    ->icon('heroicon-o-calculator')
                    ->action(fn (VendorEvaluation $record) => $record->calculateOverallScore())
                    ->requiresConfirmation()
                    ->modalHeading('คำนวณคะแนนรวม')
                    ->modalDescription('คำนวณคะแนนรวมจากเกณฑ์การประเมินทั้งหมด'),
                    
                Tables\Actions\Action::make('submit')
                    ->label('ส่งประเมิน')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->action(fn (VendorEvaluation $record) => $record->update(['status' => 'submitted']))
                    ->visible(fn (VendorEvaluation $record) => $record->status === 'draft')
                    ->requiresConfirmation(),
                    
                Tables\Actions\Action::make('approve')
                    ->label('อนุมัติ')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (VendorEvaluation $record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now()
                        ]);
                    })
                    ->visible(fn (VendorEvaluation $record) => $record->canBeApproved())
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EvaluationItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorEvaluations::route('/'),
            'create' => Pages\CreateVendorEvaluation::route('/create'),
            'edit' => Pages\EditVendorEvaluation::route('/{record}/edit'),
        ];
    }
}
