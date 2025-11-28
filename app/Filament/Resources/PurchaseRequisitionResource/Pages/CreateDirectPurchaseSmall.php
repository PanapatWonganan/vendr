<?php

namespace App\Filament\Resources\PurchaseRequisitionResource\Pages;

use App\Filament\Resources\PurchaseRequisitionResource;
use App\Models\PurchaseRequisition;
use App\Models\Department;
use App\Models\User;
use App\Models\Vendor;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms;

class CreateDirectPurchaseSmall extends CreateRecord
{
    protected static string $resource = PurchaseRequisitionResource::class;
    
    protected static ?string $title = 'สร้างใบขอซื้อตรง ≤ 10,000 บาท';
    protected static ?string $breadcrumb = 'จัดซื้อตรง ≤ 10,000';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        \Log::info('CreateDirectPurchaseSmall: Starting validation', $data);
        
        $data['pr_number'] = PurchaseRequisition::generatePRNumber();
        $data['company_id'] = session('company_id');
        $data['prepared_by_id'] = auth()->id();
        $data['created_by'] = auth()->id(); // Required field
        $data['pr_type'] = 'direct_small'; // ≤ 10,000
        // $data['requires_po'] = true; // Field removed from database
        $data['status'] = 'draft';
        $data['total_amount'] = 0; // Will be calculated from items
        
        // Strict validation for all items and totals
        $totalAmount = 0;
        if (isset($data['items'])) {
            foreach ($data['items'] as $index => &$item) {
                // Set defaults
                $item['unit_of_measure'] = $item['unit_of_measure'] ?? 'ชิ้น';
                $item['estimated_unit_price'] = floatval($item['estimated_unit_price'] ?? 0);
                $item['quantity'] = floatval($item['quantity'] ?? 0);
                
                // Recalculate amount to prevent manipulation
                $calculatedAmount = $item['quantity'] * $item['estimated_unit_price'];
                $item['estimated_amount'] = $calculatedAmount;
                
                // Log for debugging
                \Log::info('PR Item Calculation', [
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['estimated_unit_price'],
                    'calculated_amount' => $calculatedAmount
                ]);
                
                // Validate individual item amount - strictly NO MORE than 10,000
                if ($calculatedAmount > 10000.00) {
                    \Filament\Notifications\Notification::make()
                        ->title('ไม่สามารถสร้างใบขอซื้อได้')
                        ->body('รายการที่ ' . ($index + 1) . ' "' . ($item['description'] ?? 'ไม่ระบุ') . '" มียอดเงิน ' . number_format($calculatedAmount, 2) . ' บาท เกินวงเงิน 10,000 บาท')
                        ->danger()
                        ->persistent()
                        ->send();
                    
                    throw new \Illuminate\Validation\ValidationException(
                        validator: \Validator::make([], []),
                        response: back()->withErrors(['items' => 'รายการที่ ' . ($index + 1) . ' เกินวงเงิน 10,000 บาท'])
                    );
                }
                
                $totalAmount += $calculatedAmount;
            }
            
            // Strict total validation - NO MORE than 10,000
            if ($totalAmount > 10000.00) {
                \Filament\Notifications\Notification::make()
                    ->title('ไม่สามารถสร้างใบขอซื้อได้')
                    ->body('ยอดรวมทั้งหมด ' . number_format($totalAmount, 2) . ' บาท เกินวงเงิน 10,000 บาท สำหรับใบขอซื้อตรง')
                    ->danger()
                    ->persistent()
                    ->send();
                
                throw new \Illuminate\Validation\ValidationException(
                    validator: \Validator::make([], []),
                    response: back()->withErrors(['total' => 'ยอดรวมเกิน 10,000 บาท (' . number_format($totalAmount, 2) . ' บาท)'])
                );
            }
            
            // Warning if amount is too low
            if ($totalAmount < 100) {
                \Filament\Notifications\Notification::make()
                    ->title('ยอดเงินต่ำ')
                    ->body('ยอดรวม ' . number_format($totalAmount, 2) . ' บาท อาจต่ำเกินไป')
                    ->warning()
                    ->send();
            }
        }
        
        $data['total_amount'] = $totalAmount;
        
        return $data;
    }
    
    protected function beforeCreate(): void
    {
        // Calculate total from form data
        $data = $this->data;
        $totalAmount = 0;
        
        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                // Use the already calculated estimated_amount field
                $totalAmount += floatval($item['estimated_amount'] ?? 0);
            }
        }
        
        // Strictly enforce 10,000 limit (same as Innobic copy 2)
        if ($totalAmount > 10000) {
            \Filament\Notifications\Notification::make()
                ->title('ยอดรวมเกินวงเงิน')
                ->body('ยอดรวมต้องไม่เกิน 10,000 บาท (ยอดปัจจุบัน: ' . number_format($totalAmount, 2) . ' บาท)')
                ->danger()
                ->persistent()
                ->send();
            
            // Prevent form submission
            $this->halt();
        }
    }
    
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('ข้อมูลใบขอซื้อตรง (≤ 10,000 บาท)')
                ->description('กรอกข้อมูลใบขอซื้อตรงสำหรับวงเงินไม่เกิน 10,000 บาท')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('หัวข้อ/Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                            
                        Forms\Components\Select::make('department_id')
                            ->label('แผนก/Department')
                            ->relationship('department', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Clear requester when department changes
                                $set('requester_id', null);
                            }),
                            
                        Forms\Components\Select::make('requester_id')
                            ->label('ผู้ขอ/Requester')
                            ->options(function (Forms\Get $get) {
                                $departmentId = $get('department_id');
                                if (!$departmentId) {
                                    return [];
                                }
                                return \App\Models\User::where('department_id', $departmentId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->placeholder('เลือกแผนกก่อน/Select department first')
                            ->disabled(fn (Forms\Get $get): bool => !$get('department_id'))
                            ->helperText(fn (Forms\Get $get): string => 
                                !$get('department_id') ? 'กรุณาเลือกแผนกก่อน' : 'เลือกผู้ที่ขอซื้อสินค้า/บริการนี้'
                            ),
                            
                        Forms\Components\Select::make('supplier_vendor_id')
                            ->label('ผู้ขาย/Vendor')
                            ->relationship('supplierVendor', 'name')
                            ->options(Vendor::where('status', 'approved')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                    ]),
                    
                    Forms\Components\Textarea::make('description')
                        ->label('รายละเอียด/Description')
                        ->rows(3)
                        ->columnSpanFull(),
                        
                    Forms\Components\TextInput::make('clause_number')
                        ->label('หมวด/มาตรา')
                        ->maxLength(100),
                        
                    Forms\Components\TextInput::make('io_number')
                        ->label('IO Number')
                        ->maxLength(100),
                        
                    Forms\Components\TextInput::make('cost_center')
                        ->label('Cost Center')
                        ->maxLength(100),
                ]),
                
            Forms\Components\Section::make('รายการสินค้า/บริการ')
                ->description('เพิ่มรายการสินค้าหรือบริการที่ต้องการจัดซื้อ (วงเงินไม่เกิน 10,000 บาท)')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Grid::make(4)->schema([
                                Forms\Components\TextInput::make('description')
                                    ->label('รายการ/Description')
                                    ->required()
                                    ->columnSpan(2),
                                    
                                Forms\Components\TextInput::make('quantity')
                                    ->label('จำนวน')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get, $component) {
                                        $quantity = floatval($state ?? 1);
                                        $unitPrice = floatval($get('estimated_unit_price') ?? 0);
                                        $amount = $quantity * $unitPrice;
                                        
                                        // Check if amount exceeds limit
                                        if ($amount > 10000 && $unitPrice > 0) {
                                            // Calculate maximum allowed quantity
                                            $maxQuantity = floor(10000 / $unitPrice);
                                            
                                            // Set to maximum allowed quantity
                                            $set('quantity', $maxQuantity);
                                            $set('estimated_amount', $maxQuantity * $unitPrice);
                                            
                                            // Update component state
                                            $component->state($maxQuantity);
                                            
                                            \Filament\Notifications\Notification::make()
                                                ->title('จำนวนเกินวงเงิน!')
                                                ->body('จำนวนสูงสุดที่ใส่ได้คือ ' . $maxQuantity . ' หน่วย (สำหรับราคา ' . number_format($unitPrice, 2) . ' บาท/หน่วย)')
                                                ->danger()
                                                ->duration(5000)
                                                ->send();
                                        } else {
                                            $set('estimated_amount', $amount);
                                        }
                                    })
                                    ->rules([
                                        'required',
                                        'numeric',
                                        'min:1',
                                        function (Forms\Get $get) {
                                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                $unitPrice = floatval($get('estimated_unit_price') ?? 0);
                                                if ($unitPrice > 0) {
                                                    $amount = floatval($value) * $unitPrice;
                                                    if ($amount > 10000) {
                                                        $maxQuantity = floor(10000 / $unitPrice);
                                                        $fail("จำนวนสูงสุดที่ใส่ได้คือ {$maxQuantity} หน่วย");
                                                    }
                                                }
                                            };
                                        }
                                    ])
                                    ->validationAttribute('จำนวน'),
                                    
                                Forms\Components\TextInput::make('unit_of_measure')
                                    ->label('หน่วย')
                                    ->required()
                                    ->default('ชิ้น'),
                            ]),
                            
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('estimated_unit_price')
                                    ->label('ราคาต่อหน่วย')
                                    ->numeric()
                                    ->required()
                                    ->prefix('฿')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get, $component) {
                                        $quantity = floatval($get('quantity') ?? 1);
                                        $unitPrice = floatval($state ?? 0);
                                        $amount = $quantity * $unitPrice;
                                        
                                        // Check if amount exceeds limit
                                        if ($amount > 10000) {
                                            // Calculate maximum allowed price
                                            $maxPrice = floor(10000 / $quantity * 100) / 100; // Round down to 2 decimals
                                            
                                            // Set to maximum allowed price
                                            $set('estimated_unit_price', $maxPrice);
                                            $set('estimated_amount', $maxPrice * $quantity);
                                            
                                            // Add error state to the field
                                            $component->state($maxPrice);
                                            
                                            \Filament\Notifications\Notification::make()
                                                ->title('ราคาเกินวงเงิน!')
                                                ->body('ราคาต่อหน่วยสูงสุดที่ใส่ได้คือ ' . number_format($maxPrice, 2) . ' บาท (สำหรับจำนวน ' . $quantity . ' หน่วย)')
                                                ->danger()
                                                ->duration(5000)
                                                ->send();
                                        } else {
                                            $set('estimated_amount', $amount);
                                        }
                                    })
                                    ->rules([
                                        'required',
                                        'numeric', 
                                        'min:0.01',
                                        function (Forms\Get $get) {
                                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                $quantity = floatval($get('quantity') ?? 1);
                                                $amount = $quantity * floatval($value);
                                                if ($amount > 10000) {
                                                    $maxPrice = floor(10000 / $quantity * 100) / 100;
                                                    $fail("ราคาสูงสุดที่ใส่ได้คือ {$maxPrice} บาท");
                                                }
                                            };
                                        }
                                    ])
                                    ->validationAttribute('ราคาต่อหน่วย'),
                                    
                                Forms\Components\TextInput::make('estimated_amount')
                                    ->label('รวม')
                                    ->numeric()
                                    ->prefix('฿')
                                    ->disabled()
                                    ->dehydrated()
                                    ->rules(['max:10000'])
                                    ->helperText('ต้องไม่เกิน 10,000 บาท'),
                                    
                                Forms\Components\Textarea::make('remarks')
                                    ->label('หมายเหตุ')
                                    ->rows(2),
                            ]),
                        ])
                        ->columns(1)
                        ->reorderable()
                        ->addActionLabel('เพิ่มรายการ')
                        ->minItems(1)
                        ->required()
                        ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                            // Calculate total and validate
                            $items = $get('items') ?? [];
                            $total = 0;
                            
                            foreach ($items as $item) {
                                $total += $item['estimated_amount'] ?? 0;
                            }
                            
                            if ($total > 10000) {
                                \Filament\Notifications\Notification::make()
                                    ->title('เกินวงเงินรวม 10,000 บาท')
                                    ->body('ยอดรวมทั้งหมด: ' . number_format($total, 2) . ' บาท')
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        })
                        ->live(),
                ]),
        ];
    }
    
    protected function afterCreate(): void
    {
        // Calculate total amount from items
        $totalAmount = $this->record->items()->sum('estimated_amount');
        
        // Update total amount in the main record
        $this->record->update(['total_amount' => $totalAmount]);
        
        // Success notification with validation confirmation
        \Filament\Notifications\Notification::make()
            ->title('สร้างใบขอซื้อตรง ≤ 10,000 บาทสำเร็จ')
            ->body('ยอดรวม: ' . number_format($totalAmount, 2) . ' บาท')
            ->success()
            ->send();
    }
}
