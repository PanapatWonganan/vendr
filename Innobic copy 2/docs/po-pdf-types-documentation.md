# เอกสาร PDF Types สำหรับระบบ Purchase Order

## สรุปภาพรวม
ระบบ Purchase Order มีการสร้าง PDF 3 ประเภท ตามประเภทงาน (work_type) ที่แตกต่างกัน

## ไฟล์ที่เกี่ยวข้อง

### 1. Service หลักสำหรับสร้าง PDF
- **Path:** `/app/Services/PurchaseOrderPdfService.php`
- **หน้าที่:** 
  - เลือก template ตาม work_type
  - เตรียมข้อมูลสำหรับ PDF
  - สร้างชื่อไฟล์ PDF
  - กำหนด config สำหรับ mPDF (รองรับภาษาไทย)

### 2. Template Files (Blade Views)
อยู่ที่: `/resources/views/pdf/purchase-orders/`

#### 2.1 Template สำหรับซื้อ/เช่า
- **File:** `purchase.blade.php`
- **ใช้สำหรับ:** work_type = 'buy' และ 'rent'
- **ชื่อไฟล์ PDF:** `PO_PURCHASE_[เลขที่PO]_[timestamp].pdf`

#### 2.2 Template สำหรับจ้าง (SOW Format)
- **File:** `hire-sow.blade.php`
- **ใช้สำหรับ:** work_type = 'hire'
- **ชื่อไฟล์ PDF:** `PO_HIRE_[เลขที่PO]_[timestamp].pdf`

#### 2.3 Template สำหรับจ้าง (แบบเก่า - ไม่ได้ใช้แล้ว)
- **File:** `hire.blade.php`
- **หมายเหตุ:** ปัจจุบันไม่ได้ใช้ ระบบใช้ hire-sow.blade.php แทน

## รายละเอียดแต่ละประเภท (ตามเอกสาร SOW อ้างอิง)

### 1. ประเภท "ซื้อ" (Buy)
```php
work_type = 'buy'
template = 'pdf.purchase-orders.purchase'
workTypeLabel = 'ซื้อ'
document_code = 'PCMN-002-FO'
```
**ลักษณะเอกสาร SOW:**
- **ขอบเขตการดำเนินงาน:** ผู้ขายจะต้องดำเนินการให้บริษัทฯ ได้ตามขอบการดำเนินงานที่กำหนด
- **การรับประกันสินค้า:** มีหัวข้อเฉพาะ (4. การรับประกันสินค้า)
  - สินค้าต้องมีคุณภาพไม่ต่ำกว่าที่กำหนด และเป็นของแท้ของใหม่
  - มีระยะเวลารับประกัน และการซ่อมแซมเปลี่ยนใหม่ภายใน 7 วัน
  - ยืดระยะเวลาประกันเมื่อมีการซ่อมแซม
- **อัตราค่าปรับ:** 0.2% ต่อวันของมูลค่าสินค้า
- **ผู้ดำเนินการ:** ผู้ขาย
- **เอกสารประกอบ:** COA, สบ.5, Artwork, Spec, Report, คู่มือการใช้งาน

### 2. ประเภท "เช่า" (Rent)
```php
work_type = 'rent'
template = 'pdf.purchase-orders.purchase'
workTypeLabel = 'เช่า'
document_code = 'PCMN-002-FO'
```
**ลักษณะเอกสาร SOW:**
- **ขอบเขตการดำเนินงาน:** ผู้ให้เช่าจะต้องดำเนินการให้เช่าอสังหาริมทรัพย์หรือที่ดิน พร้อมสิ่งปลูกสร้าง ระบุขนาดพื้นที่ไม่ต่ำกว่า ___ ตารางเมตร
- **เน้นรายละเอียด:** ขนาดพื้นที่, ประเภทอาคารพาณิชย์
- **อัตราค่าปรับ:** 0.2% ของมูลค่าการเช่ารวมทั้งสัญญา/มูลค่าสินค้าที่จะเช่า/มูลค่าการเช่าในแต่ละงวด
- **ผู้ดำเนินการ:** ผู้ให้เช่า
- **ไม่มีหัวข้อ:** การรับประกันสินค้า (เพราะเป็นการเช่า)

### 3. ประเภท "จ้าง" (Hire)
```php
work_type = 'hire'
template = 'pdf.purchase-orders.hire-sow'
workTypeLabel = 'จ้าง'
document_code = 'PCM-002'
```
**ลักษณะเอกสาร SOW:**
- **ขอบเขตการดำเนินงาน:** ผู้รับจ้าง/ผู้ให้บริการจะต้องดำเนินการให้บริษัทฯ ได้ตามขอบการดำเนินงานที่กำหนด (รายละเอียดตามเอกสารแนบ)
- **การส่งมอบงาน:** เน้นการแบ่งงวดงาน และการตรวจรับงานแต่ละงวด
- **อัตราค่าปรับ:** 0.1% ต่อวันของราคาค่าจ้างงาน (ต่ำที่สุด)
- **ผู้ดำเนินการ:** ผู้รับจ้าง/ผู้ให้บริการ
- **ไม่มีหัวข้อ:** การรับประกันสินค้า (เพราะเป็นการจ้างบริการ)

## ข้อมูลที่แสดงใน PDF ทุกประเภท

### ส่วนหัวเอกสาร (Header)
- ชื่อบริษัท: บริษัท อินโนบิค จำกัด
- ที่อยู่บริษัท
- เลขประจำตัวผู้เสียภาษี
- เบอร์โทร, อีเมล
- เลขที่ PO
- วันที่ออกเอกสาร

### ข้อมูล Vendor
- ชื่อบริษัท/ผู้ขาย
- ที่อยู่
- ผู้ติดต่อ
- เบอร์โทร, อีเมล

### รายละเอียดการจัดซื้อจัดจ้าง
- ประเภทงาน (ซื้อ/เช่า/จ้าง)
- วิธีการจัดซื้อจัดจ้าง:
  - ตกลงราคา (agreement_price)
  - ประมูลโดยการประกาศเชิญ (invitation_bid)
  - ประมูลโดยการประกาศเชิญชวนทั่วไป (open_bid)
  - พิเศษ ข้อ 1 (special_1)
  - พิเศษ ข้อ 2 (special_2)
  - คัดเลือก (selection)

### รายการสินค้า/บริการ
- รายการ (items)
- จำนวน
- หน่วย
- ราคาต่อหน่วย
- ราคารวม

### สรุปยอดเงิน
- ราคารวมก่อน VAT
- VAT 7%
- ราคารวมทั้งสิ้น
- สกุลเงิน (THB/USD/etc.)

### ส่วนท้าย
- ผู้อนุมัติ
- วันที่อนุมัติ
- เงื่อนไขการชำระเงิน
- หมายเหตุเพิ่มเติม

## การเรียกใช้งาน

### 1. จาก Listener เมื่อ PO ได้รับการอนุมัติ
**File:** `/app/Listeners/SendPurchaseOrderApprovedNotification.php`
```php
$pdfService = new PurchaseOrderPdfService();
$pdfContent = $pdfService->generatePdf($purchaseOrder);
$pdfFilename = $pdfService->generateFilename($purchaseOrder);
```

### 2. การแนบ PDF ใน Email
**File:** `/app/Mail/PurchaseOrderApprovedMail.php`
```php
Attachment::fromData(
    fn () => $this->pdfContent,
    $this->pdfFilename
)->withMime('application/pdf')
```

## Configuration สำหรับ mPDF

### รองรับภาษาไทย
```php
'default_font' => 'freeserif', // รองรับ Unicode/Thai
'autoLangToFont' => true,
'autoScriptToLang' => true
```

### Path สำหรับ Temp Files
```php
'tempDir' => storage_path('app/temp'),
```

## Database Fields ที่เกี่ยวข้อง

### Table: purchase_orders
- `work_type` - enum('buy', 'hire', 'rent')
- `procurement_method` - string (วิธีการจัดซื้อจัดจ้าง)
- `po_number` - เลขที่ PO
- `po_title` - หัวข้อ PO
- `vendor_name` - ชื่อผู้ขาย
- `total_amount` - จำนวนเงินรวม
- `currency` - สกุลเงิน

## การแก้ไขและปรับปรุง

### เพิ่มประเภทงานใหม่
1. เพิ่ม enum ใน migration สำหรับ work_type
2. เพิ่มเงื่อนไขใน `selectTemplate()` method
3. สร้าง template blade ใหม่ใน `/resources/views/pdf/purchase-orders/`
4. เพิ่ม label ใน `prepareData()` method

### แก้ไข Layout หรือข้อมูลใน PDF
1. แก้ไข template blade files ตามประเภทที่ต้องการ
2. หากต้องการเพิ่มข้อมูล ให้แก้ไขใน `prepareData()` method
3. ทดสอบด้วยการสร้าง PO และอนุมัติเพื่อดู PDF ที่ส่งทาง email

### แก้ไขการตั้งชื่อไฟล์
แก้ไขใน method `generateFilename()` ในไฟล์ `PurchaseOrderPdfService.php`

## Testing Commands
มี command สำหรับทดสอบการส่ง email พร้อม PDF:
- `/app/Console/Commands/TestPurchaseOrderEmail.php`
- `/app/Console/Commands/TestPurchaseOrderEmails.php`

รันด้วย: `php artisan test:po-email {po_id}`

## สรุปความแตกต่างหลักระหว่าง 3 ประเภท

| ประเภท | รหัสเอกสาร | ผู้ดำเนินการ | อัตราค่าปรับ | จุดเด่น |
|--------|------------|-------------|-------------|---------|
| **จ้าง** | PCM-002 | ผู้รับจ้าง/ผู้ให้บริการ | 0.1% | เน้นขอบเขตงาน + งวดงาน |
| **ซื้อ** | PCMN-002-FO | ผู้ขาย | 0.2% | มีการรับประกันสินค้า |
| **เช่า** | PCMN-002-FO | ผู้ให้เช่า | 0.2% | เน้นพื้นที่และอสังหาริมทรัพย์ |

## แนวทางการปรับปรุง Template

### 1. ปรับปรุง PurchaseOrderPdfService.php
- เพิ่มการกำหนด document_code ตาม work_type
- เพิ่มข้อมูลเฉพาะแต่ละประเภท (warranty, area_size, etc.)

### 2. สร้าง Template แยกตามประเภท
- **hire-sow.blade.php:** เน้นขอบเขตงาน + งวดงาน (PCM-002)
- **purchase.blade.php:** สำหรับซื้อ + การรับประกัน (PCMN-002-FO)  
- **rent.blade.php:** สำหรับเช่า + ข้อมูลพื้นที่ (PCMN-002-FO)

### 3. ข้อมูลเพิ่มเติมที่ควรมีใน Database
- `document_code` - รหัสเอกสาร
- `warranty_period` - ระยะเวลารับประกัน (สำหรับซื้อ)
- `area_size` - ขนาดพื้นที่ (สำหรับเช่า)
- `delivery_phases` - จำนวนงวดการส่งมอบ
- `inspection_committee_contact` - ข้อมูลติดต่อคณะกรรมการตรวจรับ

---
*อัพเดทล่าสุด: 2025-08-19*
*ตามเอกสาร SOW อ้างอิง: จ้าง, ซื้อ, เช่า*