<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TermsOfReferenceResource\Pages;
use App\Models\PurchaseRequisition;
use App\Models\TermsOfReference;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TermsOfReferenceResource extends Resource
{
    protected static ?string $model = TermsOfReference::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'TOR Management';
    protected static ?string $navigationGroup = 'Procurement Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'TOR';
    protected static ?string $pluralModelLabel = 'Terms of References';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'admin', 'requester', 'department_head',
            'procurement_officer', 'procurement_manager',
            'procurement_committee', 'auditor',
        ]) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $companyId = session('company_id');
        return parent::getEloquentQuery()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId));
    }

    public static function getNavigationBadge(): ?string
    {
        $companyId = session('company_id');
        $user = Auth::user();
        if (!$user) return null;

        $count = TermsOfReference::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['submitted', 'reviewing'])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    // ─── Step 1: Basic Information ────────────────
                    Forms\Components\Wizard\Step::make('ข้อมูลพื้นฐาน')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('ชื่อ TOR / Title')
                                    ->required()
                                    ->maxLength(500)
                                    ->placeholder('ระบุชื่อ TOR')
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('department_id')
                                    ->label('แผนก / Department')
                                    ->relationship('department', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('tor_type')
                                    ->label('ประเภท TOR')
                                    ->required()
                                    ->options(TermsOfReference::getTorTypeOptions())
                                    ->default('goods')
                                    ->searchable(),

                                Forms\Components\Select::make('form_category')
                                    ->label('แบบฟอร์ม')
                                    ->options(function () {
                                        if (session('company_id') == 2) {
                                            return ['law_based' => 'แบบฟอร์มเชิงพาณิชย์'];
                                        }
                                        return TermsOfReference::getFormCategoryOptions();
                                    })
                                    ->default(fn () => session('company_id') == 2 ? 'law_based' : null)
                                    ->disabled(fn () => session('company_id') == 2)
                                    ->dehydrated(true)
                                    ->searchable(),

                                Forms\Components\Select::make('work_type')
                                    ->label('ประเภทงาน')
                                    ->required()
                                    ->options(TermsOfReference::getWorkTypeOptions())
                                    ->searchable(),

                                Forms\Components\Select::make('procurement_method')
                                    ->label('วิธีจัดซื้อ')
                                    ->options(TermsOfReference::getProcurementMethodOptions())
                                    ->searchable(),

                                Forms\Components\Select::make('category')
                                    ->label('หมวดหมู่')
                                    ->options(TermsOfReference::getCategoryOptions())
                                    ->searchable(),

                                Forms\Components\Select::make('priority')
                                    ->label('ลำดับความสำคัญ')
                                    ->required()
                                    ->default('medium')
                                    ->options(TermsOfReference::getPriorityOptions()),

                                Forms\Components\TextInput::make('project_name')
                                    ->label('ชื่อโครงการ')
                                    ->maxLength(500)
                                    ->placeholder('ชื่อโครงการ (ถ้ามี)'),

                                Forms\Components\TextInput::make('project_code')
                                    ->label('รหัสโครงการ')
                                    ->maxLength(100)
                                    ->placeholder('e.g., PRJ-2026-001'),
                            ]),
                        ]),

                    // ─── Step 2: Scope of Work ───────────────────
                    Forms\Components\Wizard\Step::make('ขอบเขตงาน')
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->schema([
                            Forms\Components\Livewire::make(\App\Livewire\TorAiDraft::class)
                                ->columnSpanFull()
                                ->lazy(),

                            Forms\Components\RichEditor::make('background')
                                ->label('ความเป็นมา / หลักการและเหตุผล')
                                ->placeholder('อธิบายความเป็นมาและเหตุผลที่ต้องจัดซื้อจัดจ้าง...')
                                ->columnSpanFull(),

                            Forms\Components\RichEditor::make('objectives')
                                ->label('วัตถุประสงค์')
                                ->placeholder('ระบุวัตถุประสงค์ของการจัดซื้อจัดจ้าง...')
                                ->columnSpanFull(),

                            Forms\Components\RichEditor::make('scope_of_work')
                                ->label('ขอบเขตของงาน')
                                ->required()
                                ->placeholder('ระบุขอบเขตของงานโดยละเอียด...')
                                ->columnSpanFull(),

                            Forms\Components\RichEditor::make('deliverables')
                                ->label('ผลงานที่ต้องส่งมอบ')
                                ->placeholder('ระบุผลงานที่ต้องส่งมอบ...')
                                ->columnSpanFull(),

                            Forms\Components\RichEditor::make('specifications')
                                ->label('ข้อกำหนดทางเทคนิค')
                                ->placeholder('ระบุข้อกำหนดทางเทคนิค (Specifications)...')
                                ->columnSpanFull(),
                        ]),

                    // ─── Step 3: Conditions & Qualifications ─────
                    Forms\Components\Wizard\Step::make('เงื่อนไขและคุณสมบัติ')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->schema([
                            Forms\Components\RichEditor::make('qualification_requirements')
                                ->label('คุณสมบัติผู้เสนอราคา')
                                ->placeholder('ระบุคุณสมบัติที่ผู้เสนอราคาต้องมี...')
                                ->columnSpanFull(),

                            Forms\Components\Repeater::make('evaluation_criteria')
                                ->label('เกณฑ์ประเมิน')
                                ->schema([
                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('ชื่อเกณฑ์')
                                            ->required(),
                                        Forms\Components\TextInput::make('weight')
                                            ->label('น้ำหนัก (%)')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('%'),
                                        Forms\Components\TextInput::make('description')
                                            ->label('คำอธิบาย'),
                                    ]),
                                ])
                                ->columnSpanFull()
                                ->collapsible()
                                ->addActionLabel('เพิ่มเกณฑ์ประเมิน')
                                ->itemLabel(fn (array $state): ?string => ($state['name'] ?? '') . ' (' . ($state['weight'] ?? 0) . '%)'),

                            Forms\Components\RichEditor::make('payment_terms')
                                ->label('เงื่อนไขการจ่ายเงิน')
                                ->placeholder('ระบุเงื่อนไขการจ่ายเงิน...')
                                ->columnSpanFull(),

                            Forms\Components\RichEditor::make('warranty_requirements')
                                ->label('การรับประกัน')
                                ->placeholder('ระบุเงื่อนไขการรับประกัน...')
                                ->columnSpanFull(),

                            Forms\Components\RichEditor::make('penalty_clause')
                                ->label('เงื่อนไขเบี้ยปรับ')
                                ->placeholder('ระบุเงื่อนไขเบี้ยปรับกรณีส่งมอบล่าช้า...')
                                ->columnSpanFull(),
                        ]),

                    // ─── Step 4: Items & Budget ──────────────────
                    Forms\Components\Wizard\Step::make('รายการและงบประมาณ')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->relationship()
                                ->label('รายการ')
                                ->schema([
                                    Forms\Components\Grid::make(6)->schema([
                                        Forms\Components\Hidden::make('item_number'),

                                        Forms\Components\TextInput::make('description')
                                            ->label('รายละเอียด')
                                            ->required()
                                            ->columnSpan(2),

                                        Forms\Components\Textarea::make('specifications')
                                            ->label('ข้อกำหนด')
                                            ->rows(1)
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('quantity')
                                            ->label('จำนวน')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                $unitPrice = (float) $get('estimated_unit_price');
                                                $quantity = (float) $state;
                                                $set('estimated_total_price', $unitPrice * $quantity);
                                            }),

                                        Forms\Components\TextInput::make('unit_of_measure')
                                            ->label('หน่วย')
                                            ->placeholder('ชิ้น, ชุด, งาน'),

                                        Forms\Components\TextInput::make('estimated_unit_price')
                                            ->label('ราคาต่อหน่วย')
                                            ->numeric()
                                            ->prefix('฿')
                                            ->step(0.01)
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                $unitPrice = (float) $state;
                                                $quantity = (float) $get('quantity');
                                                $set('estimated_total_price', $unitPrice * $quantity);
                                            }),

                                        Forms\Components\TextInput::make('estimated_total_price')
                                            ->label('ราคารวม')
                                            ->numeric()
                                            ->prefix('฿')
                                            ->disabled()
                                            ->dehydrated(),

                                        Forms\Components\TextInput::make('delivery_location')
                                            ->label('สถานที่ส่งมอบ'),

                                        Forms\Components\DatePicker::make('required_date')
                                            ->label('ต้องการภายในวันที่'),

                                        Forms\Components\Textarea::make('remarks')
                                            ->label('หมายเหตุ')
                                            ->rows(1)
                                            ->columnSpan(2),
                                    ]),
                                ])
                                ->columnSpanFull()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['description'] ?? 'รายการใหม่')
                                ->addActionLabel('เพิ่มรายการ')
                                ->reorderableWithButtons()
                                ->cloneable(),

                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('budget_estimate')
                                    ->label('งบประมาณโดยประมาณ')
                                    ->numeric()
                                    ->prefix('฿')
                                    ->step(0.01)
                                    ->placeholder('0.00'),

                                Forms\Components\TextInput::make('budget_code')
                                    ->label('รหัสงบประมาณ')
                                    ->placeholder('e.g., BDG-2026-001'),

                                Forms\Components\Select::make('currency')
                                    ->label('สกุลเงิน')
                                    ->default('THB')
                                    ->options([
                                        'THB' => 'THB - บาท',
                                        'USD' => 'USD - ดอลลาร์สหรัฐ',
                                        'EUR' => 'EUR - ยูโร',
                                        'JPY' => 'JPY - เยนญี่ปุ่น',
                                    ]),
                            ]),

                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('วันเริ่มต้น')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $endDate = $get('end_date');
                                        if ($state && $endDate) {
                                            $days = \Carbon\Carbon::parse($state)->diffInDays(\Carbon\Carbon::parse($endDate));
                                            $set('duration_days', $days);
                                        }
                                    }),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('วันสิ้นสุด')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $startDate = $get('start_date');
                                        if ($state && $startDate) {
                                            $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($state));
                                            $set('duration_days', $days);
                                        }
                                    }),

                                Forms\Components\TextInput::make('duration_days')
                                    ->label('ระยะเวลา (วัน)')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                        ]),

                    // ─── Step 5: Committee & Documents ───────────
                    Forms\Components\Wizard\Step::make('คณะกรรมการและเอกสาร')
                        ->icon('heroicon-o-user-group')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Select::make('procurement_committee_id')
                                    ->label('คณะกรรมการจัดซื้อ')
                                    ->options(function () {
                                        return \App\Models\User::whereHas('roles', function ($query) {
                                            $query->where('name', 'procurement_committee');
                                        })->orderBy('name')->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->placeholder('เลือกคณะกรรมการจัดซื้อ'),

                                Forms\Components\Select::make('inspection_committee_id')
                                    ->label('คณะกรรมการตรวจรับ')
                                    ->options(function () {
                                        return \App\Models\User::whereHas('roles', function ($query) {
                                            $query->where('name', 'inspection_committee');
                                        })->orderBy('name')->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->placeholder('เลือกคณะกรรมการตรวจรับ'),
                            ]),

                            Forms\Components\FileUpload::make('attachments_upload')
                                ->label('เอกสารแนบ')
                                ->multiple()
                                ->directory('tor-attachments')
                                ->maxSize(10240)
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'image/*',
                                    'application/msword',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                ])
                                ->columnSpanFull()
                                ->dehydrated(false),
                        ]),
                ])
                ->columnSpanFull()
                ->skippable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tor_number')
                    ->label('เลข TOR')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('ชื่อ TOR')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('แผนก')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('tor_type')
                    ->label('ประเภท')
                    ->formatStateUsing(fn ($state) => TermsOfReference::getTorTypeOptions()[$state] ?? $state)
                    ->colors([
                        'primary' => 'goods',
                        'info' => 'services',
                        'warning' => 'construction',
                        'success' => 'consulting',
                    ]),

                Tables\Columns\TextColumn::make('procurement_method')
                    ->label('วิธีจัดซื้อ')
                    ->formatStateUsing(fn ($state) => TermsOfReference::getProcurementMethodOptions()[$state] ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('สถานะ')
                    ->formatStateUsing(fn ($state) => TermsOfReference::getStatusOptions()[$state] ?? $state)
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'submitted',
                        'warning' => 'reviewing',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => fn ($state) => in_array($state, ['cancelled', 'expired', 'amended']),
                    ]),

                Tables\Columns\TextColumn::make('budget_estimate')
                    ->label('งบประมาณ')
                    ->money('THB')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('สำคัญ')
                    ->formatStateUsing(fn ($state) => TermsOfReference::getPriorityOptions()[$state] ?? $state)
                    ->colors([
                        'secondary' => 'low',
                        'primary' => 'medium',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ]),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('ผู้สร้าง')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('สร้างเมื่อ')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('สถานะ')
                    ->options(TermsOfReference::getStatusOptions()),

                Tables\Filters\SelectFilter::make('tor_type')
                    ->label('ประเภท')
                    ->options(TermsOfReference::getTorTypeOptions()),

                Tables\Filters\SelectFilter::make('procurement_method')
                    ->label('วิธีจัดซื้อ')
                    ->options(TermsOfReference::getProcurementMethodOptions()),

                Tables\Filters\SelectFilter::make('department')
                    ->label('แผนก')
                    ->relationship('department', 'name'),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('ลำดับสำคัญ')
                    ->options(TermsOfReference::getPriorityOptions()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->canBeEdited()),

                // Submit action
                Tables\Actions\Action::make('submit')
                    ->label('ส่งพิจารณา')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('ส่ง TOR เพื่อพิจารณา')
                    ->modalDescription('คุณต้องการส่ง TOR นี้เพื่อพิจารณาหรือไม่?')
                    ->visible(fn ($record) => $record->status === 'draft' && (
                        $record->created_by === Auth::id()
                        || Auth::user()?->hasAnyRole(['admin', 'procurement_manager'])
                    ))
                    ->action(function ($record) {
                        $record->submit(Auth::user());
                        \App\Events\TorSubmitted::dispatch($record, Auth::user());
                    }),

                // Approve action
                Tables\Actions\Action::make('approve')
                    ->label('อนุมัติ')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('อนุมัติ TOR')
                    ->form([
                        Forms\Components\Textarea::make('comments')
                            ->label('หมายเหตุ (ถ้ามี)')
                            ->rows(2),
                    ])
                    ->visible(fn ($record) => $record->canBeApproved() && Auth::user()?->hasAnyRole([
                        'admin', 'procurement_manager', 'department_head',
                    ]))
                    ->action(function ($record, array $data) {
                        $record->approve(Auth::user(), $data['comments'] ?? null);
                        \App\Events\TorApproved::dispatch($record, Auth::user());
                    }),

                // Reject action
                Tables\Actions\Action::make('reject')
                    ->label('ไม่อนุมัติ')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('ไม่อนุมัติ TOR')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('เหตุผลที่ไม่อนุมัติ')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn ($record) => $record->canBeApproved() && Auth::user()?->hasAnyRole([
                        'admin', 'procurement_manager', 'department_head',
                    ]))
                    ->action(function ($record, array $data) {
                        $record->reject(Auth::user(), $data['rejection_reason']);
                        \App\Events\TorRejected::dispatch($record, Auth::user(), $data['rejection_reason']);
                    }),

                // Create PR from TOR
                Tables\Actions\Action::make('create_pr')
                    ->label('สร้าง PR')
                    ->icon('heroicon-o-document-plus')
                    ->color('primary')
                    ->visible(fn ($record) => $record->status === 'approved')
                    ->url(fn ($record) => route('filament.admin.resources.purchase-requisitions.create', [
                        'tor_id' => $record->id,
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->hasRole('admin')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTermsOfReferences::route('/'),
            'create' => Pages\CreateTermsOfReference::route('/create'),
            'view' => Pages\ViewTermsOfReference::route('/{record}'),
            'edit' => Pages\EditTermsOfReference::route('/{record}/edit'),
        ];
    }
}
