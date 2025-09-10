# 📋 ระบบ GR (Goods Receipt/Material Receipt) - คู่มือสำหรับผู้ดูแลระบบ

## 📋 **Flow ของระบบ GR ที่สมบูรณ์**

### 🔄 **1. การสร้าง GR (GR Creation Flow)**

#### **Step 1: ผู้ใช้เข้าสู่ระบบ**
```
User Login → Filament Admin Panel → Goods Receipts Menu → Create GR
```

#### **Step 2: การกรอกข้อมูล GR**
```
GoodsReceiptResource Form:
├── เลือกใบสั่งซื้อ (PO) - ดึงเฉพาะ status = 'approved'
├── เลือกผู้ขาย (Supplier)
├── ⭐ เลือกคณะกรรมการตรวจสอบ (Inspection Committee) - ใหม่!
├── วันที่รับ (Receipt Date) - default วันนี้
├── งวดที่ (Delivery Milestone)
├── เปอร์เซ็นต์ (Milestone Percentage) - default 100%
├── สถานะการตรวจสอบ - default 'pending'
├── สถานะ - default 'draft'
└── หมายเหตุ (Notes)
```

#### **Step 3: การบันทึกและสร้าง GR**
```
User กด Save →
├── GoodsReceipt::create() ถูกเรียก
├── BaseModel จะใส่ company_id อัตโนมัติ
├── GoodsReceipt::boot() จะทำงาน:
│   ├── สร้าง GR number (GR2025090XXX) 
│   ├── ใส่ created_by = Auth::id()
│   └── 🚀 Dispatch GoodsReceiptCreated Event
└── ข้อมูล GR ถูกบันทึกในฐานข้อมูล
```

### 📧 **2. ระบบแจ้งเตือนทันที (Immediate Notification Flow)**

#### **Event Handling**
```
GoodsReceiptCreated Event ถูก Dispatch →
├── EventServiceProvider จับ Event
├── ส่งไปที่ SendGoodsReceiptNotification Listener
└── Listener ทำงานแบบ Queue (background job)
```

#### **Listener Processing**
```
SendGoodsReceiptNotification::handle():
├── ดึงข้อมูล GR พร้อม relationships (PO, Supplier, InspectionCommittee)
├── ตรวจสอบข้อมูลครบถ้วน:
│   ├── มี InspectionCommittee และ email หรือไม่?
│   └── มี Creator หรือไม่?
├── ส่งอีเมลหลัก → คณะกรรมการตรวจสอบ
│   └── GoodsReceiptNotificationMail → inspection_committee@email.com
├── ส่งอีเมลสำเนา → ผู้สร้าง GR (ถ้าต่างคน)
│   └── GoodsReceiptNotificationMail (isCreatorCopy=true)
└── Log ผลการส่ง
```

### ⏰ **3. ระบบแจ้งเตือนล่วงหน้า (Scheduled Reminder Flow)**

#### **Daily Schedule (ทุกวัน)**
```
08:00 → gr:send-reminders --days=15 (15 วันล่วงหน้า)
08:15 → gr:send-reminders --days=7  (7 วันล่วงหน้า)  
08:30 → gr:send-reminders --days=3  (3 วันล่วงหน้า)
08:45 → gr:send-reminders --days=1  (1 วันล่วงหน้า)
```

#### **Reminder Command Processing**
```
SendGoodsReceiptReminders Command:
├── คำนวณ reminderDate = today + X days
├── Query หา GR ที่เข้าเงื่อนไข:
│   ├── PO.expected_delivery_date = reminderDate
│   ├── GR.status IN ['draft', 'pending']
│   ├── GR.inspection_status = 'pending'
│   └── reminder_sent_at IS NULL OR < today
├── วนลูปส่งอีเมลแต่ละ GR:
│   ├── ตรวจสอบข้อมูลคณะกรรมการ
│   ├── ส่ง GoodsReceiptReminderMail
│   ├── อัปเดต reminder_sent_at = now()
│   └── Log ผลการส่ง
└── รายงานสรุป (สำเร็จ/ล้มเหลว)
```

### 🗂️ **4. โครงสร้างข้อมูล (Database Structure)**

#### **ตาราง `goods_receipts`**
```sql
goods_receipts:
├── id (Primary Key)
├── company_id (Multi-tenant)
├── gr_number (Auto-generated: GR2025090XXX)
├── purchase_order_id → purchase_orders
├── supplier_id → suppliers  
├── inspection_committee_id → users ⭐ ใหม่!
├── receipt_date
├── delivery_milestone & milestone_percentage
├── inspection_status (pending/passed/failed/partial)
├── status (draft/completed/returned/cancelled)
├── notes & inspection_notes
├── committee_notified_at
├── reminder_sent_at ⭐ ใหม่! (ป้องกันส่งซ้ำ)
├── created_by → users
├── created_at, updated_at
└── ... (fields อื่น ๆ)
```

### 📨 **5. ระบบอีเมล (Email System)**

#### **อีเมลแจ้งเตือนทันที**
```
Subject: แจ้งเตือนใบตรวจรับงาน/วัสดุ (GR) - GR2025090XXX

Content:
├── สวัสดี คุณ[ชื่อคณะกรรมการ]
├── รายละเอียด GR (เลขที่, วันที่, งวด, %)
├── ข้อมูล PO & Supplier  
├── สถานะปัจจุบัน (pending/draft)
├── ผู้สร้าง & วันที่สร้าง
├── ปุ่ม "ดูรายละเอียดใบตรวจรับ"
└── ลงท้าย
```

#### **อีเมลแจ้งเตือนล่วงหน้า**
```
Subject: 🔔 แจ้งเตือน: GR ครบกำหนดในอีก X วัน - GR2025090XXX

Content:
├── ⚠️ การแจ้งเตือนสำคัญ: ครบกำหนดในอีก X วัน
├── รายละเอียด GR เต็ม
├── 📅 วันที่ครบกำหนดส่งมอบ (highlighted)
├── 📋 การดำเนินการที่แนะนำ (step-by-step)
├── ปุ่ม "🔍 ดำเนินการตรวจสอบเลย"  
├── ⏰ หมายเหตุ: ส่งล่วงหน้า X วัน
└── ติดต่อผู้สร้าง GR
```

### 🔄 **6. Workflow States (สถานะการทำงาน)**

#### **GR Status Flow**
```
draft → pending → completed
  ├── → returned
  ├── → partially_returned  
  └── → cancelled
```

#### **Inspection Status Flow**
```
pending → passed ✅
       → failed ❌
       → partial 🟠
```

#### **Email Trigger Points**
```
GR Created → ส่งอีเมลทันที
PO.expected_delivery_date - 15 days → ส่งอีเมลแจ้งเตือน 15 วัน
PO.expected_delivery_date - 7 days → ส่งอีเมลแจ้งเตือน 7 วัน  
PO.expected_delivery_date - 3 days → ส่งอีเมลแจ้งเตือน 3 วัน
PO.expected_delivery_date - 1 day → ส่งอีเมลแจ้งเตือน 1 วัน
```

### ⚙️ **7. ระบบป้องกันข้อผิดพลาด (Error Prevention)**

#### **Duplicate Prevention**
```
reminder_sent_at field:
├── NULL = ยังไม่ส่ง
├── < startOfDay = ส่งเมื่อวานแล้ว (ส่งใหม่ได้)
└── >= startOfDay = ส่งแล้ววันนี้ (ข้าม)
```

#### **Data Validation**
```
GR Creation:
├── PO ต้อง status = 'approved'
├── InspectionCommittee ต้องมี email
├── company_id จาก session
└── created_by = Auth::user()
```

### 🎯 **8. ประโยชน์ของระบบ**

#### **สำหรับคณะกรรมการตรวจสอบ:**
- ได้รับแจ้งเตือนทันทีเมื่อมี GR ใหม่
- ได้รับเตือนล่วงหน้าก่อนครบกำหนด
- สามารถคลิกเข้าระบบได้ทันที
- รู้รายละเอียดครบถ้วน

#### **สำหรับผู้ดูแลระบบ:**
- ติดตาม log การส่งอีเมล
- ป้องกันการส่งซ้ำ
- รายงานสถิติการทำงาน
- ระบบรันอัตโนมัติ

#### **สำหรับองค์กร:**
- ลดความเสี่ยงพลาดกำหนด
- เพิ่มประสิทธิภาพการตรวจสอบ
- มีระบบ audit trail ครบถ้วน
- ประหยัดเวลาประสานงาน

---

## 🛠️ **คำสั่งที่สำคัญสำหรับผู้ดูแลระบบ**

### **Manual Testing Commands**
```bash
# ทดสอบส่งการแจ้งเตือนล่วงหน้า
php artisan gr:send-reminders --days=15
php artisan gr:send-reminders --days=7
php artisan gr:send-reminders --days=3
php artisan gr:send-reminders --days=1

# ดู schedule ทั้งหมด
php artisan schedule:list

# รัน queue jobs
php artisan queue:work --once

# ดู logs
tail -f storage/logs/laravel.log
```

### **Database Migrations**
```bash
# รัน migrations ใหม่
php artisan migrate

# ตรวจสอบ migration status
php artisan migrate:status
```

### **Cache Commands**
```bash
# Clear cache เมื่อแก้ไข config
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🔧 **ไฟล์ที่สำคัญในระบบ**

### **Models**
- `app/Models/GoodsReceipt.php` - โมเดลหลักของ GR
- `app/Models/BaseModel.php` - โมเดลพื้นฐานที่ใส่ company_id อัตโนมัติ

### **Events & Listeners**
- `app/Events/GoodsReceiptCreated.php` - Event เมื่อสร้าง GR
- `app/Listeners/SendGoodsReceiptNotification.php` - Listener ส่งอีเมลทันที

### **Mail Classes**
- `app/Mail/GoodsReceiptNotificationMail.php` - อีเมลแจ้งเตือนทันที
- `app/Mail/GoodsReceiptReminderMail.php` - อีเมลแจ้งเตือนล่วงหน้า

### **Commands**
- `app/Console/Commands/SendGoodsReceiptReminders.php` - คำสั่งส่งการแจ้งเตือนล่วงหน้า

### **Views**
- `resources/views/emails/goods-receipt-notification.blade.php` - เทมเพลตอีเมลทันที
- `resources/views/emails/goods-receipt-reminder.blade.php` - เทมเพลตอีเมลล่วงหน้า

### **Filament Resources**
- `app/Filament/Resources/GoodsReceiptResource.php` - หน้าจัดการ GR ใน Admin Panel

### **Configuration**
- `routes/console.php` - การตั้งเวลา schedule
- `app/Providers/EventServiceProvider.php` - การลงทะเบียน events

---

## 📊 **สถิติและการติดตาม**

### **Log Messages ที่ควรติดตาม**
```
- "GR notification sent to inspection committee"
- "GR reminder sent" 
- "Failed to send GR notification"
- "GoodsReceipt not found"
- "Creator not found"
```

### **Database Queries สำหรับ Report**
```sql
-- ดู GR ที่ส่งการแจ้งเตือนแล้ว
SELECT gr_number, reminder_sent_at, inspection_committee_id 
FROM goods_receipts 
WHERE reminder_sent_at IS NOT NULL;

-- ดู GR ที่ยังคาง pending
SELECT gr_number, status, inspection_status, expected_delivery_date
FROM goods_receipts gr
JOIN purchase_orders po ON gr.purchase_order_id = po.id
WHERE gr.inspection_status = 'pending' 
AND po.expected_delivery_date < NOW();

-- นับจำนวนการแจ้งเตือนต่อวัน
SELECT DATE(reminder_sent_at) as date, COUNT(*) as count
FROM goods_receipts 
WHERE reminder_sent_at IS NOT NULL
GROUP BY DATE(reminder_sent_at)
ORDER BY date DESC;
```

---

**วันที่สร้าง:** 09/09/2025  
**เวอร์ชัน:** 1.0  
**ผู้สร้าง:** Claude Code Assistant  
**สถานะ:** Production Ready ✅