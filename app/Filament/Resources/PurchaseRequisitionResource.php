<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseRequisitionResource\Pages;
use App\Models\PurchaseRequisition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseRequisitionResource extends Resource
{
    protected static ?string $model = PurchaseRequisition::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Purchase Requisitions (PR)';

    protected static ?string $navigationGroup = 'Procurement Management';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'requester', 'department_head', 'procurement_officer', 'procurement_manager', 'procurement_committee', 'auditor']) ?? false;
    }

    // ข้อ 20: pending-approval count shown as a badge on this menu
    // (replaces the standalone "PR รออนุมัติ" navigation item).
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (! $user || ! $user->hasAnyRole(['admin', 'department_head', 'procurement_officer', 'procurement_manager', 'auditor'])) {
            return null;
        }

        $companyId = session('company_id');
        $query = static::getModel()::where('status', 'pending_approval')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        // Department heads (without procurement roles) only see their own department
        if (! $user->hasAnyRole(['admin', 'procurement_manager', 'procurement_officer', 'auditor'])
            && $user->hasRole('department_head') && $user->department_id) {
            $query->where('department_id', $user->department_id);
        }

        $count = $query->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'PR รออนุมัติ';
    }

    public static function getEloquentQuery(): Builder
    {
        $companyId = session('company_id');

        return parent::getEloquentQuery()->when($companyId, fn ($query) => $query->where('company_id', $companyId));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Purchase Requisition Information')
                    ->description('Basic PR information and details')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('pr_number')
                                ->label('PR Number')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(fn () => 'Auto-generated')
                                ->visibleOn('create'),

                            Forms\Components\TextInput::make('pr_number')
                                ->label('PR Number')
                                ->readonly()
                                ->visibleOn(['edit', 'view']),

                            Forms\Components\TextInput::make('title')
                                ->label('Title')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Brief description of the request'),
                        ]),

                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\Select::make('category')
                                ->label('Category')
                                ->required()
                                ->options(PurchaseRequisition::getCategoryOptions())
                                ->searchable(),

                            Forms\Components\Select::make('form_category')
                                ->label('แบบฟอร์ม')
                                ->options(function () {
                                    if (session('company_id') == 2) {
                                        return ['law_based' => 'แบบฟอร์มเชิงพาณิชย์'];
                                    }

                                    return PurchaseRequisition::getFormCategoryOptions();
                                })
                                ->default(fn () => session('company_id') == 2 ? 'law_based' : null)
                                ->disabled(fn () => session('company_id') == 2)
                                ->dehydrated(true)
                                ->searchable()
                                ->placeholder('เลือกประเภทแบบฟอร์ม'),

                            Forms\Components\Select::make('work_type')
                                ->label('Work Type')
                                ->required()
                                ->options(PurchaseRequisition::getWorkTypeOptions())
                                ->searchable(),

                            Forms\Components\Select::make('procurement_method')
                                ->label('Procurement Method')
                                ->options(PurchaseRequisition::getProcurementMethodOptions())
                                ->searchable(),
                        ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Detailed description of the requirement...')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('priority')
                                ->label('Priority')
                                ->required()
                                ->default('medium')
                                ->options([
                                    'low' => 'Low (ต่ำ)',
                                    'medium' => 'Medium (ปานกลาง)',
                                    'high' => 'High (สูง)',
                                    'urgent' => 'Urgent (เร่งด่วน)',
                                ]),

                            Forms\Components\DatePicker::make('request_date')
                                ->label('Request Date')
                                ->default(now())
                                ->readonly()
                                ->visibleOn(['edit', 'view']),

                            Forms\Components\DatePicker::make('required_date')
                                ->label('Required Date')
                                ->required()
                                ->minDate(now()->addDay())
                                ->helperText('When do you need this?'),
                        ]),
                    ]),

                Forms\Components\Section::make('Departmental Information')
                    ->description('Department and requester details')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('department_id')
                                ->label('Department')
                                ->relationship('department', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live() // Make it reactive
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    // Clear requester when department changes
                                    $set('requester_id', null);
                                }),

                            Forms\Components\Select::make('requester_id')
                                ->label('Requester')
                                ->options(function (Forms\Get $get) {
                                    $departmentId = $get('department_id');
                                    if (! $departmentId) {
                                        return [];
                                    }

                                    return \App\Models\User::where('department_id', $departmentId)
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                })
                                ->required()
                                ->searchable()
                                ->placeholder('Select department first')
                                ->disabled(fn (Forms\Get $get): bool => ! $get('department_id'))
                                ->helperText(fn (Forms\Get $get): string => ! $get('department_id') ? 'Please select a department first' : 'Select the person who requested this purchase'
                                ),
                        ]),

                        Forms\Components\TextInput::make('purpose')
                            ->label('Purpose/Justification')
                            ->maxLength(255)
                            ->placeholder('Why is this needed?'),
                    ]),

                Forms\Components\Section::make('Budget & Project Information')
                    ->description('Budget allocation and project details')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('procurement_budget')
                                ->label('Estimated Budget')
                                ->numeric()
                                ->prefix('฿')
                                ->step(0.01)
                                ->placeholder('0.00'),

                            Forms\Components\Select::make('currency')
                                ->label('Currency')
                                ->default('THB')
                                ->searchable()
                                ->options([
                                    'THB' => 'THB - บาท',
                                    'USD' => 'USD - ดอลลาร์สหรัฐ',
                                    'EUR' => 'EUR - ยูโร',
                                    'GBP' => 'GBP - ปอนด์สเตอร์ลิง',
                                    'JPY' => 'JPY - เยนญี่ปุ่น',
                                    'CNY' => 'CNY - หยวนจีน',
                                    'KRW' => 'KRW - วอนเกาหลี',
                                    'SGD' => 'SGD - ดอลลาร์สิงคโปร์',
                                    'MYR' => 'MYR - ริงกิตมาเลเซีย',
                                    'IDR' => 'IDR - รูเปียห์อินโดนีเซีย',
                                    'VND' => 'VND - ดองเวียดนาม',
                                    'PHP' => 'PHP - เปโซฟิลิปปินส์',
                                    'MMK' => 'MMK - จัตพม่า',
                                    'LAK' => 'LAK - กีบลาว',
                                    'KHR' => 'KHR - เรียลกัมพูชา',
                                    'BND' => 'BND - ดอลลาร์บรูไน',
                                    'INR' => 'INR - รูปีอินเดีย',
                                    'TWD' => 'TWD - ดอลลาร์ไต้หวัน',
                                    'HKD' => 'HKD - ดอลลาร์ฮ่องกง',
                                    'AUD' => 'AUD - ดอลลาร์ออสเตรเลีย',
                                    'NZD' => 'NZD - ดอลลาร์นิวซีแลนด์',
                                    'CAD' => 'CAD - ดอลลาร์แคนาดา',
                                    'CHF' => 'CHF - ฟรังก์สวิส',
                                    'SEK' => 'SEK - โครนาสวีเดน',
                                    'NOK' => 'NOK - โครนนอร์เวย์',
                                    'DKK' => 'DKK - โครนเดนมาร์ก',
                                    'RUB' => 'RUB - รูเบิลรัสเซีย',
                                    'AED' => 'AED - เดอร์แฮมสหรัฐอาหรับเอมิเรตส์',
                                    'SAR' => 'SAR - ริยาลซาอุดีอาระเบีย',
                                    'QAR' => 'QAR - ริยาลกาตาร์',
                                    'KWD' => 'KWD - ดีนาร์คูเวต',
                                    'BHD' => 'BHD - ดีนาร์บาห์เรน',
                                    'OMR' => 'OMR - ริยาลโอมาน',
                                    'ZAR' => 'ZAR - แรนด์แอฟริกาใต้',
                                    'BRL' => 'BRL - เรอัลบราซิล',
                                    'MXN' => 'MXN - เปโซเม็กซิโก',
                                    'TRY' => 'TRY - ลีราตุรกี',
                                    'PLN' => 'PLN - ซลอตีโปแลนด์',
                                    'CZK' => 'CZK - โครูนาเช็ก',
                                    'HUF' => 'HUF - โฟรินต์ฮังการี',
                                    'ILS' => 'ILS - เชเกลอิสราเอล',
                                    'EGP' => 'EGP - ปอนด์อียิปต์',
                                    'NGN' => 'NGN - ไนราไนจีเรีย',
                                    'KES' => 'KES - ชิลลิงเคนยา',
                                    'PKR' => 'PKR - รูปีปากีสถาน',
                                    'BDT' => 'BDT - ตากาบังกลาเทศ',
                                    'LKR' => 'LKR - รูปีศรีลังกา',
                                    'NPR' => 'NPR - รูปีเนปาล',
                                ]),

                            Forms\Components\Toggle::make('is_budgeted')
                                ->label('Is Budgeted')
                                ->default(true)
                                ->helperText('Does this have budget allocation?'),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('budget_code')
                                ->label('Budget Code')
                                ->maxLength(255)
                                ->placeholder('e.g., BDG-2025-001'),

                            Forms\Components\TextInput::make('project_code')
                                ->label('Project Code')
                                ->maxLength(255)
                                ->placeholder('e.g., PRJ-2025-001'),
                        ]),
                    ]),

                Forms\Components\Section::make('Approval & Committee Assignment')
                    ->description('Approval workflow and committee assignments')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('procurement_committee_id')
                                ->label('Procurement Committee')
                                ->options(function () {
                                    return \App\Models\User::whereHas('roles', function ($query) {
                                        $query->where('name', 'procurement_committee');
                                    })->orderBy('name')->pluck('name', 'id');
                                })
                                ->searchable()
                                ->placeholder('Select Procurement Committee Member'),

                            Forms\Components\Select::make('inspection_committee_id')
                                ->label('Inspection Committee')
                                ->options(function () {
                                    return \App\Models\User::whereHas('roles', function ($query) {
                                        $query->where('name', 'inspection_committee');
                                    })->orderBy('name')->pluck('name', 'id');
                                })
                                ->searchable()
                                ->placeholder('Select Inspection Committee Member'),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('pr_approver_id')
                                ->label('PR Approver')
                                ->options(function () {
                                    return \App\Models\User::whereHas('roles', function ($query) {
                                        $query->whereIn('name', ['approver', 'admin', 'procurement_manager']);
                                    })->orderBy('name')->pluck('name', 'id');
                                })
                                ->searchable()
                                ->placeholder('Select PR Approver'),

                            Forms\Components\Select::make('other_stakeholder_id')
                                ->label('Other Stakeholder')
                                ->options(function () {
                                    return \App\Models\User::whereHas('roles', function ($query) {
                                        $query->where('name', 'other_stakeholder');
                                    })->orderBy('name')->pluck('name', 'id');
                                })
                                ->searchable()
                                ->placeholder('Select Other Stakeholder'),
                        ]),
                    ]),

                Forms\Components\Section::make('Delivery & Payment Schedule')
                    ->description('Timeline and payment information')
                    ->schema([
                        Forms\Components\Textarea::make('delivery_schedule')
                            ->label('Delivery Schedule')
                            ->rows(3)
                            ->placeholder('When and how should items be delivered?')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('payment_schedule')
                            ->label('Payment Schedule')
                            ->rows(3)
                            ->placeholder('Payment terms and schedule...')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Additional Notes')
                            ->rows(3)
                            ->placeholder('Any additional information...')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Purchase Requisition Items')
                    ->description('List of items requested')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Grid::make(6)->schema([
                                    Forms\Components\TextInput::make('description')
                                        ->label('Item Description')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('quantity')
                                        ->label('Quantity')
                                        ->required()
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $unitPrice = (float) $get('estimated_unit_price');
                                            $quantity = (float) $state;
                                            $set('estimated_amount', $unitPrice * $quantity);
                                        }),

                                    Forms\Components\TextInput::make('unit_of_measure')
                                        ->label('Unit')
                                        ->required()
                                        ->maxLength(50)
                                        ->placeholder('e.g., pcs, kg, box'),

                                    Forms\Components\TextInput::make('estimated_unit_price')
                                        ->label('Unit Price')
                                        ->required()
                                        ->numeric()
                                        ->prefix('฿')
                                        ->step(0.01)
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $unitPrice = (float) $state;
                                            $quantity = (float) $get('quantity');
                                            $set('estimated_amount', $unitPrice * $quantity);
                                        }),

                                    Forms\Components\TextInput::make('estimated_amount')
                                        ->label('Total Price')
                                        ->required()
                                        ->numeric()
                                        ->prefix('฿')
                                        ->disabled()
                                        ->dehydrated(),

                                    Forms\Components\Select::make('status')
                                        ->label('Item Status')
                                        ->options([
                                            'pending' => 'Pending',
                                            'ordered' => 'Ordered',
                                            'partially_ordered' => 'Partially Ordered',
                                            'received' => 'Received',
                                            'cancelled' => 'Cancelled',
                                        ])
                                        ->default('pending'),
                                ]),

                                Forms\Components\Textarea::make('specification')
                                    ->label('Technical Specification')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('remarks')
                                    ->label('Remarks')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? 'New Item')
                            ->addActionLabel('Add Item')
                            ->reorderableWithButtons()
                            ->cloneable()
                            ->minItems(1)
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                $totalAmount = collect($state ?? [])->sum('estimated_amount');
                                $set('total_amount', $totalAmount);
                            }),
                    ]),

                Forms\Components\Section::make('Status Information')
                    ->description('Current status and workflow information')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->default('draft')
                                ->options([
                                    'draft' => 'Draft',
                                    'submitted' => 'Submitted',
                                    'pending_approval' => 'Pending Approval',
                                    'approved' => 'Approved',
                                    'rejected' => 'Rejected',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->disabled(fn ($record) => ! $record || $record->status !== 'draft'),

                            Forms\Components\TextInput::make('total_amount')
                                ->label('Total Amount')
                                ->numeric()
                                ->prefix('฿')
                                ->default(0.00)
                                ->readonly()
                                ->helperText('Auto-calculated from items'),

                            Forms\Components\TextInput::make('current_approver_id')
                                ->label('Current Approver')
                                ->numeric()
                                ->disabled()
                                ->visibleOn(['edit', 'view']),
                        ]),

                        Forms\Components\Textarea::make('approval_comments')
                            ->label('Approval Comments')
                            ->rows(3)
                            ->readonly()
                            ->visibleOn(['edit', 'view'])
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->rows(3)
                            ->readonly()
                            ->visible(fn ($get) => in_array($get('status'), ['rejected']))
                            ->columnSpanFull(),
                    ])
                    ->visibleOn(['edit', 'view']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pr_number')
                    ->label('PR Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable(),

                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Requester')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('form_category')
                    ->label('แบบฟอร์ม')
                    ->formatStateUsing(fn ($state) => PurchaseRequisition::getFormCategoryOptions()[$state] ?? $state)
                    ->colors([
                        'info' => 'act_based',
                        'warning' => 'law_based',
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'submitted',
                        'warning' => 'pending_approval',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => 'cancelled',
                    ]),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priority')
                    ->colors([
                        'secondary' => 'low',
                        'primary' => 'medium',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ]),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('THB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('required_date')
                    ->label('Required Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),

                Tables\Filters\SelectFilter::make('department')
                    ->relationship('department', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseRequisitions::route('/'),
            'create' => Pages\CreatePurchaseRequisition::route('/create'),
            'view' => Pages\ViewPurchaseRequisition::route('/{record}'),
            'edit' => Pages\EditPurchaseRequisition::route('/{record}/edit'),
            'create-direct-small' => Pages\CreateDirectPurchaseSmall::route('/direct/small/create'),
            'create-direct-medium' => Pages\CreateDirectPurchaseMedium::route('/direct/medium/create'),
            'my-requests' => Pages\MyRequests::route('/my/requests'),
            'pending-approvals' => Pages\PendingApprovals::route('/pending/approvals'),
        ];
    }
}
