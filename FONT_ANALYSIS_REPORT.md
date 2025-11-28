# รายงานการวิเคราะห์ Font ในระบบ Innobic

## สรุปผล

ระบบใช้ **Sarabun** เป็น font หลัก แต่มีบางส่วนที่ใช้ font แตกต่างกัน ซึ่งอาจทำให้เกิดความไม่สอดคล้องในการแสดงผล

---

## 1. การตั้งค่า Font หลัก

### ✅ Filament Admin Panel
**ไฟล์:** `app/Providers/Filament/AdminPanelProvider.php:49`
```php
->font('Sarabun')
```
- ตั้งค่าให้ทั้งระบบ Filament ใช้ Sarabun
- ครอบคลุมทุกหน้าใน admin panel

### ✅ CSS หลัก
**ไฟล์:** `resources/css/app.css`
```css
@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');

body {
    font-family: 'Sarabun', sans-serif;
}
```

---

## 2. การใช้งาน Font ในแต่ละส่วน

### ✅ ส่วนที่ใช้ Sarabun ถูกต้อง

| ประเภท | ไฟล์ | Font |
|--------|------|------|
| Layout | `resources/views/layouts/app.blade.php` | Sarabun, sans-serif |
| Layout | `resources/views/layouts/company.blade.php` | Sarabun, sans-serif |
| Layout | `resources/views/layouts/auth.blade.php` | Sarabun, sans-serif |
| Company Select | `resources/views/company/select-filament.blade.php` | Sarabun, Inter, system-ui, sans-serif |
| Email | `resources/views/emails/purchase-order-approved.blade.php` | Sarabun, Arial, sans-serif |
| Email | `resources/views/emails/purchase-order-rejected.blade.php` | Sarabun, Arial, sans-serif |
| Email | `resources/views/emails/purchase-requisition-approved.blade.php` | Sarabun, Arial, sans-serif |
| Email | `resources/views/emails/purchase-requisition-rejected.blade.php` | Sarabun, Arial, sans-serif |
| Email | `resources/views/emails/delivery-reminder.blade.php` | Sarabun, Arial, sans-serif |
| Email | `resources/views/emails/goods-receipt-created.blade.php` | Sarabun, Arial, sans-serif |

---

## 3. ⚠️ ส่วนที่ใช้ Font แตกต่าง (ปัญหา)

### 🔴 ปัญหาสำคัญ

#### 3.1 Email Template ใช้ Segoe UI
**ไฟล์:** `resources/views/emails/purchase-requisition-submitted.blade.php:9`
```css
font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
```
**ผลกระทบ:** Email ฉบับนี้จะแสดงผลต่างจาก email อื่นๆ

**แนะนำแก้ไข:**
```css
font-family: 'Sarabun', Arial, sans-serif;
```

---

#### 3.2 PDF ใช้ THSarabunNew
**ไฟล์:**
- `resources/views/delivery-note.blade.php:15`
- `resources/views/pdf/delivery-note.blade.php:15`
- `resources/views/pdf/purchase-orders/purchase.blade.php:15`
- `resources/views/pdf/purchase-orders/hire-sow.blade.php:15`
- `resources/views/pdf/purchase-orders/rent.blade.php:15`

```css
font-family: 'THSarabunNew', 'Sarabun', Arial, sans-serif;
```

**หมายเหตุ:**
- THSarabunNew เป็น font ที่ออกแบบมาสำหรับ PDF โดยเฉพาะ
- รองรับภาษาไทยได้ดีกว่าใน PDF
- **อาจไม่จำเป็นต้องแก้ไข** ถ้าการแสดงผลใน PDF ไม่มีปัญหา

---

#### 3.3 PDF Hire ใช้ Serif
**ไฟล์:** `resources/views/pdf/purchase-orders/hire.blade.php:16`
```css
font-family: serif;
```
**ผลกระทบ:** PDF ใบนี้จะแสดงผลแตกต่างจาก PDF ใบอื่นๆ

**แนะนำแก้ไข:**
```css
font-family: 'THSarabunNew', 'Sarabun', Arial, sans-serif;
```

---

#### 3.4 Guest Layout ใช้ Figtree
**ไฟล์:** `resources/views/layouts/guest.blade.php`
```html
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
```

**หมายเหตุ:**
- เป็น default Laravel Breeze template
- ใช้สำหรับหน้า login/register ที่ไม่ได้ใช้ Filament
- **แนะนำเปลี่ยน** เป็น Sarabun เพื่อความสอดคล้อง

---

## 4. การโหลด Font

### Google Fonts (Sarabun)
ส่วนใหญ่ของระบบโหลด Sarabun จาก Google Fonts:
```html
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
```

**น้ำหนัก (weights) ที่ใช้:** 300, 400, 500, 600, 700

---

## 5. สรุปปัญหาและคำแนะนำ

### 🔴 ต้องแก้ไขทันที (เพื่อความสอดคล้อง)

1. **Email Template:**
   - แก้ `resources/views/emails/purchase-requisition-submitted.blade.php`
   - เปลี่ยนจาก `Segoe UI` เป็น `Sarabun`

2. **PDF Hire:**
   - แก้ `resources/views/pdf/purchase-orders/hire.blade.php`
   - เปลี่ยนจาก `serif` เป็น `THSarabunNew, Sarabun`

3. **Guest Layout:**
   - แก้ `resources/views/layouts/guest.blade.php`
   - เปลี่ยนจาก `Figtree` เป็น `Sarabun`

### 🟡 พิจารณาแก้ไข (ไม่เร่งด่วน)

4. **PDF Templates ที่ใช้ THSarabunNew:**
   - ใช้ได้ดีอยู่แล้ว สำหรับ PDF
   - ถ้าต้องการความสอดคล้อง 100% อาจเปลี่ยนเป็น Sarabun
   - แต่อาจส่งผลต่อการแสดงผลภาษาไทยใน PDF

---

## 6. ข้อแนะนำเพิ่มเติม

### การตั้งค่า Font Fallback ที่ดี
ปัจจุบันใช้:
```css
font-family: 'Sarabun', Arial, sans-serif;
```

**แนะนำ:**
```css
font-family: 'Sarabun', 'Noto Sans Thai', Arial, sans-serif;
```
- เพิ่ม Noto Sans Thai เป็น fallback สำหรับภาษาไทย
- กรณี Sarabun โหลดไม่ได้

### การใช้ Font Variable (ถ้าต้องการปรับเปลี่ยนในอนาคต)
สร้างไฟล์ CSS config:
```css
:root {
    --font-primary: 'Sarabun', sans-serif;
    --font-thai-pdf: 'THSarabunNew', 'Sarabun', sans-serif;
}

body {
    font-family: var(--font-primary);
}
```

---

## 7. ตรวจสอบเพิ่มเติม

### คำสั่งตรวจสอบ Font ที่ใช้:
```bash
# ค้นหาไฟล์ทั้งหมดที่กำหนด font-family
grep -r "font-family" resources/views --include="*.blade.php" -n

# ค้นหาการโหลด Google Fonts
grep -r "fonts.googleapis.com" resources/views --include="*.blade.php" -n
```

---

## 8. Checklist การแก้ไข

- [ ] แก้ไข `purchase-requisition-submitted.blade.php` (Segoe UI → Sarabun)
- [ ] แก้ไข `pdf/purchase-orders/hire.blade.php` (serif → THSarabunNew)
- [ ] แก้ไข `layouts/guest.blade.php` (Figtree → Sarabun)
- [ ] ทดสอบการแสดงผล Email
- [ ] ทดสอบการแสดงผล PDF
- [ ] ทดสอบหน้า Login/Register
- [ ] ตรวจสอบภาษาไทยแสดงผลถูกต้องทุกหน้า

---

**สรุปสุดท้าย:**
ระบบใช้ **Sarabun** เป็นหลัก แต่มี **3 ไฟล์** ที่ใช้ font แตกต่าง:
1. Email 1 ฉบับ (Segoe UI)
2. PDF 1 ไฟล์ (serif)
3. Guest layout (Figtree)

**ควรแก้ไขให้ใช้ Sarabun ทั้งหมด** เพื่อความสอดคล้องในการแสดงผล

---

**วิเคราะห์โดย:** Claude Code
**วันที่:** 2025-10-19
**เวอร์ชัน:** 1.0
