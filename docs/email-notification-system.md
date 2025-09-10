# ระบบอีเมลแจ้งเตือน Purchase Order

## ภาพรวมระบบ

ระบบอีเมลแจ้งเตือน Purchase Order ใช้ Laravel Event-Driven Architecture เพื่อส่งอีเมลแจ้งเตือนให้ผู้ขอใบ PO เมื่อมีการอนุมัติหรือปฏิเสธ

### คุณสมบัติหลัก

1. **การแจ้งเตือนแบบอัตโนมัติ**: ส่งอีเมลทันทีเมื่อมีการอนุมัติ/ปฏิเสธ
2. **Queue System**: ส่งอีเมลแบบ background เพื่อประสิทธิภาพ
3. **Email Templates**: Template สวยงามและ responsive
4. **User Preferences**: ผู้ใช้สามารถเปิด/ปิดการรับอีเมลได้
5. **Error Handling**: จัดการ error และ logging ครบถ้วน

## โครงสร้างระบบ

### 1. Events
- `App\Events\PurchaseOrderApproved`: Event เมื่อ PO ได้รับอนุมัติ
- `App\Events\PurchaseOrderRejected`: Event เมื่อ PO ถูกปฏิเสธ

### 2. Listeners
- `App\Listeners\SendPurchaseOrderApprovedNotification`: จัดการการส่งอีเมลอนุมัติ
- `App\Listeners\SendPurchaseOrderRejectedNotification`: จัดการการส่งอีเมลปฏิเสธ

### 3. Mail Classes
- `App\Mail\PurchaseOrderApprovedMail`: Template อีเมลแจ้งการอนุมัติ
- `App\Mail\PurchaseOrderRejectedMail`: Template อีเมลแจ้งการปฏิเสธ

### 4. Email Templates
- `resources/views/emails/purchase-order-approved.blade.php`: Template HTML อนุมัติ
- `resources/views/emails/purchase-order-rejected.blade.php`: Template HTML ปฏิเสธ

## การใช้งาน

### การส่งอีเมลอัตโนมัติ

อีเมลจะถูกส่งโดยอัตโนมัติเมื่อ:

```php
// ในการอนุมัติ PO
if ($purchaseOrder->approve($approverId)) {
    event(new PurchaseOrderApproved($purchaseOrder, $approver));
}

// ในการปฏิเสธ PO
if ($purchaseOrder->reject($rejectedBy, $reason)) {
    event(new PurchaseOrderRejected($purchaseOrder, $rejector, $reason));
}
```

### การทดสอบอีเมล

```bash
# ทดสอบอีเมลอนุมัติ
php artisan test:po-emails 1 --type=approved

# ทดสอบอีเมลปฏิเสธ
php artisan test:po-emails 1 --type=rejected
```

### การรัน Queue Worker

```bash
# รัน queue worker เพื่อส่งอีเมลแบบ background
php artisan queue:work

# รันแค่ job เดียว
php artisan queue:work --once

# ดู queue status
php artisan queue:failed
```

## การตั้งค่าผู้ใช้

### Email Preferences ในฐานข้อมูล

| Field | Description | Default |
|-------|-------------|---------|
| `email_po_approved` | รับอีเมลเมื่อ PO อนุมัติ | true |
| `email_po_rejected` | รับอีเมลเมื่อ PO ปฏิเสธ | true |
| `email_po_notifications` | เปิด/ปิดการแจ้งเตือนทั้งหมด | true |

### การเช็ค Preferences ใน Code

```php
// ตรวจสอบก่อนส่งอีเมล
if ($user->email_po_approved && $user->email_po_notifications) {
    // ส่งอีเมล
}
```

## Content ของอีเมล

### อีเมลแจ้งการอนุมัติ

- ✅ Header สีเขียวแสดงความสำเร็จ
- 📋 รายละเอียด PO ครบถ้วน
- 👤 ข้อมูลผู้อนุมัติ
- 🔗 ลิงก์ไปดู PO
- 📋 ขั้นตอนต่อไป

### อีเมลแจ้งการปฏิเสธ

- ❌ Header สีแดงแสดงการปฏิเสธ
- 📋 รายละเอียด PO
- 👤 ข้อมูลผู้ปฏิเสธ
- 🚫 เหตุผลการปฏิเสธ
- 🔗 ลิงก์ไปแก้ไข PO
- 💡 คำแนะนำการแก้ไข

## การตั้งค่าอีเมล

### ไฟล์ `.env`

```env
# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Innobic PO System"

# Queue Configuration
QUEUE_CONNECTION=database
```

### การใช้ Gmail SMTP

1. เปิด 2-Factor Authentication
2. สร้าง App Password
3. ใช้ App Password ใน `MAIL_PASSWORD`

### การใช้ Mailtrap (สำหรับ Testing)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
```

## Logging และ Monitoring

### Log Messages

- ✅ **Info**: Email sent successfully
- ⚠️ **Warning**: Email skipped (user preferences)
- ❌ **Error**: Email failed to send

### ตำแหน่ง Log Files

```
storage/logs/laravel.log
```

### การตรวจสอบ Log

```bash
# ดู log ล่าสุด
tail -f storage/logs/laravel.log

# ค้นหา email logs
grep "Purchase Order.*email" storage/logs/laravel.log
```

## Troubleshooting

### ปัญหาที่พบบ่อย

1. **อีเมลไม่ส่ง**
   - ตรวจสอบ queue worker ว่าทำงานอยู่หรือไม่
   - ตรวจสอบการตั้งค่า SMTP
   - ดู error ใน log

2. **Template ไม่สวย**
   - ตรวจสอบ CSS ใน email template
   - ทดสอบใน email client ต่างๆ

3. **User ไม่ได้รับอีเมล**
   - ตรวจสอบ email preferences
   - ตรวจสอบ email address ถูกต้องหรือไม่
   - เช็ค spam folder

### การ Debug

```php
// เปิด debug mode ใน listener
Log::debug('PO Email Debug', [
    'po_number' => $purchaseOrder->po_number,
    'creator_email' => $creator->email,
    'preferences' => [
        'email_po_approved' => $creator->email_po_approved,
        'email_po_rejected' => $creator->email_po_rejected,
        'email_po_notifications' => $creator->email_po_notifications,
    ]
]);
```

## การขยายระบบ

### เพิ่มประเภทการแจ้งเตือนใหม่

1. สร้าง Event ใหม่
2. สร้าง Listener ใหม่
3. สร้าง Mail class ใหม่
4. สร้าง Template ใหม่
5. ลงทะเบียนใน EventServiceProvider

### เพิ่มผู้รับอีเมลเพิ่มเติม

```php
// ใน Listener
$recipients = [
    $creator->email,
    $departmentHead->email,
    'admin@company.com'
];

foreach ($recipients as $email) {
    Mail::to($email)->send($mailable);
}
```

### เพิ่ม CC/BCC

```php
Mail::to($creator->email)
    ->cc(['manager@company.com'])
    ->bcc(['admin@company.com'])
    ->send($mailable);
```

## Security Considerations

1. **Email Validation**: ตรวจสอบ email address ก่อนส่ง
2. **Rate Limiting**: จำกัดจำนวนอีเมลต่อช่วงเวลา
3. **Data Sanitization**: ทำความสะอาดข้อมูลก่อนแสดงใน template
4. **Authentication**: ตรวจสอบสิทธิ์ก่อนส่งอีเมล

## Performance Tips

1. ใช้ Queue สำหรับการส่งอีเมลแบบ batch
2. ใช้ Redis แทน database สำหรับ queue
3. ใช้ Email Service Provider เช่น SendGrid, Mailgun
4. Cache email templates

## Best Practices

1. ทดสอบ email templates ใน client ต่างๆ
2. ใช้ plain text fallback
3. ใส่ unsubscribe link
4. ใช้ responsive design
5. Keep subject lines short และ descriptive
6. ใช้ logging สำหรับ audit trail 