# แผนการพัฒนาระบบ TOR/SOW (Terms of Reference / Scope of Work)

## 📖 ภาพรวมโครงการ

ระบบ TOR/SOW เป็นส่วนขยายของระบบ Procurement Management ที่มีอยู่ เพื่อจัดการข้อกำหนดอ้างอิงและขอบเขตงานก่อนการจัดซื้อจัดจ้าง

### ความแตกต่างระหว่าง TOR และ SOW
- **TOR (Terms of Reference)**: ใช้ในภาครัฐ, เน้นกรอบการทำงาน, ใช้กับการจ้างที่ปรึกษา/วิจัย
- **SOW (Statement/Scope of Work)**: ใช้ในภาคเอกชน, เน้นรายละเอียดงาน, ใช้กับการจ้างเหมาบริการ

## 📋 แผนการพัฒนาแบบละเอียด

### Phase 1: การวิเคราะห์และออกแบบ

#### 1. วิเคราะห์ระบบปัจจุบันและความต้องการ TOR/SOW
- [ ] ศึกษา workflow การจัดซื้อจัดจ้างปัจจุบัน
- [ ] ระบุจุดที่ต้องใช้ TOR/SOW ในกระบวนการ
- [ ] กำหนดประเภท TOR/SOW:
  - บริการทั่วไป (General Services)
  - ที่ปรึกษา (Consulting Services) 
  - งานก่อสร้าง (Construction)
  - จัดหาสินค้า (Goods Procurement)
  - เทคโนโลยีสารสนเทศ (IT Services)
- [ ] กำหนด User Roles และ Permissions

#### 2. ออกแบบ Database Schema สำหรับ TOR/SOW
```sql
-- ตาราง TOR หลัก
terms_of_references:
- id (PK)
- reference_number (unique)
- title
- description (longtext)
- category_id (FK)
- department_id (FK)
- created_by (FK - users)
- approver_id (FK - users)
- status (enum: draft, pending_approval, approved, rejected, closed)
- budget_min, budget_max
- start_date, end_date
- requirements (json)
- deliverables (json)
- evaluation_criteria (json)
- created_at, updated_at

-- ประเภท TOR
tor_categories:
- id (PK)
- name
- description
- template_fields (json)
- created_at, updated_at

-- ไฟล์แนบ TOR
tor_attachments:
- id (PK)
- terms_of_reference_id (FK)
- filename
- original_name
- file_path
- file_size
- mime_type
- uploaded_by (FK - users)
- created_at, updated_at

-- การอนุมัติ TOR
tor_approvals:
- id (PK)
- terms_of_reference_id (FK)
- approver_id (FK - users)
- status (enum: pending, approved, rejected)
- comments
- approved_at
- created_at, updated_at

-- แม่แบบ TOR
tor_templates:
- id (PK)
- category_id (FK)
- name
- template_content (longtext)
- fields (json)
- is_active (boolean)
- created_by (FK - users)
- created_at, updated_at

-- เชื่อมโยงกับ Purchase Requisition
purchase_requisitions:
+ terms_of_reference_id (FK) - เพิ่มฟิลด์นี้
```

### Phase 2: การพัฒนา Backend

#### 3. สร้าง Model และ Migration สำหรับ TOR/SOW
**Models ที่ต้องสร้าง:**
- `app/Models/TermsOfReference.php`
- `app/Models/TorCategory.php`
- `app/Models/TorAttachment.php`
- `app/Models/TorApproval.php`
- `app/Models/TorTemplate.php`

**Relations:**
```php
// TermsOfReference
public function category() // belongsTo TorCategory
public function creator() // belongsTo User
public function approver() // belongsTo User
public function attachments() // hasMany TorAttachment
public function approvals() // hasMany TorApproval
public function purchaseRequisitions() // hasMany PurchaseRequisition

// PurchaseRequisition (แก้ไข existing)
public function termsOfReference() // belongsTo TermsOfReference
```

**Migrations ที่ต้องสร้าง:**
- `create_tor_categories_table`
- `create_terms_of_references_table`
- `create_tor_attachments_table`
- `create_tor_approvals_table`
- `create_tor_templates_table`
- `add_terms_of_reference_id_to_purchase_requisitions_table`

#### 4. สร้าง Seeders และ Factories
- TorCategorySeeder (ข้อมูลประเภท TOR เริ่มต้น)
- TorTemplateSeeder (แม่แบบ TOR พื้นฐาน)
- TermsOfReferenceFactory (สำหรับ testing)

### Phase 3: การพัฒนา Frontend (Filament)

#### 5. สร้าง Filament Resource สำหรับจัดการ TOR/SOW
**Resources ที่ต้องสร้าง:**
- `app/Filament/Resources/TermsOfReferenceResource.php`
- `app/Filament/Resources/TorCategoryResource.php`
- `app/Filament/Resources/TorTemplateResource.php`

**Form Components:**
- Rich Text Editor สำหรับ description
- JSON Form สำหรับ requirements และ deliverables
- File Upload สำหรับ attachments
- Date Range Picker สำหรับ timeline
- Budget Range fields
- Status indicators

**Table Columns:**
- Reference number
- Title
- Category
- Status badge
- Budget range
- Created date
- Actions

**Filters:**
- Category filter
- Status filter
- Date range filter
- Creator filter

#### 6. เชื่อมโยง TOR/SOW กับ Purchase Requisition
- แก้ไข `PurchaseRequisitionResource`
- เพิ่ม TOR selection ใน form
- สร้าง RelationManager สำหรับแสดง TOR details
- เพิ่ม validation rule: บาง category ของ PR ต้องมี TOR

### Phase 4: Workflow และ Approval

#### 7. สร้างระบบ Workflow และ Approval สำหรับ TOR/SOW
**Approval Workflow:**
1. Draft → Pending Approval
2. Pending Approval → Approved/Rejected
3. Approved → Active (พร้อมใช้งาน)
4. Active → Closed (หมดอายุ)

**สร้างไฟล์:**
- `app/Services/TorApprovalService.php`
- `app/Mail/TorApprovalRequest.php`
- `app/Mail/TorApproved.php`
- `app/Mail/TorRejected.php`
- `app/Notifications/TorStatusChanged.php`

**Events & Listeners:**
- `TorSubmittedForApproval` event
- `TorApproved` event
- `TorRejected` event

#### 8. สร้าง Templates System
- Template management interface
- Template variables system:
  - `{{project_title}}`
  - `{{budget_range}}`
  - `{{timeline}}`
  - `{{department}}`
- Template preview functionality
- Clone template to new TOR

### Phase 5: File Management

#### 9. เพิ่มระบบจัดการไฟล์แนบสำหรับ TOR/SOW
**Features:**
- Multiple file upload
- File type validation (PDF, DOC, DOCX, XLS, XLSX)
- File size limits
- File versioning
- Download tracking
- Secure file access (ตาม permissions)

**Storage Configuration:**
- ใช้ Laravel Storage disk
- Organized folder structure: `tor/{tor_id}/attachments/`
- File naming convention: `{timestamp}_{original_name}`

### Phase 6: Reporting และ Notifications

#### 10. สร้างระบบแจ้งเตือนและรายงาน TOR/SOW
**Notifications:**
- Email alerts สำหรับ approval pending
- Deadline reminders
- Status change notifications
- Daily/Weekly digest

**Reports:**
- TOR status dashboard
- Usage statistics by department
- Performance metrics (approval time, etc.)
- Budget analysis
- Export functionality (PDF, Excel)

**Dashboard Widgets:**
- TOR status overview
- Pending approvals count
- Recent activities
- Upcoming deadlines

### Phase 7: Testing และ Deployment

#### 11. ทดสอบและปรับปรุงระบบ TOR/SOW
**Testing Strategy:**
- Unit Tests สำหรับ Models และ Services
- Feature Tests สำหรับ Workflows
- Browser Tests สำหรับ UI interactions
- API Tests สำหรับ integrations

**Test Cases:**
- TOR creation และ validation
- Approval workflow
- File upload และ management
- Permissions และ access control
- Email notifications
- Report generation

## 🎯 Deliverables แต่ละ Phase

### Phase 1 Deliverables
- [ ] Requirements analysis document
- [ ] Database ERD diagram
- [ ] User stories และ acceptance criteria
- [ ] Wireframes สำหรับ UI

### Phase 2 Deliverables
- [ ] Database migrations
- [ ] Eloquent models พร้อม relationships
- [ ] Seeders และ sample data
- [ ] Unit tests สำหรับ models

### Phase 3 Deliverables
- [ ] Filament resources
- [ ] Form และ table configurations
- [ ] UI components
- [ ] Basic CRUD functionality

### Phase 4 Deliverables
- [ ] Approval workflow system
- [ ] Email notifications
- [ ] Template management system
- [ ] Status tracking

### Phase 5 Deliverables
- [ ] File upload system
- [ ] File security และ access control
- [ ] File versioning
- [ ] Storage management

### Phase 6 Deliverables
- [ ] Notification system
- [ ] Reporting dashboard
- [ ] Analytics และ metrics
- [ ] Export functionality

### Phase 7 Deliverables
- [ ] Complete test suite
- [ ] User documentation
- [ ] API documentation
- [ ] Deployment guide

## 🚀 การ Deploy และ Go-Live

### Pre-deployment Checklist
- [ ] ทดสอบระบบใน staging environment
- [ ] Training สำหรับ users
- [ ] Data migration plan
- [ ] Backup และ rollback plan
- [ ] Performance monitoring setup

### Go-Live Strategy
1. **Soft Launch** - เปิดให้ใช้กับ department เดียวก่อน
2. **Feedback Collection** - รวบรวม feedback และปรับปรุง
3. **Full Rollout** - เปิดให้ใช้ทั้งองค์กร
4. **Post-launch Support** - ติดตามและแก้ไขปัญหา

## 📞 การติดต่อและสนับสนุน

เมื่อพร้อมจะเริ่มพัฒนา ให้แจ้งว่าต้องการเริ่มจาก Phase ไหนก่อน
- ระยะเวลาประมาณการ: 4-6 สัปดาห์
- Resource ที่ต้องการ: Developer 1 คน, Tester 1 คน
- Technology Stack: Laravel, Filament, MySQL, Vue.js

---
*เอกสารนี้สร้างเมื่อ: 2025-09-09*
*อัพเดตล่าสุด: 2025-09-09*