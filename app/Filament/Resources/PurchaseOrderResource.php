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

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Purchase Orders (ใบสั่งซื้อ)';
    protected static ?string $modelLabel = 'ใบสั่งซื้อ';
    protected static ?string $pluralModelLabel = 'ใบสั่งซื้อ';
    protected static ?string $navigationGroup = 'Procurement Management';
    protected static ?int $navigationSort = 7;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'procurement_officer', 'procurement_manager', 'department_head', 'auditor']) ?? false;
    }

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
                            ->label('เลือก PR (ต้องผ่าน VA อนุมัติแล้ว)')
                            ->relationship(
                                name: 'purchaseRequisition',
                                titleAttribute: 'pr_number',
                                modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query) =>
                                    $query->when(
                                        session('company_id'),
                                        fn ($q, $companyId) => $q->where('company_id', $companyId)
                                    )
                                    ->whereIn('status', ['approved'])
                                    ->whereHas('valueAnalysis', fn ($va) => $va->where('status', 'approved'))
                            )
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                $va = $record->valueAnalysis;
                                $vaLabel = $va ? " [VA: {$va->va_number}]" : '';
                                return "{$record->pr_number} - {$record->title}{$vaLabel}";
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $pr = PurchaseRequisition::with(['items', 'valueAnalysis'])->find($state);
                                    if ($pr) {
                                        // Auto-fill PO title from PR
                                        $set('po_title', $pr->title ?? $pr->description);

                                        $va = $pr->valueAnalysis;

                                        // Copy items from PR (ราคาต่อ item จาก PR — ผู้ใช้แก้เองได้)
                                        $poItems = $pr->items->map(function ($item) {
                                            return [
                                                'item_code' => $item->item_code,
                                                'description' => $item->description,
                                                'quantity' => $item->quantity,
                                                'unit_of_measure' => $item->unit_of_measure,
                                                'unit_price' => $item->estimated_unit_price ?? 0,
                                                'line_total' => ($item->quantity ?? 0) * ($item->estimated_unit_price ?? 0),
                                                'status' => 'ordered',
                                                'line_number' => $item->line_number ?? 1,
                                            ];
                                        })->toArray();

                                        $set('items', $poItems);

                                        // ยอดรวมใช้จาก VA agreed_amount (ราคาที่ตกลงจริง)
                                        $vaAmount = ($va && $va->status === 'approved' && $va->agreed_amount > 0)
                                            ? (float) $va->agreed_amount
                                            : collect($poItems)->sum('line_total');

                                        $taxAmount = $vaAmount * 0.07;
                                        $totalAmount = $vaAmount + $taxAmount;

                                        $set('items_total', round($vaAmount, 2));
                                        $set('subtotal', round($vaAmount, 2));
                                        $set('tax_amount', round($taxAmount, 2));
                                        $set('total_amount', round($totalAmount, 2));
                                    }
                                }
                            })
                            ->helperText('แสดงเฉพาะ PR ที่ผ่าน Value Analysis อนุมัติแล้ว ราคาจะดึงจาก VA')
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
                                ->options(function () {
                                    if (session('company_id') == 2) {
                                        return ['law_based' => 'แบบฟอร์มเชิงพาณิชย์'];
                                    }
                                    return [
                                        'act_based' => 'แบบฟอร์มตาม พ.ร.บ.',
                                        'law_based' => 'แบบฟอร์มเชิงพาณิชย์',
                                    ];
                                })
                                ->default(fn () => session('company_id') == 2 ? 'law_based' : null)
                                ->disabled(fn () => session('company_id') == 2)
                                ->dehydrated(true)
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

                            Forms\Components\TextInput::make('exchange_rate')
                                ->label('อัตราแลกเปลี่ยน')
                                ->numeric()
                                ->default(1.0000)
                                ->step(0.0001)
                                ->minValue(0.0001),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('items_total')
                                ->label('ยอดจาก VA (ตกลงราคา)')
                                ->numeric()
                                ->prefix('฿')
                                ->default(0.00)
                                ->readonly()
                                ->dehydrated(false)
                                ->helperText('ดึงอัตโนมัติจาก Vendor Approve'),

                            Forms\Components\TextInput::make('subtotal')
                                ->label('ยอดสุทธิ (ไม่รวม VAT)')
                                ->numeric()
                                ->prefix('฿')
                                ->default(0.00)
                                ->readonly()
                                ->dehydrated(true)
                                ->helperText('เท่ากับยอดจาก VA'),
                        ]),

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

                // รายการสินค้า (ดึงจาก PR อัตโนมัติ — ดูอย่างเดียว)
                Forms\Components\Section::make('รายการสินค้า (จาก PR)')
                    ->description('รายการสินค้าดึงจากใบ PR อัตโนมัติ ยอดรวมใช้จาก VA')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->schema([
                                Forms\Components\Grid::make(4)->schema([
                                    Forms\Components\TextInput::make('item_code')
                                        ->label('รหัสสินค้า')
                                        ->readOnly(),

                                    Forms\Components\TextInput::make('description')
                                        ->label('รายละเอียด')
                                        ->readOnly(),

                                    Forms\Components\TextInput::make('quantity')
                                        ->label('จำนวน')
                                        ->numeric()
                                        ->readOnly(),

                                    Forms\Components\TextInput::make('unit_of_measure')
                                        ->label('หน่วย')
                                        ->readOnly(),
                                ]),

                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('unit_price')
                                        ->label('ราคาต่อหน่วย (ประมาณการ)')
                                        ->numeric()
                                        ->prefix('฿')
                                        ->readOnly(),

                                    Forms\Components\TextInput::make('line_total')
                                        ->label('รวม')
                                        ->numeric()
                                        ->prefix('฿')
                                        ->readOnly(),
                                ]),

                                Forms\Components\Hidden::make('line_number')
                                    ->default(1),
                                Forms\Components\Hidden::make('status')
                                    ->default('ordered'),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->minItems(0),
                    ])
                    ->collapsed()
                    ->collapsible(),

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
                                        ->required()
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
                                        ->required()
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
                                    ->default(fn () => session('company_id')),

                                Forms\Components\Hidden::make('created_by')
                                    ->default(auth()->id()),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('เพิ่มงวดการจ่าย')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                                !empty($state['milestone_title']) ? $state['milestone_title'] : 'งวดการจ่าย'
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
                    ->sortable()
                    ->description(fn ($record) => $record->amendment_label),

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
                        'act_based' => 'แบบฟอร์มตาม พ.ร.บ.',
                        'law_based' => 'แบบฟอร์มเชิงพาณิชย์',
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

                // Approve PO (pending_approval only)
                Tables\Actions\Action::make('approve')
                    ->label('อนุมัติ')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending_approval'
                        && auth()->user()?->hasAnyRole(['admin', 'procurement_manager', 'department_head']))
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => "อนุมัติ {$record->po_number}")
                    ->modalDescription('คุณต้องการอนุมัติใบสั่งซื้อนี้หรือไม่?')
                    ->modalSubmitActionLabel('ยืนยันอนุมัติ')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('approval_notes')
                            ->label('หมายเหตุ (ถ้ามี)')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $user = auth()->user();
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => $user->id,
                            'approved_at' => now(),
                            'approval_notes' => $data['approval_notes'] ?? null,
                        ]);

                        // If this is an amendment, finalize it
                        if ($record->amendment_number > 0) {
                            $pendingAmendment = $record->amendments()
                                ->where('status', 'pending')
                                ->latest()
                                ->first();

                            if ($pendingAmendment) {
                                app(\App\Services\AmendmentService::class)
                                    ->finalizeAmendment($pendingAmendment, $user->id);
                            }
                        }

                        event(new \App\Events\PurchaseOrderApproved($record, $user));

                        Notification::make()
                            ->title('อนุมัติเรียบร้อย')
                            ->body("อนุมัติ {$record->po_number} แล้ว" .
                                ($record->amendment_number > 0 ? " (แก้ไขครั้งที่ {$record->amendment_number})" : ''))
                            ->success()
                            ->send();
                    }),

                // Reject PO (pending_approval only)
                Tables\Actions\Action::make('reject')
                    ->label('ปฏิเสธ')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending_approval'
                        && auth()->user()?->hasAnyRole(['admin', 'procurement_manager', 'department_head']))
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => "ปฏิเสธ {$record->po_number}")
                    ->modalDescription('คุณต้องการปฏิเสธใบสั่งซื้อนี้หรือไม่?')
                    ->modalSubmitActionLabel('ยืนยันปฏิเสธ')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('rejection_notes')
                            ->label('เหตุผลการปฏิเสธ')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $user = auth()->user();
                        $record->update([
                            'status' => 'rejected',
                            'rejected_by' => $user->id,
                            'rejected_at' => now(),
                            'rejection_notes' => $data['rejection_notes'],
                        ]);

                        event(new \App\Events\PurchaseOrderRejected($record, $user));

                        Notification::make()
                            ->title('ปฏิเสธเรียบร้อย')
                            ->body("ปฏิเสธ {$record->po_number} แล้ว")
                            ->success()
                            ->send();
                    }),

                // Submit for Approval (draft only)
                Tables\Actions\Action::make('submitForApproval')
                    ->label('ส่งอนุมัติ')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn ($record) => $record->status === 'draft' && $record->created_by === Auth::id())
                    ->requiresConfirmation()
                    ->modalHeading('ส่ง PO เพื่ออนุมัติ')
                    ->modalDescription('คุณต้องการส่งใบสั่งซื้อนี้เพื่อขออนุมัติหรือไม่?')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'pending_approval',
                            'submitted_at' => now(),
                        ]);

                        Notification::make()
                            ->title('ส่งอนุมัติแล้ว')
                            ->body('ใบสั่งซื้อถูกส่งเพื่อขออนุมัติเรียบร้อย')
                            ->success()
                            ->send();
                    }),

                // Request Amendment (approved PO onwards)
                Tables\Actions\Action::make('requestAmendment')
                    ->label('ขอแก้ไข PO')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn ($record) =>
                        $record->canRequestAmendment() &&
                        auth()->user()?->hasAnyRole(['admin', 'procurement_officer', 'procurement_manager']))
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => "ขอแก้ไข {$record->po_number}")
                    ->modalDescription('ระบบจะสร้าง VA Revision เพื่อให้คุณแก้ไขรายละเอียดก่อนส่งอนุมัติ PO ใหม่')
                    ->form([
                        \Filament\Forms\Components\Select::make('amendment_type')
                            ->label('ประเภทการแก้ไข')
                            ->options(\App\Models\ValueAnalysis::AMENDMENT_TYPES)
                            ->required()
                            ->native(false),
                        \Filament\Forms\Components\Textarea::make('amendment_reason')
                            ->label('เหตุผลที่ต้องแก้ไข')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $service = app(\App\Services\AmendmentService::class);
                            $vaRevision = $service->createVaRevisionFromPo(
                                $record,
                                $data['amendment_type'],
                                $data['amendment_reason'],
                                auth()->id()
                            );

                            Notification::make()
                                ->title('สร้าง VA Revision สำเร็จ')
                                ->body('VA ' . $vaRevision->va_number . ' (Rev.' . $vaRevision->revision_number . ') ถูกสร้างแล้ว กรุณาแก้ไขและส่งอนุมัติ')
                                ->success()
                                ->send();

                            return redirect()->to(
                                \App\Filament\Resources\ValueAnalysisResource::getUrl('edit', ['record' => $vaRevision])
                            );
                        } catch (\RuntimeException $e) {
                            Notification::make()
                                ->title('ไม่สามารถสร้าง VA Revision ได้')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    // Bulk approve
                    Tables\Actions\BulkAction::make('bulkApprove')
                        ->label('อนุมัติที่เลือก')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('อนุมัติ PO ที่เลือก')
                        ->modalDescription('คุณต้องการอนุมัติใบสั่งซื้อที่เลือกทั้งหมดหรือไม่?')
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['admin', 'procurement_manager', 'department_head']))
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $user = Auth::user();
                            $approved = 0;

                            foreach ($records as $record) {
                                if ($record->status !== 'pending_approval') continue;

                                // Permission check
                                $canApprove = $user->hasAnyRole(['admin', 'procurement_manager'])
                                    || ($user->hasRole('department_head') && $user->department_id === $record->department_id);

                                if (!$canApprove) continue;

                                $record->update([
                                    'status' => 'approved',
                                    'approved_by' => $user->id,
                                    'approved_at' => now(),
                                ]);

                                event(new \App\Events\PurchaseOrderApproved($record, $user));
                                $approved++;
                            }

                            Notification::make()
                                ->title("อนุมัติแล้ว {$approved} รายการ")
                                ->success()
                                ->send();
                        }),
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