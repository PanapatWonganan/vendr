# แผนพัฒนาระบบ TOR (Terms of Reference) + AI Assistant

## VENDR Procurement System - Innobic Asia / Nutrition / LL

> เอกสารนี้เป็น checklist สำหรับการ implement ระบบ TOR ที่สอดคล้องกับ architecture ปัจจุบัน
> ออกแบบจากการวิเคราะห์ codebase จริงเมื่อ 2026-03-07

---

## สารบัญ

- [Phase 1: Database & Models](#phase-1-database--models)
- [Phase 2: Filament Resource (CRUD)](#phase-2-filament-resource-crud)
- [Phase 3: Workflow & Approval](#phase-3-workflow--approval)
- [Phase 4: TOR to PR Conversion](#phase-4-tor--pr-conversion)
- [Phase 5: AI TOR Assistant](#phase-5-ai-tor-assistant)
- [Phase 6: Events & Notifications](#phase-6-events--notifications)
- [Phase 7: PDF Export](#phase-7-pdf-export)
- [Phase 8: Dashboard & Reports](#phase-8-dashboard--reports)
- [Phase 9: Testing & QA](#phase-9-testing--qa)

---

## Architecture Overview

### ตำแหน่งใน Flow

```
[TOR] --> [PR] --> [VA] --> [PO] --> [GR] --> [Payment]
  |         ^
  |         |
  +-- tor_id (FK)
```

### Design Principles

- TermsOfReference extends **BaseModel** (multi-database, auto company_id)
- ใช้ **ProcurementAttachment** (polymorphic) ไม่สร้าง attachment table แยก
- ใช้ **CommitteeMember** pattern เดิม (เพิ่ม tor_id)
- Revision chain ผ่าน **parent_tor_id** (pattern เดียวกับ ValueAnalysis)
- Fields ที่ map กับ PR: form_category, work_type, procurement_method, category, budget_code
- AI ใช้ **gpt-4o-mini** ผ่าน Http facade (pattern เดียวกับ VendorRiskAssessmentService)

---

## Phase 1: Database & Models

### 1.1 Migration: terms_of_references

- [x] สร้าง `database/migrations/xxxx_create_terms_of_references_table.php`
  - [x] id, tor_number (VARCHAR 50, UNIQUE, format TOR-YYYYMMDD-XXXX)
  - [x] company_id (BIGINT, FK companies)
  - [x] department_id (BIGINT, FK departments)
  - [x] title (VARCHAR 500, NOT NULL)
  - [x] project_name (VARCHAR 500, nullable)
  - [x] project_code (VARCHAR 100, nullable)
  - [x] tor_type ENUM: goods, services, construction, consulting
  - [x] form_category ENUM: act_based, law_based (สอดคล้อง PR)
  - [x] work_type ENUM: buy, hire, rent (สอดคล้อง PR)
  - [x] procurement_method VARCHAR: agreement_price, invitation_bid, open_bid, special_1, special_2, selection
  - [x] category VARCHAR (ใช้ enum เดียวกับ PR: premium_products, advertising_services, etc.)
  - [x] background TEXT nullable
  - [x] objectives TEXT nullable
  - [x] scope_of_work TEXT NOT NULL
  - [x] deliverables TEXT nullable
  - [x] specifications TEXT nullable
  - [x] qualification_requirements TEXT nullable
  - [x] evaluation_criteria JSON nullable (array ของเกณฑ์ + น้ำหนัก)
  - [x] payment_terms TEXT nullable
  - [x] warranty_requirements TEXT nullable
  - [x] penalty_clause TEXT nullable (เงื่อนไขเบี้ยปรับ)
  - [x] start_date DATE nullable
  - [x] end_date DATE nullable
  - [x] duration_days INT nullable
  - [x] budget_estimate DECIMAL(15,2) nullable
  - [x] currency VARCHAR(3) default 'THB'
  - [x] budget_code VARCHAR(100) nullable
  - [x] status ENUM: draft, submitted, reviewing, approved, rejected, amended, cancelled, expired (default draft)
  - [x] priority ENUM: low, medium, high, urgent (default medium)
  - [x] procurement_committee_id BIGINT nullable (FK users)
  - [x] inspection_committee_id BIGINT nullable (FK users)
  - [x] submitted_at TIMESTAMP nullable
  - [x] submitted_by BIGINT nullable (FK users)
  - [x] approved_at TIMESTAMP nullable
  - [x] approved_by BIGINT nullable (FK users)
  - [x] rejected_at TIMESTAMP nullable
  - [x] rejected_by BIGINT nullable (FK users)
  - [x] rejection_reason TEXT nullable
  - [x] revision_number INT default 0
  - [x] parent_tor_id BIGINT nullable (FK self, revision chain)
  - [x] amendment_reason TEXT nullable
  - [x] created_by BIGINT NOT NULL (FK users)
  - [x] updated_by BIGINT nullable (FK users)
  - [x] timestamps, soft deletes
  - [x] indexes: tor_number, status, department_id, company_id, parent_tor_id

### 1.2 Migration: tor_items

- [x] สร้าง `database/migrations/xxxx_create_tor_items_table.php`
  - [x] id, tor_id (FK terms_of_references, CASCADE)
  - [x] item_number INT (ลำดับรายการ)
  - [x] description TEXT NOT NULL
  - [x] specifications TEXT nullable
  - [x] quantity DECIMAL(10,2) NOT NULL
  - [x] unit_of_measure VARCHAR(50) nullable
  - [x] estimated_unit_price DECIMAL(15,2) nullable
  - [x] estimated_total_price DECIMAL(15,2) nullable
  - [x] delivery_location VARCHAR(500) nullable
  - [x] required_date DATE nullable
  - [x] remarks TEXT nullable
  - [x] timestamps
  - [x] index: tor_id

### 1.3 Migration: tor_approval_history

- [x] สร้าง `database/migrations/xxxx_create_tor_approval_history_table.php`
  - [x] id, tor_id (FK terms_of_references, CASCADE)
  - [x] action ENUM: submitted, reviewed, approved, rejected, returned, cancelled, amended
  - [x] performed_by BIGINT (FK users)
  - [x] performed_at TIMESTAMP
  - [x] from_status VARCHAR(50) nullable
  - [x] to_status VARCHAR(50) nullable
  - [x] comments TEXT nullable
  - [x] timestamps
  - [x] index: (tor_id, performed_at)

### 1.4 Migration: เพิ่ม tor_id ใน purchase_requisitions

- [x] สร้าง `database/migrations/xxxx_add_tor_id_to_purchase_requisitions.php`
  - [x] เพิ่ม tor_id BIGINT UNSIGNED nullable หลัง pr_number
  - [x] เพิ่ม index

### 1.5 Migration: เพิ่ม tor_id ใน committee_members

- [x] สร้าง `database/migrations/xxxx_add_tor_id_to_committee_members.php`
  - [x] เพิ่ม tor_id BIGINT UNSIGNED nullable
  - [x] เพิ่ม index

### 1.6 Run migrations

- [x] `php artisan migrate` (ทั้ง 3 company databases)
- [x] ตรวจสอบ tables สร้างถูกต้อง

### 1.7 Model: TermsOfReference

- [x] สร้าง `app/Models/TermsOfReference.php` extends **BaseModel**
  - [x] $fillable ทุก field
  - [x] $casts: dates, decimal, json (evaluation_criteria)
  - [x] SoftDeletes trait
  - [x] Relationships:
    - [x] belongsTo(Company)
    - [x] belongsTo(Department)
    - [x] belongsTo(User, 'created_by') as creator
    - [x] belongsTo(User, 'updated_by') as updater
    - [x] belongsTo(User, 'submitted_by') as submitter
    - [x] belongsTo(User, 'approved_by') as approver
    - [x] belongsTo(User, 'rejected_by') as rejector
    - [x] belongsTo(User, 'procurement_committee_id') as procurementCommittee
    - [x] belongsTo(User, 'inspection_committee_id') as inspectionCommittee
    - [x] hasMany(TorItem, 'tor_id') ordered by item_number
    - [x] hasMany(TorApprovalHistory, 'tor_id')
    - [x] hasMany(PurchaseRequisition, 'tor_id')
    - [x] hasMany(CommitteeMember, 'tor_id')
    - [x] morphMany(ProcurementAttachment, 'attachable')
    - [x] belongsTo(self, 'parent_tor_id') as parentTor
    - [x] hasMany(self, 'parent_tor_id') as revisions
  - [x] Scopes: approved(), pending(), reviewing(), byDepartment()
  - [x] Static: generateTorNumber() (format TOR-YYYYMMDD-XXXX)
  - [x] Methods: approve(User), reject(User, reason), submit(User), recordHistory()
  - [x] Methods: canBeEdited(), canBeSubmitted(), canBeApproved()
  - [x] Static: getTorTypeOptions(), getStatusOptions()
  - [x] Static: getCategoryOptions() (reuse PR categories)
  - [x] Static: getProcurementMethodOptions() (reuse PR methods)

### 1.8 Model: TorItem

- [x] สร้าง `app/Models/TorItem.php` extends Model (ไม่ต้อง BaseModel เพราะ FK ผ่าน tor_id)
  - [x] $fillable ทุก field
  - [x] belongsTo(TermsOfReference, 'tor_id')

### 1.9 Model: TorApprovalHistory

- [x] สร้าง `app/Models/TorApprovalHistory.php` extends Model
  - [x] $fillable ทุก field
  - [x] belongsTo(TermsOfReference, 'tor_id')
  - [x] belongsTo(User, 'performed_by') as performer

### 1.10 แก้ไข Models ที่มีอยู่

- [x] `app/Models/PurchaseRequisition.php`
  - [x] เพิ่ม 'tor_id' ใน $fillable
  - [x] เพิ่ม belongsTo(TermsOfReference, 'tor_id') as termsOfReference
- [x] `app/Models/CommitteeMember.php`
  - [x] เพิ่ม 'tor_id' ใน $fillable
  - [x] เพิ่ม belongsTo(TermsOfReference, 'tor_id')
- [x] `app/Models/ProcurementAttachment.php`
  - [x] เพิ่ม categories ใน getCategories(): tor_specification, tor_drawing, tor_reference, tor_template

---

## Phase 2: Filament Resource (CRUD)

### 2.1 Resource หลัก

- [x] สร้าง `app/Filament/Resources/TermsOfReferenceResource.php`
  - [x] $model = TermsOfReference
  - [x] $navigationIcon = 'heroicon-o-document-text'
  - [x] $navigationGroup = 'Procurement'
  - [x] $navigationLabel = 'TOR Management' / 'ขอบเขตงาน (TOR)'
  - [x] $navigationSort = 0 (ก่อน PR)
  - [x] getEloquentQuery() -> scoped by company_id

### 2.2 Form: Wizard 5 Steps

- [x] **Step 1: ข้อมูลพื้นฐาน**
  - [x] title (TextInput, required, maxLength 500)
  - [x] department_id (Select, relationship)
  - [x] tor_type (Select: goods/services/construction/consulting)
  - [x] form_category (Select: act_based/law_based)
  - [x] work_type (Select: buy/hire/rent)
  - [x] procurement_method (Select: 6 options จาก PR)
  - [x] category (Select: reuse PR categories)
  - [x] priority (Select: low/medium/high/urgent)
  - [x] project_name (TextInput, nullable)
  - [x] project_code (TextInput, nullable)

- [x] **Step 2: ขอบเขตงาน**
  - [x] ปุ่ม "AI ช่วยร่าง TOR" (Livewire component, Phase 5)
  - [x] background (RichEditor: ความเป็นมา/หลักการและเหตุผล)
  - [x] objectives (RichEditor: วัตถุประสงค์)
  - [x] scope_of_work (RichEditor: ขอบเขตของงาน, required)
  - [x] deliverables (RichEditor: ผลงานที่ต้องส่งมอบ)
  - [x] specifications (RichEditor: ข้อกำหนดทางเทคนิค)
  - [x] ปุ่ม "AI ปรับปรุง" ข้างแต่ละ field (Phase 5)

- [x] **Step 3: เงื่อนไขและคุณสมบัติ**
  - [x] qualification_requirements (RichEditor: คุณสมบัติผู้เสนอราคา)
  - [x] evaluation_criteria (Repeater: [{name, weight, description}])
  - [x] payment_terms (RichEditor: เงื่อนไขการจ่ายเงิน)
  - [x] warranty_requirements (RichEditor: การรับประกัน)
  - [x] penalty_clause (RichEditor: เงื่อนไขเบี้ยปรับ)

- [x] **Step 4: รายการและงบประมาณ**
  - [x] items (Repeater, relationship)
    - [x] item_number (auto increment)
    - [x] description (TextInput, required)
    - [x] specifications (Textarea)
    - [x] quantity (TextInput numeric, required)
    - [x] unit_of_measure (TextInput)
    - [x] estimated_unit_price (TextInput numeric, prefix ฿)
    - [x] estimated_total_price (calculated: qty x unit_price)
    - [x] delivery_location (TextInput)
    - [x] required_date (DatePicker)
  - [x] budget_estimate (TextInput numeric, prefix ฿, required)
  - [x] budget_code (TextInput)
  - [x] currency (Select, default THB)
  - [x] start_date (DatePicker)
  - [x] end_date (DatePicker)
  - [x] duration_days (TextInput numeric, auto-calculate จาก dates)

- [x] **Step 5: คณะกรรมการและเอกสาร**
  - [x] procurement_committee_id (Select, relationship users)
  - [x] inspection_committee_id (Select, relationship users)
  - [x] committee_members (Repeater หรือ relation manager)
  - [x] attachments (FileUpload, multiple, polymorphic)
  - [x] ปุ่ม "AI ตรวจสอบ TOR" (Phase 5)

### 2.3 Table (List View)

- [x] Columns:
  - [x] tor_number (searchable, sortable)
  - [x] title (searchable, limit 50)
  - [x] department.name
  - [x] tor_type (badge)
  - [x] procurement_method
  - [x] status (badge with colors)
  - [x] budget_estimate (money THB)
  - [x] priority (badge)
  - [x] creator.name
  - [x] created_at (date, toggleable)
- [x] Filters:
  - [x] status (SelectFilter)
  - [x] tor_type (SelectFilter)
  - [x] procurement_method (SelectFilter)
  - [x] department_id (SelectFilter)
  - [x] priority (SelectFilter)
  - [x] created_at (DateFilter: range)
- [x] Search: tor_number, title, project_name

### 2.4 Pages

- [x] สร้าง `Pages/ListTermsOfReferences.php`
- [x] สร้าง `Pages/CreateTermsOfReference.php`
  - [x] mutateFormDataBeforeCreate: set created_by, company_id, tor_number
- [x] สร้าง `Pages/EditTermsOfReference.php`
  - [x] visible เฉพาะ status = draft/rejected
  - [x] mutateFormDataBeforeSave: set updated_by
- [x] สร้าง `Pages/ViewTermsOfReference.php`
  - [x] แสดง infolist ทุก field
  - [x] แสดง approval history timeline
  - [x] แสดง related PRs (ถ้ามี)
  - [x] Header actions: submit, approve, reject, create_pr, amend

### 2.5 Access Control

- [x] canAccess(): roles admin, procurement_officer, procurement_manager, department_head, requester
- [x] canCreate(): same as canAccess
- [x] canEdit(): own draft/rejected TOR + procurement_manager + admin
- [x] canDelete(): own draft only + admin

---

## Phase 3: Workflow & Approval

### 3.1 Status Transitions

- [x] draft -> submitted (action: submit)
  - [x] ตรวจสอบ required fields ครบ
  - [x] set submitted_at, submitted_by
  - [x] recordHistory('submitted')
- [x] submitted -> reviewing (auto หรือ manual)
  - [x] recordHistory('reviewed')
- [x] reviewing -> approved (action: approve)
  - [x] set approved_at, approved_by
  - [x] recordHistory('approved')
- [x] reviewing -> rejected (action: reject)
  - [x] require rejection_reason
  - [x] set rejected_at, rejected_by, rejection_reason
  - [x] recordHistory('rejected')
- [x] rejected -> draft (action: return to draft for editing)
  - [x] recordHistory('returned')
- [x] approved -> amended (action: amend)
  - [x] สร้าง revision ใหม่ (copy TOR + items, set parent_tor_id, increment revision_number)
  - [x] set amendment_reason
  - [x] recordHistory('amended')
- [x] approved -> cancelled (action: cancel)
  - [x] recordHistory('cancelled')

### 3.2 Table Actions (ListTermsOfReferences)

- [x] Action: submit
  - [x] visible: status=draft, owner or admin/procurement_manager
  - [x] requiresConfirmation
  - [x] dispatch TorSubmitted event
- [x] Action: approve
  - [x] visible: status=submitted/reviewing, role=admin/procurement_manager/department_head
  - [x] requiresConfirmation with optional notes
  - [x] dispatch TorApproved event
- [x] Action: reject
  - [x] visible: status=submitted/reviewing, role=admin/procurement_manager/department_head
  - [x] require rejection_reason (textarea in modal)
  - [x] dispatch TorRejected event
- [x] Action: create_pr
  - [x] visible: status=approved
  - [x] redirect to PR create with tor_id parameter
- [x] Action: amend
  - [x] visible: status=approved, owner or admin/procurement_manager
  - [x] require amendment_reason
  - [x] create revision, redirect to edit

### 3.3 View Page Header Actions

- [x] เหมือน Table Actions แต่อยู่ใน ViewTermsOfReference page
- [x] เพิ่มปุ่ม export_pdf (Phase 7)

### 3.4 Approval Level Logic (ตามวงเงิน)

- [x] < 100,000 THB: Department Head อนุมัติได้เลย
- [x] 100,000 - 500,000 THB: Department Head + Procurement Manager
- [x] 500,000 - 2,000,000 THB: + Finance Director
- [x] > 2,000,000 THB: + Managing Director
- [x] ตรวจสอบ role + department ของ approver

---

## Phase 4: TOR -> PR Conversion

### 4.1 Conversion Logic

- [x] สร้าง method TermsOfReference::convertToPrData(): array
  - [x] Map fields:
    ```
    TOR.title              -> PR.title
    TOR.department_id      -> PR.department_id
    TOR.form_category      -> PR.form_category
    TOR.work_type          -> PR.work_type
    TOR.procurement_method -> PR.procurement_method
    TOR.category           -> PR.category
    TOR.budget_estimate    -> PR.procurement_budget
    TOR.budget_code        -> PR.budget_code
    TOR.scope_of_work      -> PR.description
    TOR.objectives         -> PR.purpose
    TOR.background         -> PR.justification
    TOR.start_date         -> PR.required_date
    TOR.end_date           -> PR.expected_delivery_date
    TOR.procurement_committee_id -> PR.procurement_committee_id
    TOR.inspection_committee_id  -> PR.inspection_committee_id
    TOR.id                 -> PR.tor_id
    ```
  - [x] Map TOR items -> PR items:
    ```
    TorItem.description          -> PrItem.description
    TorItem.specifications       -> PrItem.specification
    TorItem.quantity             -> PrItem.quantity
    TorItem.unit_of_measure      -> PrItem.unit_of_measure
    TorItem.estimated_unit_price -> PrItem.estimated_unit_price
    TorItem.estimated_total_price -> PrItem.estimated_amount
    TorItem.required_date        -> PrItem.required_date
    TorItem.remarks              -> PrItem.remarks
    ```

### 4.2 แก้ไข PurchaseRequisitionResource

- [x] CreatePurchaseRequisition: รับ query parameter `tor_id`
  - [x] ถ้ามี tor_id: auto-fill form data จาก TOR
  - [x] แสดง info banner "สร้างจาก TOR: TOR-XXXXXXXX-XXXX"
- [x] Form: เพิ่ม Select `tor_id` (optional, เฉพาะ approved TOR)
  - [x] เมื่อเลือก TOR: auto-fill fields (reactive)

### 4.3 แสดง TOR link ใน PR View

- [x] ViewPurchaseRequisition: แสดง TOR reference (ถ้ามี tor_id)
  - [x] link ไปหน้า TOR view

---

## Phase 5: AI TOR Assistant

### 5.1 Service: TorAiService

- [x] สร้าง `app/Services/TorAiService.php`
  - [x] constructor: apiKey, model จาก config('services.openai')
  - [x] Pattern เดียวกับ VendorRiskAssessmentService:
    - [x] Http::timeout(60)->withToken()->post()
    - [x] temperature: 0.3
    - [x] response_format: json_object (สำหรับ draft/review)
    - [x] Fallback: rule-based/template เมื่อ API ล้ม

### 5.2 Method: generateDraft(array $basicInfo): array

- [x] Input: title, tor_type, work_type, procurement_method, budget_estimate, department, category
- [x] System Prompt: ผู้เชี่ยวชาญเขียน TOR ตามกฎหมายไทย
  - [x] อ้างอิง พ.ร.บ. จัดซื้อจัดจ้าง 2560
  - [x] ระเบียบกระทรวงการคลัง ข้อ 21
  - [x] หลัก SMART
  - [x] ห้ามล็อคสเปค ต้องอ้าง มอก./มาตรฐานสากล
  - [x] แบ่งเนื้อหาตาม tor_type (goods/services/construction/consulting)
  - [x] ปรับรายละเอียดตาม procurement_method และ budget
- [x] Output JSON:
  ```json
  {
    "background": "...",
    "objectives": "...",
    "scope_of_work": "...",
    "deliverables": "...",
    "specifications": "...",
    "qualification_requirements": "...",
    "evaluation_criteria": [{"name":"...","weight":40,"description":"..."}],
    "payment_terms": "...",
    "warranty_requirements": "...",
    "penalty_clause": "...",
    "suggested_duration_days": 90,
    "suggested_items": [{"description":"...","quantity":1,"unit":"งาน"}]
  }
  ```
- [x] Fallback: return template ตาม tor_type (hardcoded Thai templates)

### 5.3 Method: reviewTor(TermsOfReference $tor): array

- [x] Input: TOR ทุก field + items
- [x] System Prompt: ผู้ตรวจสอบ TOR ตามระเบียบ
  - [x] ตรวจจับ "ล็อคสเปค" (ระบุยี่ห้อ, คุณลักษณะเจาะจงเกินไป)
  - [x] ตรวจหลัก SMART (วัดผลได้? มีกรอบเวลา? เป็นไปได้จริง?)
  - [x] ตรวจองค์ประกอบครบตามระเบียบ
  - [x] ตรวจความสอดคล้องของ budget กับ scope
  - [x] ตรวจคุณสมบัติผู้เสนอราคาตาม ม.64
- [x] Output JSON:
  ```json
  {
    "score": 85,
    "grade": "B",
    "issues": [
      {"field": "specifications", "severity": "warning", "message": "...", "suggestion": "..."}
    ],
    "passed": [
      {"field": "scope_of_work", "message": "ขอบเขตงานชัดเจน ครบถ้วน"}
    ],
    "summary": "TOR นี้มีคุณภาพดี แต่ควรแก้ไข 2 จุด..."
  }
  ```
- [x] Fallback: rule-based checklist
  - [x] ตรวจ field ว่าง/ไม่ว่าง
  - [x] ตรวจ budget > 0
  - [x] ตรวจ evaluation_criteria มีอย่างน้อย 1 เกณฑ์
  - [x] ตรวจ items มีอย่างน้อย 1 รายการ

### 5.4 Method: improveField(string $field, string $content, array $context): string

- [x] Input: field name, เนื้อหาปัจจุบัน, context (tor_type, budget, title)
- [x] System Prompt: ปรับปรุงเนื้อหาตามหลัก SMART ไม่ล็อคสเปค
- [x] Output: เนื้อหาที่ปรับปรุงแล้ว (plain text/HTML)
- [x] Fallback: return เนื้อหาเดิม

### 5.5 Livewire Component: TorAiDraft

- [x] สร้าง `app/Livewire/TorAiDraft.php`
  - [x] รับ props: form data จาก Step 1
  - [x] method: generate() -> เรียก TorAiService::generateDraft()
  - [x] dispatch event กลับไป fill form fields
  - [x] แสดง loading state ระหว่างรอ AI
  - [x] แสดง error message ถ้า fail
- [x] สร้าง `resources/views/livewire/tor-ai-draft.blade.php`
  - [x] ปุ่ม "AI ช่วยร่าง TOR"
  - [x] loading spinner
  - [x] success/error notification

### 5.6 Livewire Component: TorAiReview

- [x] สร้าง `app/Livewire/TorAiReview.php`
  - [x] รับ props: TOR id หรือ form data ทั้งหมด
  - [x] method: review() -> เรียก TorAiService::reviewTor()
  - [x] แสดงผลเป็น card: score, issues, passed
  - [x] กดที่ issue -> highlight field ที่มีปัญหา
- [x] สร้าง `resources/views/livewire/tor-ai-review.blade.php`
  - [x] ปุ่ม "AI ตรวจสอบ TOR"
  - [x] Score display (วงกลม/progress bar)
  - [x] Issues list (warning/error icons)
  - [x] Passed list (check icons)

### 5.7 Livewire Component: TorAiImprove

- [x] สร้าง `app/Livewire/TorAiImprove.php`
  - [x] รับ props: field name, current content, context
  - [x] method: improve() -> เรียก TorAiService::improveField()
  - [x] แสดง diff (เดิม vs ใหม่) ให้ user เลือก
  - [x] ปุ่ม "ใช้ข้อความใหม่" / "ยกเลิก"
- [x] สร้าง `resources/views/livewire/tor-ai-improve.blade.php`
  - [x] ปุ่ม icon ข้าง field
  - [x] Modal แสดงข้อความเดิม vs ข้อความใหม่

### 5.8 ผูก Components เข้า Filament Form

- [x] แทรก TorAiDraft component ที่ต้น Step 2
- [x] แทรก TorAiImprove ข้างทุก RichEditor field ใน Step 2-3
- [x] แทรก TorAiReview component ที่ Step 5 (+ ViewTermsOfReference infolist)

---

## Phase 6: Events & Notifications

### 6.1 Events

- [x] สร้าง `app/Events/TorSubmitted.php`
  - [x] properties: $tor, $submitter
  - [x] implements ShouldQueue (ถ้าต้องการ)
- [x] สร้าง `app/Events/TorApproved.php`
  - [x] properties: $torId, $approverId, $connectionName, $companyId (pattern เดียวกับ PO)
- [x] สร้าง `app/Events/TorRejected.php`
  - [x] properties: $torId, $rejectorId, $connectionName, $companyId, $reason

### 6.2 Listeners

- [x] สร้าง `app/Listeners/SendTorSubmittedNotification.php`
  - [x] Recipients: procurement_committee + procurement_manager ทุกคน
  - [x] Channel: Email + Telegram
  - [x] Cache duplicate prevention (5 min TTL)
  - [x] Pattern เดียวกับ SendPurchaseRequisitionApprovedNotification
- [x] สร้าง `app/Listeners/SendTorApprovedNotification.php`
  - [x] Recipients: TOR creator + department head
  - [x] Channel: Email + Telegram
  - [x] เนื้อหา: TOR อนุมัติแล้ว สามารถสร้าง PR ได้
- [x] สร้าง `app/Listeners/SendTorRejectedNotification.php`
  - [x] Recipients: TOR creator
  - [x] Channel: Email + Telegram
  - [x] เนื้อหา: TOR ถูกปฏิเสธ พร้อมเหตุผล

### 6.3 Register Events

- [x] แก้ไข `app/Providers/EventServiceProvider.php`
  - [x] Event::listen(TorSubmitted::class, SendTorSubmittedNotification::class)
  - [x] Event::listen(TorApproved::class, SendTorApprovedNotification::class)
  - [x] Event::listen(TorRejected::class, SendTorRejectedNotification::class)

### 6.4 Email Templates

- [x] สร้าง `resources/views/emails/tor-submitted.blade.php`
- [x] สร้าง `resources/views/emails/tor-approved.blade.php`
- [x] สร้าง `resources/views/emails/tor-rejected.blade.php`
- [x] สร้าง Mailable classes: `TorSubmittedMail`, `TorApprovedMail`, `TorRejectedMail`
- [x] อัปเดต Listeners ใช้ Mailable แทน Mail::raw()

### 6.5 Telegram Integration

- [x] เพิ่ม TOR commands ใน TelegramBotService
  - [x] /tor_pending — แสดง TOR ที่รอพิจารณา
  - [x] /tor_approved — แสดง TOR ที่อนุมัติแล้วยังไม่สร้าง PR
  - [x] notifyTorSubmitted() — แจ้ง approvers ผ่าน Telegram
  - [x] notifyTorApproved() — แจ้ง creator ผ่าน Telegram
  - [x] notifyTorRejected() — แจ้ง creator พร้อมเหตุผลผ่าน Telegram
  - [x] sendToApprovers() + getTorApprovers() helper methods

---

## Phase 7: PDF Export

### 7.1 PDF Service

- [x] สร้าง `app/Services/TorPdfService.php`
  - [x] Pattern เดียวกับ PurchaseOrderPdfService
  - [x] method: generatePdf(TermsOfReference $tor): string (path)
  - [x] method: generateFilename(TermsOfReference $tor): string
  - [x] ใช้ DOMPDF หรือ MPDF (เหมือนระบบเดิม)
  - [x] รองรับภาษาไทย (Sarabun font)

### 7.2 PDF Template

- [x] สร้าง `resources/views/pdf/tor.blade.php`
  - [x] หัวกระดาษ: logo บริษัท, เลข TOR, วันที่
  - [x] ข้อมูลพื้นฐาน: ชื่อ, ประเภท, วิธีจัดซื้อ, แผนก
  - [x] เนื้อหา TOR: ความเป็นมา, วัตถุประสงค์, ขอบเขตงาน, etc.
  - [x] ตารางรายการ: items + ราคาประมาณ
  - [x] เกณฑ์ประเมิน: ตารางเกณฑ์ + น้ำหนัก
  - [x] เงื่อนไข: การจ่ายเงิน, รับประกัน, เบี้ยปรับ
  - [x] ท้ายกระดาษ: ลายเซ็นผู้จัดทำ, ผู้อนุมัติ

### 7.3 Export Action

- [x] เพิ่ม action 'export_pdf' ใน Table Actions และ View Page
  - [x] เรียก TorPdfService::generatePdf()
  - [x] return response download

---

## Phase 8: Dashboard & Reports

### 8.1 Dashboard Widget

- [x] สร้าง `app/Filament/Widgets/TorStatsWidget.php`
  - [x] Stat: TOR ทั้งหมด (เดือนนี้)
  - [x] Stat: รอพิจารณา
  - [x] Stat: อนุมัติแล้ว (ยังไม่สร้าง PR)
  - [x] Stat: มูลค่ารวม TOR ที่อนุมัติ
  - [x] Chart: TOR by status (doughnut chart) — `TorStatusChart.php`
  - [x] Chart: TOR by department (stacked bar chart) — `TorDepartmentChart.php`

### 8.2 SLA Integration

- [x] แก้ไข `app/Services/SlaService.php`
  - [x] เพิ่ม SLA_STANDARDS: selection => 20
  - [x] เพิ่ม stage: tor_submission_to_approval
  - [x] method: trackTorSubmissionToApproval(TermsOfReference $tor)
- [x] แก้ไข `app/Models/SlaTracking.php`
  - [x] เพิ่ม tor_id nullable + termsOfReference() relationship
  - [x] เพิ่ม stage name 'tor_submission_to_approval'
- [x] สร้าง migration `add_tor_id_to_sla_trackings`

### 8.3 Navigation Item

- [x] เพิ่ม badge count ที่ navigation "TOR Management"
  - [x] แสดงจำนวน TOR ที่รอพิจารณา (สำหรับ approver)

---

## Phase 9: Testing & QA

### 9.1 Unit Tests (`tests/Unit/TorTest.php` — 13 tests)

- [x] test: TOR number generation (format TOR-YYYYMMDD-XXXX)
- [x] test: Option methods (torType, status, priority)
- [x] test: canBeEdited/canBeSubmitted/canBeApproved logic
- [x] test: isRevision() + revision_label
- [x] test: status_text + tor_type_label attributes
- [x] test: TOR -> PR field mapping (convertToPrData)
- [x] test: All required classes exist (21 classes)

### 9.2 Feature Tests (`tests/Feature/TorFeatureTest.php` — 8 tests)

- [x] test: SLA working day calculation
- [x] test: SLA weekend exclusion
- [x] test: SLA grade calculation (S/A/B/C/D/F)
- [x] test: SLA standard days per procurement method
- [x] test: AI fallback draft returns expected fields
- [x] test: AI fallback draft for each TOR type
- [x] test: AI fallback evaluation criteria sums to 100
- [x] test: AI improve field returns original when empty

### 9.3 AI Tests (included in Feature Tests above)

- [x] test: TorAiService::generateDraft() returns valid structure (fallback)
- [x] test: TorAiService::generateDraft() works for all 4 types
- [x] test: TorAiService::improveField() returns original for empty input
- [x] test: Fallback works when API key missing (tested via fallback path)

### 9.4 Manual QA Checklist

- [ ] สร้าง TOR ใหม่ผ่าน Wizard ครบ 5 steps
- [ ] AI Draft: กดปุ่มแล้ว fill fields ถูกต้อง
- [ ] AI Review: ตรวจแล้วแสดง score + issues
- [ ] AI Improve: ปรับปรุง field แล้วแสดง diff
- [ ] Submit -> Notification ส่งถึง approver (Email + Telegram)
- [ ] Approve -> Notification ส่งถึง creator
- [ ] Reject -> Notification ส่งถึง creator พร้อมเหตุผล
- [ ] Create PR from TOR -> fields map ถูกต้อง
- [ ] Export PDF -> เปิดได้ ภาษาไทยไม่เพี้ยน
- [ ] Amendment -> revision ใหม่สร้างถูกต้อง
- [ ] Company isolation -> เปลี่ยนบริษัท แล้ว TOR ของอีกบริษัทไม่แสดง

---

## ลำดับการ Implement ที่แนะนำ

```
Phase 1 (Database & Models)     ← ทำก่อนเลย เป็นฐาน
    |
Phase 2 (Filament CRUD)         ← ทำต่อ ให้มีหน้าจอใช้งานได้
    |
Phase 3 (Workflow & Approval)   ← เพิ่ม approval flow
    |
Phase 4 (TOR -> PR)             ← เชื่อม PR
    |
Phase 5 (AI Assistant)          ← เพิ่ม AI ช่วยเขียน
    |
Phase 6 (Notifications)         ← Email + Telegram
    |
Phase 7 (PDF Export)            ← Export document
    |
Phase 8 (Dashboard)             ← Reports + widgets
    |
Phase 9 (Testing)               ← ทำคู่กันทุก phase ถ้าเป็นไปได้
```

---

## ไฟล์ทั้งหมดที่ต้องสร้าง/แก้ไข

### สร้างใหม่ (38 ไฟล์)

| # | ไฟล์ | Phase |
|---|------|-------|
| 1 | `database/migrations/xxxx_create_terms_of_references_table.php` | 1 |
| 2 | `database/migrations/xxxx_create_tor_items_table.php` | 1 |
| 3 | `database/migrations/xxxx_create_tor_approval_history_table.php` | 1 |
| 4 | `database/migrations/xxxx_add_tor_id_to_purchase_requisitions.php` | 1 |
| 5 | `database/migrations/xxxx_add_tor_id_to_committee_members.php` | 1 |
| 6 | `app/Models/TermsOfReference.php` | 1 |
| 7 | `app/Models/TorItem.php` | 1 |
| 8 | `app/Models/TorApprovalHistory.php` | 1 |
| 9 | `app/Filament/Resources/TermsOfReferenceResource.php` | 2 |
| 10 | `app/Filament/Resources/TermsOfReferenceResource/Pages/ListTermsOfReferences.php` | 2 |
| 11 | `app/Filament/Resources/TermsOfReferenceResource/Pages/CreateTermsOfReference.php` | 2 |
| 12 | `app/Filament/Resources/TermsOfReferenceResource/Pages/EditTermsOfReference.php` | 2 |
| 13 | `app/Filament/Resources/TermsOfReferenceResource/Pages/ViewTermsOfReference.php` | 2 |
| 14 | `app/Services/TorAiService.php` | 5 |
| 15 | `app/Livewire/TorAiDraft.php` | 5 |
| 16 | `app/Livewire/TorAiReview.php` | 5 |
| 17 | `app/Livewire/TorAiImprove.php` | 5 |
| 18 | `resources/views/livewire/tor-ai-draft.blade.php` | 5 |
| 19 | `resources/views/livewire/tor-ai-review.blade.php` | 5 |
| 20 | `resources/views/livewire/tor-ai-improve.blade.php` | 5 |
| 21 | `resources/views/livewire/tor-ai-review-infolist.blade.php` | 5 |
| 22 | `app/Events/TorSubmitted.php` | 6 |
| 23 | `app/Events/TorApproved.php` | 6 |
| 24 | `app/Events/TorRejected.php` | 6 |
| 25 | `app/Listeners/SendTorSubmittedNotification.php` | 6 |
| 26 | `app/Listeners/SendTorApprovedNotification.php` | 6 |
| 27 | `app/Listeners/SendTorRejectedNotification.php` | 6 |
| 28 | `app/Mail/TorSubmittedMail.php` | 6 |
| 29 | `app/Mail/TorApprovedMail.php` | 6 |
| 30 | `app/Mail/TorRejectedMail.php` | 6 |
| 31 | `resources/views/emails/tor-submitted.blade.php` | 6 |
| 32 | `resources/views/emails/tor-approved.blade.php` | 6 |
| 33 | `resources/views/emails/tor-rejected.blade.php` | 6 |
| 34 | `app/Services/TorPdfService.php` | 7 |
| 35 | `resources/views/pdf/tor.blade.php` | 7 |
| 36 | `app/Filament/Widgets/TorStatsWidget.php` | 8 |
| 37 | `app/Filament/Widgets/TorStatusChart.php` | 8 |
| 38 | `app/Filament/Widgets/TorDepartmentChart.php` | 8 |
| 39 | `database/migrations/2026_03_10_100000_add_tor_id_to_sla_trackings.php` | 8 |
| 40 | `tests/Unit/TorTest.php` | 9 |
| 41 | `tests/Feature/TorFeatureTest.php` | 9 |

### แก้ไข (10 ไฟล์)

| # | ไฟล์ | Phase |
|---|------|-------|
| 1 | `app/Models/PurchaseRequisition.php` — เพิ่ม tor_id, relationship | 1 |
| 2 | `app/Models/CommitteeMember.php` — เพิ่ม tor_id, relationship | 1 |
| 3 | `app/Models/ProcurementAttachment.php` — เพิ่ม TOR categories | 1 |
| 4 | `app/Filament/Resources/PurchaseRequisitionResource.php` — TOR selector | 4 |
| 5 | `app/Providers/EventServiceProvider.php` — register TOR events | 6 |
| 6 | `app/Services/TelegramBotService.php` — TOR commands + notify methods | 6 |
| 7 | `app/Services/SlaService.php` — trackTorSubmissionToApproval() | 8 |
| 8 | `app/Models/SlaTracking.php` — tor_id, relationship, stage name | 8 |
| 9 | `app/Providers/Filament/AdminPanelProvider.php` — register TOR widgets | 8 |
| 10 | `app/Filament/Resources/TermsOfReferenceResource/Pages/ViewTermsOfReference.php` — AI Review section | 5 |
