# Purchase Order PDF Auto-Fill Fields

## สรุปการปรับปรุง PDF Template
วันที่: 2025-10-17

## ปัญหา
- PDF template มีหลายช่องที่เป็น `...` (จุดไข่ปลา)
- ต้องการให้ระบบ auto-fill จากข้อมูลใน PO ที่สร้างไว้

## Field ที่มีใน Database แล้ว (สามารถใช้ได้เลย)

### จาก `purchase_orders` table:
1. **delivery_address** - สถานที่จัดส่ง
2. **expected_delivery_date** - วันที่คาดว่าจะส่งมอบ
3. **payment_terms** - เงื่อนไขการชำระเงิน
4. **delivery_schedule** - ตารางการส่งมอบ
5. **payment_schedule** - ตารางการชำระเงิน
6. **operation_duration** - ระยะเวลาดำเนินการ
7. **order_date** - วันที่สั่งซื้อ
8. **delivery_address** - ที่อยู่จัดส่ง

## Field ที่ยังไม่มีใน Database (ต้องเพิ่ม)

### 1. ระยะเวลาและวันที่
- `start_date` (date) - วันเริ่มต้นสัญญา
- `end_date` (date) - วันสิ้นสุดสัญญา
- `delivery_period` (string) - ระยะเวลาส่งมอบ เช่น "30 วัน", "60 วัน"

### 2. งวดการส่งมอบ
- `total_phases` (integer) - จำนวนงวดทั้งหมด
- `delivery_phases` (text) - รายละเอียดงวดการส่งมอบ เช่น "ตามข้อ1"

### 3. สถานที่และผู้ติดต่อ
- `delivery_location` (text) - สถานที่จัดส่งแบบละเอียด
- `contact_person` (string) - ชื่อผู้ติดต่อ
- `contact_phone` (string) - เบอร์โทรศัพท์ผู้ติดต่อ

### 4. การรับประกัน
- `warranty_days` (integer) - จำนวนวันรับประกัน (default: 7)
- `extended_warranty_days` (integer) - จำนวนวันรับประกันเพิ่มเติม

### 5. การชำระเงิน
- `payment_advance_percent` (decimal) - % ชำระล่วงหน้าหลังลงนาม
- `payment_completion_percent` (decimal) - % ชำระหลังส่งมอบงาน
- `payment_other` (string) - การชำระเงินแบบอื่นๆ

## แนวทางการแก้ไข

### ขั้นตอนที่ 1: สร้าง Migration
```bash
php artisan make:migration add_sow_fields_to_purchase_orders_table
```

### ขั้นตอนที่ 2: เพิ่ม Fields ใน Migration
```php
Schema::table('purchase_orders', function (Blueprint $table) {
    // ระยะเวลา
    $table->date('start_date')->nullable()->after('expected_delivery_date');
    $table->date('end_date')->nullable()->after('start_date');
    $table->string('delivery_period')->nullable()->after('end_date'); // เช่น "30 วัน"

    // งวดการส่งมอบ
    $table->integer('total_phases')->nullable()->after('delivery_period');
    $table->text('delivery_phases')->nullable()->after('total_phases');

    // สถานที่และผู้ติดต่อ
    $table->text('delivery_location')->nullable()->after('delivery_address');
    $table->string('contact_person')->nullable()->after('delivery_location');
    $table->string('contact_phone')->nullable()->after('contact_person');

    // การรับประกัน
    $table->integer('warranty_days')->default(7)->after('contact_phone');
    $table->integer('extended_warranty_days')->nullable()->after('warranty_days');

    // การชำระเงิน
    $table->decimal('payment_advance_percent', 5, 2)->nullable()->after('payment_schedule');
    $table->decimal('payment_completion_percent', 5, 2)->nullable()->after('payment_advance_percent');
    $table->string('payment_other')->nullable()->after('payment_completion_percent');
});
```

### ขั้นตอนที่ 3: เพิ่มใน Model
```php
// app/Models/PurchaseOrder.php
protected $fillable = [
    // ... existing fields
    'start_date',
    'end_date',
    'delivery_period',
    'total_phases',
    'delivery_phases',
    'delivery_location',
    'contact_person',
    'contact_phone',
    'warranty_days',
    'extended_warranty_days',
    'payment_advance_percent',
    'payment_completion_percent',
    'payment_other',
];

protected $casts = [
    // ... existing casts
    'start_date' => 'date',
    'end_date' => 'date',
    'warranty_days' => 'integer',
    'extended_warranty_days' => 'integer',
    'payment_advance_percent' => 'decimal:2',
    'payment_completion_percent' => 'decimal:2',
];
```

### ขั้นตอนที่ 4: เพิ่มใน Form (PurchaseOrderResource.php)
เพิ่ม fields ในส่วนที่เหมาะสม:
- ส่วนของ Delivery Information
- ส่วนของ Payment Terms
- ส่วนของ Warranty

### ขั้นตอนที่ 5: แก้ไข PDF Template
แทนที่ `...` ด้วย field จริง:
```blade
{{ $purchaseOrder->start_date ?? '........................' }}
{{ $purchaseOrder->end_date ?? '........................' }}
{{ $purchaseOrder->delivery_period ?? '........................' }}
{{ $purchaseOrder->total_phases ?? '........................' }}
{{ $purchaseOrder->contact_person ?? '........................' }}
{{ $purchaseOrder->contact_phone ?? '........................' }}
{{ $purchaseOrder->warranty_days ?? '7' }}
```

## ตำแหน่งใน PDF Template ที่ต้องแก้

### resources/views/pdf/purchase-orders/purchase.blade.php

1. **หัวข้อ 2.1 ระยะเวลาดำเนินการ** (บรรทัด ~268-271)
   - แทนที่ `start_date`, `end_date`, `delivery_period`

2. **หัวข้อ 2.2 รายละเอียดการส่งมอบงาน** (บรรทัด ~276-278)
   - แทนที่ `total_phases`, `delivery_phases`

3. **หัวข้อ 2.4 สถานที่จัดส่ง** (บรรทัด ~286-290)
   - แทนที่ `delivery_location`, `contact_person`, `contact_phone`

4. **หัวข้อ 4.4 การรับประกันสินค้า** (บรรทัด ~382)
   - แทนที่ `warranty_days`

5. **หัวข้อ 4.5 การรับประกันเพิ่มเติม** (บรรทัด ~386)
   - แทนที่ `extended_warranty_days`

6. **หัวข้อ 5 การชำระเงิน** (บรรทัด ~364, 371, 378)
   - แทนที่ `payment_advance_percent`, `payment_completion_percent`, `payment_other`

## ประโยชน์
1. ลดการกรอกข้อมูลซ้ำ
2. ป้องกันข้อผิดพลาดจากการพิมพ์
3. PDF มีข้อมูลครบถ้วนอัตโนมัติ
4. ง่ายต่อการตรวจสอบและแก้ไข

## หมายเหตุ
- สามารถเพิ่ม validation ใน Form เพื่อให้แน่ใจว่าข้อมูลถูกต้อง
- ควรเพิ่ม default values ที่เหมาะสม เช่น warranty_days = 7
- อาจต้องเพิ่ม helper text ใน form เพื่ออธิบายการกรอกข้อมูล
