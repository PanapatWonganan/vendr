# VENDR Bug Fixes & Improvements Session
**Date:** 2026-02-28
**Branch:** main
**Status:** Surveyed - Ready to fix

---

## Survey Summary

สำรวจระบบทั้งหมดแล้ว พบปัญหาและจุดที่ควรปรับปรุง **200+ จุด** แบ่งเป็นหมวดหมู่ดังนี้:

| หมวด | จำนวน Issues | ระดับความสำคัญ |
|------|-------------|---------------|
| Security (Critical) | 15 | CRITICAL |
| Model Issues | 107 | HIGH-MEDIUM |
| Filament Resource Issues | 92 | HIGH-MEDIUM |
| Controller/Route Issues | 24 | HIGH-MEDIUM |
| Telegram & Notifications | 35 | HIGH-MEDIUM |
| Database/Config Issues | 17 | HIGH-LOW |

---

## CRITICAL: ต้องแก้ทันที

### 1. Exposed API Keys in .env (CRITICAL)
- `.env` มี API keys ที่ commit ไปใน git
  - TELEGRAM_BOT_TOKEN
  - SENDGRID_API_KEY
  - OPENAI_API_KEY
  - APP_KEY
- **Action:** Rotate ทุก key ทันที, เพิ่ม `.env` ใน `.gitignore`, ลบ history

### 2. Mass Assignment Vulnerabilities (CRITICAL)
- Models หลายตัวมี audit fields (`approved_by`, `rejected_by`, `created_by`) อยู่ใน `$fillable`
- **ไฟล์ที่ต้องแก้:**
  - `app/Models/PurchaseOrder.php` - ลบ `approved_by`, `rejected_by`, `created_by`, `updated_by` จาก $fillable
  - `app/Models/PurchaseRequisition.php` - ลบ `approved_by`, `rejected_by`, `created_by` จาก $fillable
  - `app/Models/GoodsReceipt.php` - ลบ `received_by`, `created_by`, `updated_by`, `quality_checked_by`, `reviewed_by` จาก $fillable
  - `app/Models/PaymentMilestone.php` - ลบ `paid_by` จาก $fillable
  - `app/Models/ValueAnalysis.php` - ลบ `created_by`, `analyzed_by`, `approved_by` จาก $fillable
  - `app/Models/ContractApproval.php` - ลบ `reviewed_by`, `reviewed_at` จาก $fillable
  - `app/Models/VendorAssessment.php` - ลบ `assessed_by` จาก $fillable
  - `app/Models/Supplier.php` - ลบ `created_by`, `updated_by` จาก $fillable
  - `app/Models/User.php` - ลบ `telegram_otp` จาก $fillable

### 3. Multi-Tenant Bypass - Company Switching (CRITICAL)
- `app/Http/Middleware/CompanyMiddleware.php` - ไม่ validate ว่า user มีสิทธิ์เข้า company นั้นหรือไม่
- `app/Http/Controllers/CompanyController.php` - `switchCompany()` ไม่ check authorization
- **Action:** เพิ่ม authorization check ให้ verify ว่า user มี role ใน company ก่อน switch

### 4. Missing Authorization on File Downloads (CRITICAL)
- `app/Http/Controllers/ContractApprovalController.php:400` - `downloadFile()` ไม่มี authorization check
- `app/Http/Controllers/PurchaseRequisitionController.php:687` - check แค่ department ไม่ check company
- **Action:** เพิ่ม authorization checks ทุก download route

### 5. OTP Stored in Plain Text (HIGH)
- Telegram OTP เก็บเป็น plain text ในฐานข้อมูล
- **Action:** เปลี่ยนเป็น Hash::make() ตอนบันทึก, Hash::check() ตอนยืนยัน

---

## HIGH PRIORITY: ควรแก้โดยเร็ว

### 6. Model Inconsistencies
- [ ] **Dual Vendor/Supplier concept** - มีทั้ง `Vendor` model และ `Supplier` model ทำหน้าที่เหมือนกัน ทำให้สับสน
- [ ] **BaseModel ไม่ consistent** - บาง model extend BaseModel (auto-set company_id), บางตัวไม่ extend
  - ต้อง extend: `Vendor`, `PurchaseRequisition`
- [ ] **Duplicate relationships** - `PurchaseRequisition` มี `user()` กับ `requester()` ซ้ำกัน, `creator()` กับ `createdBy()` ซ้ำกัน
- [ ] **Missing $casts** - `Vendor` model ไม่มี $casts เลย
- [ ] **GoodsReceipt** - ใช้ `STATUS_PENDING_REVIEW` ที่ไม่มีการ define เป็น constant

### 7. Missing Soft Deletes
Models ที่ควรมี SoftDeletes แต่ยังไม่มี:
- [ ] `Vendor`
- [ ] `PurchaseRequisition`
- [ ] `GoodsReceipt`
- [ ] `PaymentMilestone`
- [ ] `VendorEvaluation`
- [ ] `ValueAnalysis`
- [ ] `ProcurementAnomaly`

### 8. N+1 Query Problems
- [ ] `User.hasPermission()` - load roles แล้ว loop check permission ทีละตัว
- [ ] `VendorEvaluation.calculateOverallScore()` - query relationship 2 ครั้ง
- [ ] `VendorResource` table columns - query VendorScore 4 ครั้งต่อ row
- [ ] `VendorPerformanceReportResource` - 600+ queries ถ้ามี 100 vendors

### 9. ContractApprovalResource - 19 Issues (Worst Resource)
- [ ] ไม่มี approval workflow actions (approve/reject)
- [ ] Status เป็น TextInput แทนที่จะเป็น Select
- [ ] ไม่มี tenant scoping (ดูข้อมูลข้ามบริษัทได้)
- [ ] ไม่มี ViewAction
- [ ] Form fields ไม่มี validation

### 10. Missing Audit Logging
- [ ] PO approve/reject - ไม่มี logging
- [ ] Vendor status changes - ไม่มี logging
- [ ] Critical actions ต่างๆ ไม่มี audit trail

### 11. Database Migration Issues
- [ ] Migration `2025_07_18_222330` - สร้าง column แต่ไม่สร้าง foreign key, down() พยายาม drop foreign key ที่ไม่มี
- [ ] Duplicate title migration สำหรับ `purchase_requisitions`
- [ ] Duplicate database connection definitions ใน `config/database.php`
- [ ] Missing indexes บน foreign key columns หลายตัว

### 12. Missing Telegram Notifications
- [ ] ไม่มี Telegram notification เมื่อ PO ถูกสร้าง
- [ ] ไม่มี Telegram notification เมื่อ PO ถูก approve
- [ ] ไม่มี Telegram notification เมื่อ GR ถูกสร้าง/approve
- [ ] ไม่มี Telegram notification เมื่อ Payment Milestone ถูกชำระ
- [ ] ไม่มี event/notification สำหรับ ContractApproval เลย
- [ ] Notification preferences เก็บใน Cache (หายเมื่อ restart) ไม่ได้เก็บใน DB

---

## MEDIUM PRIORITY: ปรับปรุงเมื่อมีเวลา

### 13. Filament Resource Improvements
- [ ] Deprecated `.reactive()` → เปลี่ยนเป็น `.live()` ใน VendorEvaluationResource, KnowledgeArticleResource
- [ ] Navigation groups ไม่ consistent (ผสม Thai/English)
- [ ] Email notifications ส่งแบบ sync (blocking) ใน GoodsReceiptResource, PaymentMilestoneResource → ควรใช้ queue
- [ ] PaymentMilestone - ไม่มี validation ว่า total percentages <= 100%

### 14. Controller Improvements
- [ ] Error messages expose exception details (information disclosure) - `DashboardController.php:384`
- [ ] Inconsistent API response format (mix JSON/redirect)
- [ ] Race conditions ใน `getCurrentUserId()` และ `ValueAnalysisController`
- [ ] File upload อยู่นอก DB transaction → อาจเกิด orphaned records
- [ ] Missing rate limiting บนหลาย routes

### 15. Telegram Bot Improvements
- [ ] ไม่ validate Telegram API response structure
- [ ] ไม่มี retry logic สำหรับ API calls ที่ fail
- [ ] ไม่ handle rate limiting (429) จาก Telegram
- [ ] Callback data ไม่ validate ค่าก่อนใช้งาน
- [ ] ไม่ handle กรณี user block bot (ควร clear telegram_chat_id)
- [ ] ไม่ truncate ข้อความยาว (Telegram limit 4096 chars)
- [ ] OTP ไม่มี rate limiting จำนวนครั้งที่ลอง

### 16. Missing Scopes & Helper Methods
- [ ] หลาย Models ขาด scope ที่ใช้บ่อย เช่น `scopeByVendor()`, `scopeForDateRange()`, `scopePending()`
- [ ] Status constants ไม่ define ใน PurchaseRequisition, ContractApproval, ValueAnalysis
- [ ] Missing helper methods สำหรับ business logic ที่ใช้บ่อย

### 17. TODO Comments ที่ยังไม่ implement
- [ ] `PurchaseRequisitionController:276` - "TODO: Add approval workflow logic here"
- [ ] `PurchaseRequisitionController:561-595` - Attachment delete/update disabled
- [ ] `SlaService:42` - "TODO: Add holiday checking logic"
- [ ] `PermissionSeeder:117` - "TODO: Assign specific permissions to other roles"

---

## LOW PRIORITY: Nice to have

### 18. Code Quality
- [ ] Unused imports ใน PurchaseRequisitionController
- [ ] Debug logging ที่ควรลบ (PurchaseRequisitionController:195)
- [ ] Missing HasFactory trait ใน VendorEvaluation, VendorAssessment, ChatConversation, ProcurementAnomaly, SlaTracking
- [ ] Inconsistent boolean naming (is_active vs email_po_approved)
- [ ] Inconsistent date casting (date vs datetime ใน fields ที่คล้ายกัน)

### 19. Missing .env.example
- [ ] สร้าง `.env.example` ที่มีตัวแปรทั้งหมดที่ต้องการ
- [ ] Document ตัวแปรที่ reference แต่ไม่มีใน .env (INNOBIC_*_DB_*, TELEGRAM_WEBHOOK_URL, DBD_API_TOKEN)

### 20. Performance Optimization
- [ ] เปลี่ยน CACHE_STORE และ QUEUE_CONNECTION จาก database เป็น Redis (ถ้ามี)
- [ ] เพิ่ม missing database indexes
- [ ] Implement eager loading strategies ป้องกัน N+1

---

## Recommended Fix Order

สำหรับวันนี้ แนะนำลำดับการแก้ไขดังนี้:

### Round 1: Security Fixes (ทำก่อน)
1. Rotate exposed API keys
2. Fix mass assignment vulnerabilities (ลบ audit fields จาก $fillable)
3. Fix company switching authorization
4. Fix file download authorization
5. Hash OTP in database

### Round 2: Data Integrity
6. Fix BaseModel extension inconsistency
7. Add SoftDeletes to core models
8. Fix migration issues (foreign keys, duplicates)

### Round 3: Performance
9. Fix N+1 queries (VendorResource, VendorPerformanceReport)
10. Fix deprecated API usage (.reactive() → .live())

### Round 4: Feature Completeness
11. Fix ContractApprovalResource (add actions, tenant scoping)
12. Add missing Telegram notifications
13. Implement TODO items
14. Add missing audit logging

---

## How to Continue Next Session

เมื่อเปิด terminal ใหม่ ให้บอก Claude:

```
ช่วยอ่านไฟล์ docs/session-2026-02-28-bug-fixes.md แล้วทำงานต่อจากที่ค้างไว้
```

Claude จะอ่านไฟล์นี้และทำงานต่อเนื่องจาก checklist ที่ยังไม่ได้ทำ

---

*Generated by Claude Code - 2026-02-28*
