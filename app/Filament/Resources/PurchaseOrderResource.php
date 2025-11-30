<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Filament\Actions\ApprovePurchaseOrderAction;
use App\Filament\Actions\RejectPurchaseOrderAction;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Purchase Orders (ใบสั่งซื้อ)';
    protected static ?string $modelLabel = 'ใบสั่งซื้อ';
    protected static ?string $pluralModelLabel = 'ใบสั่งซื้อ';
    protected static ?string $navigationGroup = 'Procurement Management';
    protected static ?int $navigationSort = 6;
    
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (!$user) return null;

        $query = static::getModel()::where('status', 'pending_approval');

        // Filter based on user role
        if (!$user->hasRole('admin') && !$user->hasRole('procurement_manager')) {
            if ($user->hasRole('department_head') && $user->department_id) {
                $query->where('department_id', $user->department_id);
            } else {
                return null; // No access to pending approvals
            }
        }

        $count = $query->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
    
    public static function getNavigationItems(): array
    {
        return parent::getNavigationItems();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ข้อมูลหลักของ PO
                Forms\Components\Section::make('ข้อมูลหลักของ PO')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('po_number')
                                ->label('เลขที่ PO')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(fn () => 'Auto-generated')
                                ->visibleOn('create'),

                            Forms\Components\TextInput::make('sap_po_number')
                                ->label('PO จาก SAP')
                                ->maxLength(255)
                                ->placeholder('กรอกเลขที่ PO จากระบบ SAP (ถ้ามี)')
                                ->helperText('สำหรับเลข PO ที่มาจากภายนอก/SAP'),
                        ]),

                        Forms\Components\Select::make('purchase_requisition_id')
                            ->label('เลือก PR (ถ้ามี)')
                            ->relationship(
                                name: 'purchaseRequisition',
                                titleAttribute: 'pr_number',
                                modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query) =>
                                    $query->when(
                                        session('company_id'),
                                        fn ($q, $companyId) => $q->where('company_id', $companyId)
                                    )
                                    ->whereIn('status', ['approved'])
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $pr = PurchaseRequisition::with('items')->find($state);
                                    if ($pr) {
                                        // Auto-fill PO title from PR
                                        $set('po_title', $pr->title ?? $pr->description);

                                        // Auto-fill items from PR
                                        $prItems = $pr->items->map(function ($item) {
                                            return [
                                                'item_code' => $item->item_code,
                                                'description' => $item->description,
                                                'quantity' => $item->quantity,
                                                'unit_of_measure' => $item->unit_of_measure,
                                                'unit_price' => $item->estimated_unit_price,
                                                'line_total' => $item->estimated_amount,
                                                'status' => 'ordered',
                                                'line_number' => $item->line_number ?? 1,
                                            ];
                                        })->toArray();

                                        $set('items', $prItems);

                                        // Trigger total calculation after items are loaded
                                        static::updatePOTotals($get, $set);
                                    }
                                }
                            })
                            ->helperText('เลือก PR ที่ได้รับอนุมัติแล้ว เพื่อดึงรายการสินค้ามาอัตโนมัติ')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('po_title')
                            ->label('ชื่องาน')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('work_type')
                                ->label('ประเภทของงาน')
                                ->required()
                                ->options([
                                    'buy' => 'ซื้อ',
                                    'hire' => 'จ้าง',
                                    'rent' => 'เช่า',
                                ]),

                            Forms\Components\Select::make('form_category')
                                ->label('แบบฟอร์ม')
                                ->options([
                                    'act_based' => 'แบบฟอร์มตาม พรบ',
                                    'law_based' => 'แบบฟอร์มตามกฎหมาย',
                                ])
                                ->searchable()
                                ->placeholder('เลือกประเภทแบบฟอร์ม'),
                        ]),

                        Forms\Components\Select::make('procurement_method')
                            ->label('วิธีการจัดหา')
                            ->options([
                                'agreement_price' => 'ตกลงราคา',
                                'invitation_bid' => 'เชิญชวน',
                                'open_bid' => 'ประกวดราคาอิเล็กทรอนิกส์ (e-bidding)',
                                'special_1' => 'วิธีพิเศษ (กรณีที่ 1)',
                                'special_2' => 'วิธีพิเศษ (กรณีที่ 2)',
                                'selection' => 'คัดเลือก',
                            ])
                            ->columnSpanFull(),
                    ]),

                // ข้อมูลบริษัทและผู้ติดต่อ  
                Forms\Components\Section::make('ข้อมูลบริษัทและผู้ติดต่อ')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('vendor_id')
                                ->label('เลือกผู้ขาย')
                                ->relationship(
                                    name: 'vendor',
                                    titleAttribute: 'company_name',
                                    modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query) => 
                                        $query->when(
                                            session('company_id'),
                                            fn ($q, $companyId) => $q->where('company_id', $companyId)
                                        )
                                )
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state) {
                                        $vendor = \App\Models\Vendor::find($state);
                                        if ($vendor) {
                                            $set('contact_name', $vendor->contact_name);
                                            $set('contact_email', $vendor->contact_email);
                                        }
                                    }
                                }),

                            Forms\Components\TextInput::make('contact_name')
                                ->label('ชื่อผู้ติดต่อ')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('contact_email')
                                ->label('E-mail')
                                ->required()
                                ->email()
                                ->maxLength(255),
                        ]),

                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\DatePicker::make('order_date')
                                ->label('วันที่สั่งซื้อ')
                                ->required()
                                ->default(now()),

                            Forms\Components\DatePicker::make('expected_delivery_date')
                                ->label('วันที่คาดว่าจะได้รับสินค้า')
                                ->minDate(now()),

                            Forms\Components\Select::make('department_id')
                                ->label('แผนก')
                                ->relationship('department', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                        ]),

                        Forms\Components\TextInput::make('delivery_address')
                            ->label('ที่อยู่จัดส่ง')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),

                // ข้อมูลทางการเงิน
                Forms\Components\Section::make('ข้อมูลทางการเงิน')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('currency')
                                ->label('สกุลเงิน')
                                ->default('THB')
                                ->options([
                                    'THB' => 'บาท (THB)',
                                    'USD' => 'ดอลลาร์ (USD)',
                                    'EUR' => 'ยูโร (EUR)',
                                ]),

                            Forms\Components\TextInput::make('exchange_rate')
                                ->label('อัตราแลกเปลี่ยน')
                                ->numeric()
                                ->default(1.0000)
                                ->step(0.0001)
                                ->minValue(0.0001),
                        ]),

                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('items_total')
                                ->label('ยอดรวมจากรายการสินค้า')
                                ->numeric()
                                ->prefix('฿')
                                ->default(0.00)
                                ->readonly()
                                ->dehydrated(false)
                                ->helperText('คำนวณอัตโนมัติจากรายการสินค้า'),

                            Forms\Components\TextInput::make('discount_amount')
                                ->label('ส่วนลด/ปรับราคา')
                                ->numeric()
                                ->prefix('฿')
                                ->default(0.00)
                                ->step(0.01)
                                ->minValue(0)
                                ->live(onBlur: true)
                                ->helperText('กรอกจำนวนส่วนลดที่ได้จากการต่อรอง')
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    static::updatePOTotals($get, $set);
                                }),

                            Forms\Components\TextInput::make('subtotal')
                                ->label('ยอดสุทธิ (ไม่รวม VAT)')
                                ->numeric()
                                ->prefix('฿')
                                ->default(0.00)
                                ->readonly()
                                ->dehydrated(true)
                                ->helperText('= ยอดรวมสินค้า - ส่วนลด'),
                        ]),

                        Forms\Components\Textarea::make('discount_reason')
                            ->label('เหตุผลการลดราคา/ปรับราคา')
                            ->rows(2)
                            ->placeholder('เช่น ต่อรองราคาได้, โปรโมชั่นพิเศษ, ส่วนลดปริมาณ')
                            ->visible(fn (Forms\Get $get) => (float) ($get('discount_amount') ?? 0) > 0)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('tax_amount')
                                ->label('VAT 7%')
                                ->numeric()
                                ->prefix('฿')
                                ->default(0.00)
                                ->readonly()
                                ->dehydrated(true),

                            Forms\Components\TextInput::make('total_amount')
                                ->label('ยอดรวมทั้งสิ้น')
                                ->numeric()
                                ->prefix('฿')
                                ->default(0.00)
                                ->readonly()
                                ->dehydrated(true),
                        ]),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('recalculate_from_items')
                                ->label('คำนวณใหม่จากรายการสินค้า')
                                ->icon('heroicon-o-calculator')
                                ->color('info')
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    static::updatePOTotals($get, $set);
                                })
                                ->visible(fn (Forms\Get $get) => count($get('items') ?? []) > 0),
                        ]),
                    ]),

                // แนบไฟล์เอกสาร
                Forms\Components\Section::make('แนบไฟล์เอกสาร')
                    ->schema([
                        Forms\Components\Repeater::make('files')
                            ->relationship()
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\FileUpload::make('file_path')
                                        ->label('ไฟล์')
                                        ->required()
                                        ->disk('public')
                                        ->directory('po-files')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                        ->maxSize(10240)
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            if ($state) {
                                                $fileName = pathinfo($state, PATHINFO_FILENAME);
                                                $extension = pathinfo($state, PATHINFO_EXTENSION);
                                                $set('original_name', basename($state));
                                                $set('file_name', $fileName);
                                                $set('uploaded_by', \Illuminate\Support\Facades\Auth::id());
                                                
                                                // Set default values to avoid database errors
                                                $set('file_type', 'application/octet-stream'); // Default mime type
                                                $set('file_size', 0); // Default size, will be updated later
                                            }
                                        }),
                                        
                                    Forms\Components\TextInput::make('original_name')
                                        ->label('ชื่อไฟล์')
                                        ->required()
                                        ->maxLength(255),
                                        
                                    Forms\Components\Hidden::make('file_name'),
                                    Forms\Components\Hidden::make('file_type'),
                                    Forms\Components\Hidden::make('file_size'),
                                    Forms\Components\Hidden::make('uploaded_by'),
                                ]),
                            ])
                            ->addActionLabel('เพิ่มไฟล์')
                            ->deleteAction(fn ($action) => $action->requiresConfirmation())
                            ->collapsible()
                            ->minItems(0),
                    ]),

                // รายการสินค้า
                Forms\Components\Section::make('รายการสินค้า')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Grid::make(4)->schema([
                                    Forms\Components\TextInput::make('item_code')
                                        ->label('รหัสสินค้า')
                                        ->maxLength(50),

                                    Forms\Components\Textarea::make('description')
                                        ->label('รายละเอียด')
                                        ->required()
                                        ->rows(2),

                                    Forms\Components\TextInput::make('quantity')
                                        ->label('จำนวน')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0.01)
                                        ->step(0.01)
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            $unitPrice = (float) ($get('unit_price') ?? 0);
                                            $quantity = (float) ($state ?? 0);
                                            $set('line_total', $quantity * $unitPrice);
                                            // Ensure status is set
                                            if (!$get('status')) {
                                                $set('status', 'ordered');
                                            }
                                            static::updatePOTotals($get, $set);
                                        }),
                                        
                                    Forms\Components\TextInput::make('unit_of_measure')
                                        ->label('หน่วย')
                                        ->required()
                                        ->maxLength(50),
                                ]),
                                
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('unit_price')
                                        ->label('ราคาต่อหน่วย')
                                        ->numeric()
                                        ->prefix('฿')
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            $quantity = (float) ($get('quantity') ?? 0);
                                            $unitPrice = (float) ($state ?? 0);
                                            $set('line_total', $quantity * $unitPrice);
                                            // Ensure status is set
                                            if (!$get('status')) {
                                                $set('status', 'ordered');
                                            }
                                            static::updatePOTotals($get, $set);
                                        }),

                                    Forms\Components\TextInput::make('line_total')
                                        ->label('รวม')
                                        ->numeric()
                                        ->prefix('฿')
                                        ->readonly(),
                                        
                                    Forms\Components\Select::make('status')
                                        ->label('สถานะ')
                                        ->options([
                                            'ordered' => 'สั่งซื้อแล้ว',
                                            'partially_received' => 'รับบางส่วน',
                                            'fully_received' => 'รับครบแล้ว',
                                            'cancelled' => 'ยกเลิก',
                                        ])
                                        ->default('ordered'),
                                ]),
                                
                                Forms\Components\Hidden::make('line_number')
                                    ->default(1),
                                Forms\Components\Hidden::make('status')
                                    ->default('ordered'),
                            ])
                            ->addActionLabel('เพิ่มรายการสินค้า')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->minItems(1)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                // Update line numbers and ensure status is set
                                if (is_array($state)) {
                                    foreach ($state as $index => $item) {
                                        $set("items.{$index}.line_number", (int)$index + 1);
                                        if (!isset($item['status']) || empty($item['status'])) {
                                            $set("items.{$index}.status", 'ordered');
                                        }
                                    }
                                }
                                static::updatePOTotals($get, $set);
                            }),
                    ]),

                // งวดการจ่ายเงิน (Payment Milestones)
                Forms\Components\Section::make('งวดการจ่ายเงิน (Payment Milestones)')
                    ->schema([
                        Forms\Components\Repeater::make('paymentMilestones')
                            ->relationship('paymentMilestones')
                            ->schema([
                                Forms\Components\Grid::make(4)->schema([
                                    Forms\Components\TextInput::make('milestone_number')
                                        ->label('งวดที่')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1)
                                        ->placeholder('เช่น 1, 2, 3'),

                                    Forms\Components\TextInput::make('milestone_title')
                                        ->label('ชื่องวด')
                                        ->placeholder('เช่น มัดจำ, งวดกลาง, งวดสุดท้าย'),

                                    Forms\Components\TextInput::make('percentage')
                                        ->label('เปอร์เซ็นต์')
                                        ->numeric()
                                        ->suffix('%')
                                        ->step(0.01)
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            if (is_numeric($state) && is_numeric($get('../../total_amount'))) {
                                                $totalAmount = (float) $get('../../total_amount');
                                                $percentage = (float) $state;
                                                $amount = ($totalAmount * $percentage) / 100;
                                                $set('amount', round($amount, 2));
                                            }
                                        }),

                                    Forms\Components\TextInput::make('amount')
                                        ->label('จำนวนเงิน')
                                        ->numeric()
                                        ->prefix('฿')
                                        ->step(0.01)
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            if (is_numeric($state) && is_numeric($get('../../total_amount'))) {
                                                $totalAmount = (float) $get('../../total_amount');
                                                $amount = (float) $state;
                                                if ($totalAmount > 0) {
                                                    $percentage = ($amount / $totalAmount) * 100;
                                                    $set('percentage', round($percentage, 2));
                                                }
                                            }
                                        }),
                                ]),

                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\DatePicker::make('due_date')
                                        ->label('วันครบกำหนด')
                                        ->native(false),

                                    Forms\Components\Select::make('status')
                                        ->label('สถานะ')
                                        ->options([
                                            'pending' => 'รอดำเนินการ',
                                            'due' => 'ถึงกำหนด',
                                            'paid' => 'จ่ายแล้ว',
                                            'overdue' => 'เลยกำหนด',
                                            'cancelled' => 'ยกเลิก',
                                        ])
                                        ->default('pending')
                                        ->required(),
                                ]),

                                Forms\Components\Textarea::make('payment_terms')
                                    ->label('เงื่อนไขการจ่าย')
                                    ->rows(2)
                                    ->placeholder('เช่น จ่ายหลังส่งมอบงวดที่ 1, จ่ายใน 30 วัน'),

                                Forms\Components\Hidden::make('company_id')
                                    ->default(session('selected_company_id')),

                                Forms\Components\Hidden::make('created_by')
                                    ->default(auth()->id()),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('เพิ่มงวดการจ่าย')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                !empty($state['milestone_name']) ? $state['milestone_name'] : 'งวดการจ่าย'
                            ),
                    ])
                    ->collapsed()
                    ->visible(fn (Forms\Get $get) => !empty($get('id')))
                    ->description('กำหนดงวดการจ่ายเงินสำหรับ PO นี้'),
            ]);
    }

    public static function updatePOTotals(Forms\Get $get, Forms\Set $set)
    {
        $items = $get('items') ?? [];

        // Calculate total from items (ยอดรวมจากรายการสินค้า)
        $itemsTotal = 0.0;
        foreach ($items as $item) {
            $itemTotal = (float) ($item['line_total'] ?? $item['total_price'] ?? 0);
            $itemsTotal += $itemTotal;
        }

        // Get discount amount (ส่วนลด/ปรับราคา)
        $discountAmount = (float) ($get('discount_amount') ?? 0);

        // Calculate subtotal (ยอดสุทธิ = ยอดรวมสินค้า - ส่วนลด)
        $subtotal = $itemsTotal - $discountAmount;

        // Ensure subtotal is not negative
        if ($subtotal < 0) {
            $subtotal = 0;
        }

        // Calculate 7% VAT
        $taxAmount = $subtotal * 0.07;

        // Calculate grand total (ยอดรวมทั้งสิ้น = ยอดสุทธิ + VAT)
        $totalAmount = $subtotal + $taxAmount;

        // Update form fields with proper rounding
        $set('items_total', round($itemsTotal, 2));
        $set('subtotal', round($subtotal, 2));
        $set('tax_amount', round($taxAmount, 2));
        $set('total_amount', round($totalAmount, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('po_title')
                    ->label('Title')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->po_title),

                Tables\Columns\TextColumn::make('vendor.company_name')
                    ->label('Vendor')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('form_category')
                    ->label('แบบฟอร์ม')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'act_based' => 'แบบฟอร์มตาม พรบ',
                        'law_based' => 'แบบฟอร์มตามกฎหมาย',
                        default => $state,
                    })
                    ->colors([
                        'info' => 'act_based',
                        'warning' => 'law_based',
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'pending_approval',
                        'success' => 'approved',
                        'warning' => ['sent_to_supplier', 'sent_to_vendor'],
                        'primary' => 'acknowledged',
                        'success' => ['fully_received', 'received'],
                        'warning' => 'partially_received',
                        'gray' => 'closed',
                        'danger' => ['rejected', 'cancelled'],
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Draft',
                        'pending_approval' => 'Pending Approval', 
                        'approved' => 'Approved',
                        'sent_to_supplier' => 'Sent to Supplier',
                        'sent_to_vendor' => 'Sent to Vendor',
                        'acknowledged' => 'Acknowledged',
                        'fully_received' => 'Fully Received',
                        'partially_received' => 'Partially Received',
                        'received' => 'Received',
                        'closed' => 'Closed',
                        'cancelled' => 'Cancelled',
                        'rejected' => 'Rejected',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('THB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('order_date')
                    ->label('Order Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->label('Expected Delivery')
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
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'delivered' => 'Delivered',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                    
                Tables\Filters\SelectFilter::make('vendor_id')
                    ->relationship(
                        name: 'vendor',
                        titleAttribute: 'company_name',
                        modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query) => 
                            $query->when(
                                session('company_id'),
                                fn ($q, $companyId) => $q->where('company_id', $companyId)
                            )
                    )
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('my_orders')
                    ->label('My Orders')
                    ->query(fn (Builder $query): Builder => $query->where('created_by', auth()->id())),
                    
                Tables\Filters\Filter::make('pending_my_approval')
                    ->label('Pending My Approval')
                    ->query(function (Builder $query): Builder {
                        $user = auth()->user();
                        return $query->where('status', 'pending_approval')
                            ->where(function ($query) use ($user) {
                                $query->where('po_approver_id', $user->id)
                                    ->orWhere(function ($query) use ($user) {
                                        if ($user->hasRole('admin') || $user->hasRole('procurement_manager')) {
                                            return $query;
                                        }
                                        if ($user->hasRole('department_head') && $user->department_id) {
                                            return $query->where('department_id', $user->department_id);
                                        }
                                        return $query->whereNull('id');
                                    });
                            });
                    })
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin', 'procurement_manager', 'department_head'])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                // PO Approval Actions
                new ApprovePurchaseOrderAction('approve'),
                new RejectPurchaseOrderAction('reject'),

                // Enhanced PO Workflow Actions
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn ($record) => $record->status === 'draft' && $record->created_by === Auth::id())
                    ->requiresConfirmation()
                    ->modalHeading('Submit PO for Approval')
                    ->modalDescription('Are you sure you want to submit this Purchase Order for approval?')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'pending_approval',
                            'submitted_at' => now(),
                        ]);

                        Notification::make()
                            ->title('PO Submitted')
                            ->body('Purchase order has been submitted for approval')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
            'pending-approvals' => Pages\PendingPurchaseOrders::route('/pending-approvals'),
        ];
    }
}