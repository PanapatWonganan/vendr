<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * V3 template generator — matches the current DB schema and pairs with
 * procurement:import-excel-v3 (single-shot import, no --force dance).
 *
 * Changes from the V2 template:
 *  - PR sheet: adds purpose, budget_code, project_code, received_date (SLA
 *    "received → PO approved" stage); drops clause_number / negotiated_price
 *    which were never persisted.
 *  - GR sheet: adds milestone_percentage override; received_amount is now
 *    stored in goods_receipts.milestone_amount (not buried in notes).
 *  - PaymentMilestones sheet is fully optional — the importer auto-generates
 *    milestones from GR rows when a PO has none.
 *  - GR status list matches the actual DB enum (no pending_review).
 */
class GenerateImportTemplateV3 extends Command
{
    protected $signature = 'procurement:generate-import-template-v3
                            {--company= : Company code filter for Lookups sheet (optional)}
                            {--out= : Output file path (defaults to storage/app)}';

    protected $description = 'V3: Generate the one-shot Excel import template (current schema) for procurement:import-excel-v3';

    private const PR_STATUSES = [
        'draft', 'pending_approval', 'approved', 'rejected', 'completed', 'cancelled',
    ];

    private const PO_STATUSES = [
        'draft', 'pending_approval', 'approved', 'rejected', 'sent_to_supplier',
        'acknowledged', 'partially_received', 'fully_received', 'closed', 'cancelled',
    ];

    // Must match the goods_receipts.status DB enum (pending_review is NOT in the enum).
    private const GR_STATUSES = [
        'draft', 'completed', 'returned', 'partially_returned', 'cancelled',
    ];

    private const INSPECTION_STATUSES = ['pending', 'passed', 'failed', 'partial'];

    private const MILESTONE_STATUSES = ['pending', 'due', 'paid', 'overdue', 'cancelled'];

    private const VENDOR_STATUSES = ['pending', 'approved', 'rejected', 'suspended'];

    private const PROCUREMENT_METHODS = [
        'agreement_price', 'invitation_bid', 'open_bid', 'special_1', 'special_2', 'selection',
    ];

    private const CATEGORIES = [
        'premium_products', 'advertising_services', 'space_rental', 'general_services',
        'construction', 'assets', 'technology_software', 'pharmaceuticals',
        'medical_devices', 'medical_food', 'supplements',
    ];

    private const WORK_TYPES = ['buy', 'hire', 'rent'];

    private const FORM_CATEGORIES = ['act_based', 'law_based'];

    private const CURRENCIES = ['THB', 'USD', 'EUR', 'JPY', 'CNY'];

    private const COMMITTEE_ROLES = ['procurement', 'inspection', 'approver'];

    public function handle(): int
    {
        $companyCode = $this->option('company');
        $outPath = $this->option('out')
            ?: storage_path('app/procurement_import_template_v3_'.now()->format('Ymd_His').'.xlsx');

        $this->info('Generating procurement import template V3...');

        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        $this->buildInstructionsSheet($spreadsheet);
        $this->buildLookupsSheet($spreadsheet, $companyCode);
        $this->buildVendorsSheet($spreadsheet);
        $this->buildCommitteesSheet($spreadsheet);
        $this->buildPurchaseRequisitionsSheet($spreadsheet);
        $this->buildPurchaseOrdersSheet($spreadsheet);
        $this->buildGoodsReceiptsSheet($spreadsheet);
        $this->buildPaymentMilestonesSheet($spreadsheet);

        $spreadsheet->setActiveSheetIndexByName('_Instructions');

        $writer = new Xlsx($spreadsheet);
        $writer->save($outPath);

        $this->info("Template generated: {$outPath}");

        return self::SUCCESS;
    }

    // --------------------------------------------------------------
    // Sheet builders
    // --------------------------------------------------------------

    private function buildInstructionsSheet(Spreadsheet $wb): void
    {
        $sheet = $wb->createSheet();
        $sheet->setTitle('_Instructions');

        $row = 1;

        // --- Title ---
        $this->insTitle($sheet, $row++, 'VENDR - คู่มือการกรอก Template นำเข้าข้อมูลจัดซื้อจัดจ้าง (V3)');
        $this->insLine($sheet, $row++, '', 'เวอร์ชัน 3.0 | สำหรับ import ข้อมูล PR/PO/GR ย้อนหลัง แบบ "คำสั่งเดียวจบ" (one-shot import)');
        $row++;

        // --- 1. Overview ---
        $this->insSection($sheet, $row++, '1. ภาพรวม (Overview)');
        $this->insLine($sheet, $row++, 'วัตถุประสงค์', 'ใช้สำหรับนำเข้าข้อมูลใบขอซื้อ (PR) / ใบสั่งซื้อ (PO) / ใบตรวจรับ (GR) / งวดชำระเงิน ย้อนหลัง เข้าระบบ VENDR ในครั้งเดียว');
        $this->insLine($sheet, $row++, 'One-shot import', 'V3 รันคำสั่งเดียวจบ: ระบบตรวจสอบทั้งไฟล์ → ถ้าไม่มี error จะบันทึกทันที + คำนวณ SLA ให้อัตโนมัติ | ถ้ามี error แม้แต่จุดเดียว จะไม่บันทึกอะไรเลย (all-or-nothing) และแจ้งรายการ error ให้แก้');
        $this->insLine($sheet, $row++, 'Auto-create user', 'อีเมลผู้ขอ/ผู้อนุมัติ/กรรมการ ที่ยังไม่มี user ในระบบ → ระบบสร้าง user ให้อัตโนมัติ (สุ่มรหัสผ่าน) ไม่ต้องแจ้ง IT ล่วงหน้า');
        $this->insLine($sheet, $row++, 'Replace existing', 'ถ้า pr_number / po_number ซ้ำกับข้อมูลเดิมในระบบ → ระบบลบของเดิมแล้วสร้างใหม่จากไฟล์นี้ (ไฟล์นี้คือ source of truth)');
        $this->insLine($sheet, $row++, 'Historical flag', 'ข้อมูลที่ import จะถูก tag ว่า "Imported from Excel" — ข้าม workflow อนุมัติและไม่ส่ง notification');
        $row++;

        // --- 2. Workbook structure ---
        $this->insSection($sheet, $row++, '2. โครงสร้างไฟล์ Template');
        $this->insLine($sheet, $row++, '_Instructions', 'คู่มือ (sheet นี้)');
        $this->insLine($sheet, $row++, '_Lookups', 'แสดง code/id ของ Company, Department, User และรายการค่า enum ทั้งหมด (อ่านอย่างเดียว)');
        $this->insLine($sheet, $row++, 'Vendors', 'ข้อมูลผู้ขาย/ผู้รับจ้าง (กรอกครั้งเดียว ใช้ reference ใน PO)');
        $this->insLine($sheet, $row++, 'Committees', 'ข้อมูลกรรมการ/ผู้อนุมัติ (กรอกครั้งเดียว ใช้ reference ใน PR/PO)');
        $this->insLine($sheet, $row++, 'PurchaseRequisitions', 'ใบขอซื้อ (PR) - 1 แถว = 1 PR');
        $this->insLine($sheet, $row++, 'PurchaseOrders', 'ใบสั่งซื้อ (PO) - 1 แถว = 1 PO');
        $this->insLine($sheet, $row++, 'GoodsReceipts', 'ใบตรวจรับ (GR) - 1 แถว = 1 งวดตรวจรับ (ถ้ามีหลายงวด ใส่หลายแถว)');
        $this->insLine($sheet, $row++, 'PaymentMilestones', 'งวดการชำระเงิน (OPTIONAL ทั้ง sheet - ถ้าเว้นว่าง ระบบสร้างงวดจาก GR ให้อัตโนมัติ พร้อมชื่องวด "งวดที่ n/m")');
        $row++;

        // --- 3. What's new in V3 ---
        $this->insSection($sheet, $row++, '3. ของใหม่ใน V3 (เทียบกับ template เดิม)');
        $this->insLine($sheet, $row++, 'PR: purpose', 'วัตถุประสงค์การจัดซื้อ - แนะนำให้กรอกทุกแถว (ใช้แสดงในรายงาน)');
        $this->insLine($sheet, $row++, 'PR: budget_code', 'รหัสงบประมาณ - แนะนำให้กรอกถ้ามี');
        $this->insLine($sheet, $row++, 'PR: project_code', 'รหัสโครงการ (ถ้ามี)');
        $this->insLine($sheet, $row++, 'PR: received_date', 'วันที่ฝ่ายจัดซื้อรับเรื่อง - ใช้คำนวณ SLA stage "รับเรื่อง → PO อนุมัติ" (แบบวันปฏิทิน)');
        $this->insLine($sheet, $row++, 'GR: milestone_percentage', 'เปอร์เซ็นต์งวด กรอกเองได้ (override) - ถ้าเว้นว่าง ระบบคำนวณจาก received_amount / ยอด PO');
        $this->insLine($sheet, $row++, 'GR: received_amount', 'V3 เก็บลง DB จริง (คอลัมน์ milestone_amount) ไม่ได้ฝังใน notes แบบเดิม - ใช้แสดงยอดรับต่องวดได้');
        $this->insLine($sheet, $row++, 'PaymentMilestones auto', 'ไม่ต้องกรอก sheet นี้ก็ได้ - PO ไหนไม่มีงวดในไฟล์ ระบบสร้างจาก GR ให้ (1 GR = 1 งวด)');
        $this->insLine($sheet, $row++, 'ตัดคอลัมน์ที่ไม่ใช้', 'clause_number, negotiated_price ถูกตัดออก (template เดิมมีช่องแต่ระบบไม่เคยบันทึก)');
        $row++;

        // --- 4. Step-by-step ---
        $this->insSection($sheet, $row++, '4. ขั้นตอนการกรอก (Step-by-Step)');
        $this->insLine($sheet, $row++, 'Step 1: Vendors', 'รวบรวมรายชื่อผู้ขายทั้งหมดที่มีในข้อมูลเดิม → กรอกในชีต Vendors → ตั้ง vendor_code ของเราเอง (เช่น V001, V002)');
        $this->insLine($sheet, $row++, 'Step 2: Committees', 'รวบรวมชื่อกรรมการจัดหา/ตรวจรับ/ผู้อนุมัติ → กรอกในชีต Committees → ตั้ง committee_code (เช่น C001, C002) | ไม่มี user ในระบบก็กรอกได้เลย ระบบสร้างให้');
        $this->insLine($sheet, $row++, 'Step 3: PurchaseRequisitions', 'กรอก PR ทั้งหมด - อ้างอิง committee_code จาก step 2 - อ้างอิง company_id / department_id จาก _Lookups');
        $this->insLine($sheet, $row++, 'Step 4: PurchaseOrders', 'กรอก PO ทั้งหมด - อ้างอิง pr_number จาก step 3 - อ้างอิง vendor_code จาก step 1');
        $this->insLine($sheet, $row++, 'Step 5: GoodsReceipts', 'กรอกใบตรวจรับแต่ละงวด - อ้างอิง po_number จาก step 4 - งวดเดียว = 1 แถว, หลายงวด = หลายแถว');
        $this->insLine($sheet, $row++, 'Step 6: PaymentMilestones', '(ข้ามได้) กรอกเฉพาะกรณีต้องการกำหนดงวดจ่ายเอง - ถ้าข้าม ระบบสร้างจาก GR ให้อัตโนมัติ');
        $this->insLine($sheet, $row++, 'Step 7: ตรวจสอบ', 'ใช้ checklist ในข้อ 8 ก่อนส่งไฟล์');
        $this->insLine($sheet, $row++, 'Step 8: ลบ example', 'ลบแถวตัวอย่าง (สีเทา) ทุก sheet ก่อน save');
        $this->insLine($sheet, $row++, 'Step 9: Import', 'ส่งไฟล์ให้ IT รันคำสั่งเดียว: php artisan procurement:import-excel-v3 {ไฟล์} - จบ (ระบบ validate + import + คำนวณ SLA ในครั้งเดียว)');
        $row++;

        // --- 5. Legend / Color code ---
        $this->insSection($sheet, $row++, '5. สัญลักษณ์ & สี (Legend)');
        $this->insLine($sheet, $row++, 'REQ (หัว column สีแดง)', 'Required = ต้องกรอก ห้ามเว้นว่าง');
        $legendReq = $row - 1;
        $this->insLine($sheet, $row++, 'OPT (หัว column สีเหลือง)', 'Optional = กรอกถ้ามีข้อมูล ไม่มีให้เว้นว่าง');
        $legendOpt = $row - 1;
        $this->insLine($sheet, $row++, 'แถวที่ 3 (italic เล็ก)', 'คำอธิบาย column');
        $this->insLine($sheet, $row++, 'แถวสีเทา (แถวที่ 4)', 'EXAMPLE = ตัวอย่าง ลบออกก่อน import');
        $legendExample = $row - 1;
        $this->insLine($sheet, $row++, 'Dropdown (ลูกศรลง)', 'column ที่เป็น enum - เลือกจากรายการ ห้ามพิมพ์เอง');
        $row++;

        // --- 6. Data format rules ---
        $this->insSection($sheet, $row++, '6. รูปแบบข้อมูล (Data Format)');
        $this->insLine($sheet, $row++, 'วันที่ (Date)', 'ใช้ YYYY-MM-DD เท่านั้น เช่น 2025-04-17 | ห้ามใช้ 17/4/2025, Apr 17 2025 | ปี ค.ศ. เท่านั้น ห้ามใช้ พ.ศ. (2568)');
        $this->insLine($sheet, $row++, '   วิธีตั้งใน Excel', 'คลุม column วันที่ → Format Cells → Custom → พิมพ์ yyyy-mm-dd → OK');
        $this->insLine($sheet, $row++, 'ตัวเลข (Number)', 'ห้ามใส่ comma เช่น 36000 (ไม่ใช่ 36,000) | ทศนิยมใช้จุด (.) เช่น 2860.50');
        $this->insLine($sheet, $row++, 'ช่องว่าง (Empty)', 'Optional ที่ไม่มีข้อมูล = เว้นว่าง ห้ามใส่ขีด (-) N/A ไม่มี หรือ ว่าง');
        $this->insLine($sheet, $row++, 'Email', 'พิมพ์เล็ก ห้ามมีช่องว่างนำหน้า/ตามหลัง (เช่น "user@example.com ")');
        $this->insLine($sheet, $row++, 'รหัส (Code)', 'กำหนดเอง ใช้ตัวอักษร+ตัวเลข เช่น V001, C001 - ห้ามซ้ำในไฟล์เดียวกัน');
        $this->insLine($sheet, $row++, 'Currency', 'THB / USD / EUR / JPY / CNY (dropdown) - ถ้าไม่ใช่ THB แนะนำกรอก exchange_rate ใน PO (เว้นว่างระบบใช้ rate กลาง: USD=35, EUR=38, JPY=0.24, CNY=5)');
        $this->insLine($sheet, $row++, 'Status', 'เลือกจาก dropdown ห้ามพิมพ์เอง - ดูรายการใน _Lookups');
        $row++;

        // --- 7. Scenarios ---
        $this->insSection($sheet, $row++, '7. ตัวอย่าง Case การกรอก (Scenarios)');

        $this->insLine($sheet, $row++, 'Case 1: PR-PO-GR งวดเดียว', 'งานเล็ก เช่น จ้างทำของพรีเมียม 1 งวด');
        $this->insLine($sheet, $row++, '   Vendors', '1 แถว (บริษัท ABC)');
        $this->insLine($sheet, $row++, '   PurchaseRequisitions', '1 แถว');
        $this->insLine($sheet, $row++, '   PurchaseOrders', '1 แถว (total_phases = 1)');
        $this->insLine($sheet, $row++, '   GoodsReceipts', '1 แถว (milestone_number = 1)');
        $this->insLine($sheet, $row++, '   PaymentMilestones', 'ไม่ต้องกรอก - ระบบสร้าง "งวดที่ 1/1" ให้');
        $row++;

        $this->insLine($sheet, $row++, 'Case 2: PR-PO-GR หลายงวด', 'งานต่อเนื่อง เช่น จ้างบริหารข้อมูลรายไตรมาส 4 งวด');
        $this->insLine($sheet, $row++, '   PurchaseRequisitions', '1 แถว');
        $this->insLine($sheet, $row++, '   PurchaseOrders', '1 แถว (total_phases = 4)');
        $this->insLine($sheet, $row++, '   GoodsReceipts', '4 แถว (milestone_number = 1, 2, 3, 4) ใช้ po_number เดียวกันทุกแถว');
        $this->insLine($sheet, $row++, '   PaymentMilestones', 'ไม่ต้องกรอก - ระบบสร้าง "งวดที่ 1/4" ... "งวดที่ 4/4" จาก GR ให้ | หรือกรอกเองถ้างวดจ่ายไม่ตรงกับงวดรับ');
        $row++;

        $this->insLine($sheet, $row++, 'Case 3: PO ที่ยกเลิก', 'PR อนุมัติแล้วแต่ PO ถูก cancel');
        $this->insLine($sheet, $row++, '   PurchaseRequisitions', 'status = approved หรือ cancelled ตามความเป็นจริง');
        $this->insLine($sheet, $row++, '   PurchaseOrders', 'status = cancelled, notes = เหตุผลการยกเลิก');
        $this->insLine($sheet, $row++, '   GoodsReceipts', 'ไม่ต้องกรอก');
        $row++;

        $this->insLine($sheet, $row++, 'Case 4: สกุลเงินต่างประเทศ', 'ซื้อของจากต่างประเทศด้วย USD');
        $this->insLine($sheet, $row++, '   PurchaseRequisitions', 'currency = USD, procurement_budget = 1000 (เป็น USD)');
        $this->insLine($sheet, $row++, '   PurchaseOrders', 'currency = USD, total_amount = 1000, exchange_rate = 36.50 (THB per USD ณ วันออก PO ถ้าทราบ)');
        $this->insLine($sheet, $row++, '   GoodsReceipts', 'received_amount = จำนวนเงินสกุล USD (ไม่ต้องแปลงเป็น THB)');
        $row++;

        $this->insLine($sheet, $row++, 'Case 5: ไม่มี tax_id ของ vendor', 'vendor บุคคลธรรมดา หรือ vendor ที่ยังหา tax_id ไม่ได้');
        $this->insLine($sheet, $row++, '   Vendors.tax_id', 'เว้นว่างได้ - ระบบจะสร้าง placeholder (IMPORT-{vendor_code}) ให้อัตโนมัติ');
        $row++;

        $this->insLine($sheet, $row++, 'Case 6: กรรมการ/ผู้ขอ ไม่มี user ในระบบ', 'V3 ไม่ต้องแจ้ง IT ล่วงหน้าแล้ว');
        $this->insLine($sheet, $row++, '   วิธีทำ', 'กรอก email ลงไปได้เลย - ระบบสร้าง user ให้อัตโนมัติตอน import (ตั้งชื่อตามที่กรอกในชีต Committees)');
        $row++;

        // --- 8. Checklist ---
        $this->insSection($sheet, $row++, '8. Checklist ก่อนส่งไฟล์');
        $this->insLine($sheet, $row++, '[ ] ลบแถว EXAMPLE', 'ทุก sheet ต้องไม่มีแถวตัวอย่าง (สีเทา)');
        $this->insLine($sheet, $row++, '[ ] วันที่เป็น YYYY-MM-DD', 'ปี ค.ศ. ไม่มีวันที่เป็น text format อื่น');
        $this->insLine($sheet, $row++, '[ ] ไม่มี code ซ้ำ', 'vendor_code, committee_code, pr_number, po_number, gr_number ไม่ซ้ำในไฟล์');
        $this->insLine($sheet, $row++, '[ ] FK ครบ', 'ทุก pr_number/po_number/vendor_code/committee_code ที่อ้างอิง มีอยู่จริงใน sheet ที่ referenced');
        $this->insLine($sheet, $row++, '[ ] REQ ครบทุก column', 'ช่องสีแดงไม่มี cell ว่าง');
        $this->insLine($sheet, $row++, '[ ] purpose / budget_code', 'แนะนำกรอกให้ครบ (ไม่บังคับ แต่รายงานจะสมบูรณ์กว่า)');
        $this->insLine($sheet, $row++, '[ ] received_date', 'ถ้าต้องการ SLA stage "รับเรื่อง → PO อนุมัติ" ต้องกรอก received_date ใน PR');
        $this->insLine($sheet, $row++, '[ ] Status ตรงกับความเป็นจริง', 'PR ที่อนุมัติแล้วใช้ approved/completed ไม่ใช่ draft');
        $this->insLine($sheet, $row++, '[ ] ไม่เปลี่ยน header', 'ชื่อ column แถวที่ 1 ของทุก sheet ต้องตรงกับต้นฉบับ ห้ามสลับ/แทรก column');
        $this->insLine($sheet, $row++, '[ ] ยอดเงินตรวจสอบแล้ว', 'SUM ของ received_amount ของ GR ทุกงวด ≈ total_amount ของ PO');
        $this->insLine($sheet, $row++, '[ ] Save เป็น .xlsx', 'ไม่ใช่ .xls หรือ .csv');
        $row++;

        // --- 9. FAQ ---
        $this->insSection($sheet, $row++, '9. คำถามที่พบบ่อย (FAQ)');

        $this->insQa($sheet, $row++, 'Q1', 'PR ใบเดียวแต่มี PO หลายใบ ทำไง?');
        $this->insLine($sheet, $row++, '   A', 'กรอกใน PurchaseOrders หลายแถว ใช้ pr_number เดียวกันทุกแถว แต่ po_number ต่างกัน');
        $row++;

        $this->insQa($sheet, $row++, 'Q2', 'มี GR หลายงวดต่อ PO จะกรอกยังไง?');
        $this->insLine($sheet, $row++, '   A', 'กรอกใน GoodsReceipts หลายแถว ใช้ po_number เดียวกัน, milestone_number = 1, 2, 3... ไม่ซ้ำ, gr_number ต่างกันทุกแถว');
        $row++;

        $this->insQa($sheet, $row++, 'Q3', 'ไม่รู้ company_id กับ department_id ดูจากไหน?');
        $this->insLine($sheet, $row++, '   A', 'ไปที่ sheet _Lookups column Companies (A) และ Departments (D) มี id + ชื่อ list ไว้');
        $row++;

        $this->insQa($sheet, $row++, 'Q4', 'import ซ้ำไฟล์เดิม/ไฟล์แก้ไข จะเกิดอะไรขึ้น?');
        $this->insLine($sheet, $row++, '   A', 'PR/PO เลขเดิมจะถูกลบและสร้างใหม่จากไฟล์ (replace) - แก้ไฟล์แล้ว import ซ้ำได้เลย ข้อมูลจะตรงกับไฟล์ล่าสุดเสมอ');
        $row++;

        $this->insQa($sheet, $row++, 'Q5', 'ไฟล์เดิมมีคำว่า "วิธีตกลงราคา" แต่ dropdown เป็นภาษาอังกฤษ?');
        $this->insLine($sheet, $row++, '   A', 'แปลงเอง: วิธีตกลงราคา → agreement_price, วิธีพิเศษ → special_1, วิธีประมูลโดยเชิญ → invitation_bid, วิธีประมูลทั่วไป → open_bid, วิธีคัดเลือก → selection');
        $row++;

        $this->insQa($sheet, $row++, 'Q6', 'เลข PR/PO ใน Excel เดิมไม่ unique (ซ้ำกัน) ทำไง?');
        $this->insLine($sheet, $row++, '   A', 'ตรวจสอบกับทีมก่อนว่าควรเก็บตัวไหน แล้วเติม suffix เช่น EF10000535-R1 สำหรับตัวที่ revision');
        $row++;

        $this->insQa($sheet, $row++, 'Q7', 'ถ้ามี error ตอน import ข้อมูลจะเข้าไปครึ่ง ๆ กลาง ๆ มั้ย?');
        $this->insLine($sheet, $row++, '   A', 'ไม่ - V3 เป็น all-or-nothing ถ้ามี error แม้แถวเดียว ระบบจะไม่บันทึกอะไรเลย และแสดงรายการ error ทั้งหมด (sheet + แถว + สาเหตุ) ให้แก้แล้ว import ใหม่');
        $row++;

        $this->insQa($sheet, $row++, 'Q8', 'ต้องการเพิ่มแถวมากกว่า 500 แถว ทำได้มั้ย?');
        $this->insLine($sheet, $row++, '   A', 'ได้ แต่ dropdown validation ที่ setup ไว้จะครอบคลุมแค่ 500-1000 แถวแรก - copy cell ที่มี dropdown แล้ว paste ลงแถวใหม่');
        $row++;

        $this->insQa($sheet, $row++, 'Q9', 'อยากทดสอบก่อนโดยไม่บันทึกจริง ทำได้มั้ย?');
        $this->insLine($sheet, $row++, '   A', 'ได้ - ให้ IT รันด้วย --dry-run จะ validate ครบทุกอย่างแต่ rollback ไม่บันทึก');
        $row++;

        // --- 10. Glossary ---
        $this->insSection($sheet, $row++, '10. คำศัพท์ (Glossary)');
        $this->insLine($sheet, $row++, 'PR (Purchase Requisition)', 'ใบขอซื้อ/ขอจ้าง - เอกสารขออนุมัติจัดซื้อจากแผนก');
        $this->insLine($sheet, $row++, 'PO (Purchase Order)', 'ใบสั่งซื้อ/สั่งจ้าง - เอกสารที่ส่งให้ vendor หลัง PR อนุมัติ');
        $this->insLine($sheet, $row++, 'GR (Goods Receipt)', 'ใบตรวจรับ - เอกสารยืนยันว่ารับของ/รับงานแล้ว');
        $this->insLine($sheet, $row++, 'Vendor', 'ผู้ขาย/ผู้รับจ้าง (บริษัทหรือบุคคล)');
        $this->insLine($sheet, $row++, 'Committee', 'กรรมการ - กรรมการจัดหา (procurement) / ตรวจรับ (inspection) / ผู้อนุมัติ (approver)');
        $this->insLine($sheet, $row++, 'Milestone (งวด)', 'งวดส่งมอบ/ชำระเงิน - 1 PO อาจแบ่งเป็นหลายงวด');
        $this->insLine($sheet, $row++, 'SLA', 'มาตรฐานเวลาทำงาน - ระบบคำนวณอัตโนมัติหลัง import จาก submitted_at / received_date / pr_approved_at / po_approved_at');
        $this->insLine($sheet, $row++, 'Enum', 'ค่าที่กำหนดล่วงหน้า - ต้องเลือกจากรายการ ห้ามพิมพ์เอง');
        $this->insLine($sheet, $row++, 'FK (Foreign Key)', 'คีย์อ้างอิง - ใช้เชื่อมข้อมูลข้าม sheet (เช่น pr_number ใน PO ต้องตรงกับ pr_number ใน PR)');
        $this->insLine($sheet, $row++, 'All-or-nothing', 'บันทึกทั้งหมดหรือไม่บันทึกเลย - ไม่มีกรณีข้อมูลเข้าครึ่งเดียว');
        $row++;

        // --- 11. Contact ---
        $this->insSection($sheet, $row++, '11. ติดต่อ / คำสั่งสำหรับ IT');
        $this->insLine($sheet, $row++, 'ทีม Procurement', 'หากต้องการความช่วยเหลือเรื่องข้อมูล/วิธีกรอก');
        $this->insLine($sheet, $row++, 'คำสั่ง import (คำสั่งเดียวจบ)', 'php artisan procurement:import-excel-v3 {path}');
        $this->insLine($sheet, $row++, 'ทดสอบโดยไม่บันทึก', 'php artisan procurement:import-excel-v3 {path} --dry-run');
        $this->insLine($sheet, $row++, 'options เพิ่มเติม', '--company=2 (default company) | --no-replace (ข้ามเลขที่ซ้ำแทนการแทนที่) | --no-auto-users (ไม่สร้าง user อัตโนมัติ) | --skip-sla (ไม่รัน SLA backfill)');

        $totalRows = $row;

        // --- Styling ---
        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(110);
        $sheet->getStyle('A1:B'.$totalRows)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:B'.$totalRows)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        // Title
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18)->getColor()->setRGB('1F4E78');
        $sheet->mergeCells('A1:B1');
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Legend highlight
        $sheet->getStyle("A{$legendReq}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFCCCC');
        $sheet->getStyle("A{$legendOpt}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');
        $sheet->getStyle("A{$legendExample}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E7E6E6');
    }

    /** Write a section header row (blue band, bold). */
    private function insSection(Worksheet $sheet, int $row, string $title): void
    {
        $sheet->setCellValue('A'.$row, $title);
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E78');
        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    /** Write a plain key-value line. */
    private function insLine(Worksheet $sheet, int $row, string $label, string $text): void
    {
        if ($label !== '') {
            $sheet->setCellValue('A'.$row, $label);
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        }
        $sheet->setCellValue('B'.$row, $text);
    }

    /** Write a title row (spans both columns). */
    private function insTitle(Worksheet $sheet, int $row, string $text): void
    {
        $sheet->setCellValue('A'.$row, $text);
    }

    /** Write a Q&A style row (Q label in orange). */
    private function insQa(Worksheet $sheet, int $row, string $qLabel, string $question): void
    {
        $sheet->setCellValue('A'.$row, $qLabel);
        $sheet->setCellValue('B'.$row, $question);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true)->getColor()->setRGB('C65911');
        $sheet->getStyle('B'.$row)->getFont()->setBold(true);
    }

    private function buildLookupsSheet(Spreadsheet $wb, ?string $companyFilter): void
    {
        $sheet = $wb->createSheet();
        $sheet->setTitle('_Lookups');

        // Companies
        $sheet->setCellValue('A1', 'Companies');
        $sheet->setCellValue('A2', 'id');
        $sheet->setCellValue('B2', 'name');
        $this->styleHeader($sheet, 'A1:B1', 'BDD7EE');
        $this->styleHeader($sheet, 'A2:B2', 'DDEBF7');

        $row = 3;
        try {
            foreach (Company::query()->orderBy('id')->get() as $c) {
                $sheet->setCellValue("A{$row}", $c->id);
                $sheet->setCellValue("B{$row}", $c->name ?? '');
                $row++;
            }
        } catch (\Throwable $e) {
            $sheet->setCellValue('A3', '(error loading: '.$e->getMessage().')');
        }

        // Departments
        $sheet->setCellValue('D1', 'Departments');
        $sheet->setCellValue('D2', 'id');
        $sheet->setCellValue('E2', 'name');
        $sheet->setCellValue('F2', 'company_id');
        $this->styleHeader($sheet, 'D1:F1', 'BDD7EE');
        $this->styleHeader($sheet, 'D2:F2', 'DDEBF7');

        $row = 3;
        try {
            foreach (Department::query()->orderBy('id')->get() as $d) {
                $sheet->setCellValue("D{$row}", $d->id);
                $sheet->setCellValue("E{$row}", $d->name ?? '');
                $sheet->setCellValue("F{$row}", $d->company_id ?? '');
                $row++;
            }
        } catch (\Throwable $e) {
            $sheet->setCellValue('D3', '(error loading)');
        }

        // Users
        $sheet->setCellValue('H1', 'Users');
        $sheet->setCellValue('H2', 'id');
        $sheet->setCellValue('I2', 'email');
        $sheet->setCellValue('J2', 'name');
        $this->styleHeader($sheet, 'H1:J1', 'BDD7EE');
        $this->styleHeader($sheet, 'H2:J2', 'DDEBF7');

        $row = 3;
        try {
            foreach (User::query()->orderBy('id')->get() as $u) {
                $sheet->setCellValue("H{$row}", $u->id);
                $sheet->setCellValue("I{$row}", $u->email ?? '');
                $sheet->setCellValue("J{$row}", $u->name ?? '');
                $row++;
            }
        } catch (\Throwable $e) {
            $sheet->setCellValue('H3', '(error loading)');
        }

        // Enum tables
        $this->writeEnumBlock($sheet, 'L', 'Procurement Methods', self::PROCUREMENT_METHODS);
        $this->writeEnumBlock($sheet, 'N', 'Categories', self::CATEGORIES);
        $this->writeEnumBlock($sheet, 'P', 'Work Types', self::WORK_TYPES);
        $this->writeEnumBlock($sheet, 'R', 'Form Categories', self::FORM_CATEGORIES);
        $this->writeEnumBlock($sheet, 'T', 'Currencies', self::CURRENCIES);
        $this->writeEnumBlock($sheet, 'V', 'PR Statuses', self::PR_STATUSES);
        $this->writeEnumBlock($sheet, 'X', 'PO Statuses', self::PO_STATUSES);
        $this->writeEnumBlock($sheet, 'Z', 'GR Statuses', self::GR_STATUSES);
        $this->writeEnumBlock($sheet, 'AB', 'Inspection Statuses', self::INSPECTION_STATUSES);
        $this->writeEnumBlock($sheet, 'AD', 'Milestone Statuses', self::MILESTONE_STATUSES);
        $this->writeEnumBlock($sheet, 'AF', 'Vendor Statuses', self::VENDOR_STATUSES);
        $this->writeEnumBlock($sheet, 'AH', 'Committee Roles', self::COMMITTEE_ROLES);

        // Column widths
        $lastColIndex = Coordinate::columnIndexFromString('AI');
        for ($i = 1; $i <= $lastColIndex; $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setCellValue('A100', '* ใช้ sheet นี้เป็น reference ในการกรอก code/email/status');
        if ($companyFilter) {
            $sheet->setCellValue('A101', "* Template ถูกสร้างสำหรับ company filter: {$companyFilter}");
        }
    }

    private function writeEnumBlock(Worksheet $sheet, string $startCol, string $title, array $values): void
    {
        $sheet->setCellValue($startCol.'1', $title);
        $this->styleHeader($sheet, "{$startCol}1", 'FFE699');

        $row = 2;
        foreach ($values as $v) {
            $sheet->setCellValue($startCol.$row, $v);
            $row++;
        }
    }

    private function buildVendorsSheet(Spreadsheet $wb): void
    {
        $sheet = $wb->createSheet();
        $sheet->setTitle('Vendors');

        $headers = [
            ['vendor_code', 'REQ', 'Unique code เช่น V001, V002 (ใช้ reference ใน PO)'],
            ['company_name', 'REQ', 'ชื่อบริษัท/ผู้ขาย'],
            ['tax_id', 'OPT', '13 หลัก ถ้าไม่มีเว้นว่าง (ระบบจะสร้าง placeholder)'],
            ['work_category', 'OPT', 'หมวดงาน เช่น services, goods'],
            ['contact_name', 'OPT', 'ชื่อผู้ติดต่อ'],
            ['contact_phone', 'OPT', 'เบอร์โทรศัพท์'],
            ['contact_email', 'OPT', 'อีเมลผู้ติดต่อ'],
            ['address', 'OPT', 'ที่อยู่'],
            ['status', 'OPT', 'default: approved'],
        ];

        $this->writeHeaders($sheet, $headers);

        $example = ['V001', 'บริษัท ตัวอย่าง จำกัด', '0123456789012', 'services',
            'คุณตัวอย่าง', '02-123-4567', 'example@vendor.com', '123 ถนนตัวอย่าง กทม.', 'approved'];
        $this->writeExampleRow($sheet, 4, $example);

        $this->addDropdown($sheet, 'I', self::VENDOR_STATUSES, 4, 500);

        $this->autoSize($sheet, 'A', 'J');
    }

    private function buildCommitteesSheet(Spreadsheet $wb): void
    {
        $sheet = $wb->createSheet();
        $sheet->setTitle('Committees');

        $headers = [
            ['committee_code', 'REQ', 'Unique code เช่น C001, C002'],
            ['name_th', 'REQ', 'ชื่อ-นามสกุล (ภาษาไทย)'],
            ['email', 'REQ', 'อีเมล - ถ้าไม่มี user ในระบบ ระบบสร้างให้อัตโนมัติ'],
            ['default_role', 'OPT', 'procurement / inspection / approver'],
            ['position', 'OPT', 'ตำแหน่ง (optional)'],
        ];

        $this->writeHeaders($sheet, $headers);

        $example = ['C001', 'นางสาวตัวอย่าง ทดสอบ', 'example.t@innobicasia.com', 'procurement', 'ผู้จัดการ'];
        $this->writeExampleRow($sheet, 4, $example);
        $example2 = ['C002', 'นายตรวจรับ ตัวอย่าง', 'example.i@innobicasia.com', 'inspection', 'หัวหน้างาน'];
        $this->writeExampleRow($sheet, 5, $example2);

        $this->addDropdown($sheet, 'D', self::COMMITTEE_ROLES, 4, 500);

        $this->autoSize($sheet, 'A', 'F');
    }

    private function buildPurchaseRequisitionsSheet(Spreadsheet $wb): void
    {
        $sheet = $wb->createSheet();
        $sheet->setTitle('PurchaseRequisitions');

        $headers = [
            ['pr_number', 'REQ', 'เลขที่ PR (unique) เช่น EF10000535'],                             // A
            ['title', 'REQ', 'ชื่องาน'],                                                            // B
            ['purpose', 'OPT', 'วัตถุประสงค์การจัดซื้อ (แนะนำให้กรอก - ใช้ในรายงาน)'],              // C
            ['description', 'OPT', 'รายละเอียด'],                                                   // D
            ['company_id', 'REQ', 'อ้างอิง _Lookups > Companies'],                                   // E
            ['department_id', 'REQ', 'อ้างอิง _Lookups > Departments'],                              // F
            ['requester_email', 'REQ', 'อีเมลผู้ขอ (ไม่มี user ระบบสร้างให้)'],                       // G
            ['procurement_method', 'REQ', 'agreement_price / invitation_bid / open_bid / special_1 / special_2 / selection'], // H
            ['category', 'OPT', 'ดู _Lookups > Categories'],                                         // I
            ['work_type', 'REQ', 'buy / hire / rent'],                                               // J
            ['form_category', 'OPT', 'act_based / law_based'],                                       // K
            ['budget_code', 'OPT', 'รหัสงบประมาณ (แนะนำให้กรอก)'],                                   // L
            ['project_code', 'OPT', 'รหัสโครงการ (ถ้ามี)'],                                          // M
            ['currency', 'REQ', 'THB / USD / EUR / JPY / CNY'],                                      // N
            ['procurement_budget', 'REQ', 'วงเงินก่อน Vat (ตัวเลข)'],                                // O
            ['request_date', 'REQ', 'YYYY-MM-DD วันที่ยื่น PR'],                                     // P
            ['received_date', 'OPT', 'YYYY-MM-DD วันที่จัดซื้อรับเรื่อง (ใช้คำนวณ SLA)'],            // Q
            ['submitted_at', 'OPT', 'YYYY-MM-DD วันที่ส่งอนุมัติ (SLA)'],                            // R
            ['pr_approved_at', 'OPT', 'YYYY-MM-DD วันที่อนุมัติ PR'],                                // S
            ['required_date', 'REQ', 'YYYY-MM-DD วันที่ต้องการของ'],                                 // T
            ['pr_approver_email', 'OPT', 'อีเมลผู้อนุมัติ PR'],                                      // U
            ['procurement_committee_code', 'OPT', 'อ้างอิง Committees.committee_code'],              // V
            ['inspection_committee_code', 'OPT', 'อ้างอิง Committees.committee_code'],               // W
            ['status', 'REQ', 'historical ใช้ approved หรือ completed'],                             // X
            ['notes', 'OPT', 'หมายเหตุ'],                                                           // Y
        ];

        $this->writeHeaders($sheet, $headers);

        $example = [
            'TEST-PR-001', 'จ้างบริหารข้อมูลหลักลูกค้าและผลิตภัณฑ์',
            'เพื่อบริหารจัดการข้อมูลหลักลูกค้าและผลิตภัณฑ์ให้ถูกต้องเป็นปัจจุบัน', '',
            2, 1, 'kanokwan.k@innobicasia.com',
            'agreement_price', 'general_services', 'hire', 'act_based',
            'BG-2568-001', '',
            'THB', 36000,
            '2024-12-25', '2024-12-26', '2024-12-28', '2024-12-30', '2025-12-31',
            'kittinat.s@innobicasia.com', 'C001', 'C002',
            'completed', 'Imported from Excel 2568',
        ];
        $this->writeExampleRow($sheet, 4, $example);

        $this->addDropdown($sheet, 'H', self::PROCUREMENT_METHODS, 4, 500);
        $this->addDropdown($sheet, 'I', self::CATEGORIES, 4, 500);
        $this->addDropdown($sheet, 'J', self::WORK_TYPES, 4, 500);
        $this->addDropdown($sheet, 'K', self::FORM_CATEGORIES, 4, 500);
        $this->addDropdown($sheet, 'N', self::CURRENCIES, 4, 500);
        $this->addDropdown($sheet, 'X', self::PR_STATUSES, 4, 500);

        $this->autoSize($sheet, 'A', 'Z');
    }

    private function buildPurchaseOrdersSheet(Spreadsheet $wb): void
    {
        $sheet = $wb->createSheet();
        $sheet->setTitle('PurchaseOrders');

        $headers = [
            ['po_number', 'REQ', 'เลขที่ PO (unique) เช่น EF31000494'],                              // A
            ['pr_number', 'REQ', 'อ้างอิง PurchaseRequisitions.pr_number'],                          // B
            ['vendor_code', 'REQ', 'อ้างอิง Vendors.vendor_code'],                                   // C
            ['po_title', 'OPT', 'ชื่อ PO (default: copy จาก PR.title)'],                             // D
            ['work_type', 'OPT', 'buy / hire / rent (default: copy จาก PR)'],                        // E
            ['procurement_method', 'OPT', 'copy จาก PR ถ้าเว้นว่าง'],                                // F
            ['order_date', 'REQ', 'YYYY-MM-DD'],                                                     // G
            ['po_approved_at', 'OPT', 'YYYY-MM-DD วันที่อนุมัติ PO (ใช้คำนวณ SLA)'],                 // H
            ['total_amount', 'REQ', 'จำนวนเงินรวม'],                                                 // I
            ['currency', 'REQ', 'THB / USD / EUR / JPY / CNY'],                                      // J
            ['exchange_rate', 'OPT', 'ถ้าไม่ใช่ THB แนะนำกรอก (เว้นว่างใช้ rate กลาง)'],              // K
            ['stamp_duty', 'OPT', 'ค่าอากรแสตมป์'],                                                  // L
            ['start_date', 'OPT', 'YYYY-MM-DD วันที่เริ่มสัญญา'],                                    // M
            ['end_date', 'OPT', 'YYYY-MM-DD วันที่สิ้นสุดสัญญา'],                                    // N
            ['total_phases', 'OPT', 'จำนวนงวด เช่น 4, 6'],                                           // O
            ['delivery_location', 'OPT', 'สถานที่ส่งมอบ'],                                           // P
            ['payment_terms', 'OPT', 'เงื่อนไขการชำระเงิน'],                                         // Q
            ['inspection_committee_code', 'OPT', 'อ้างอิง Committees.committee_code'],               // R
            ['status', 'REQ', 'closed / fully_received / partially_received / cancelled'],           // S
            ['sap_po_number', 'OPT', 'เลข PO ในระบบ SAP (ถ้ามี)'],                                   // T
            ['notes', 'OPT', 'หมายเหตุ'],                                                            // U
        ];

        $this->writeHeaders($sheet, $headers);

        $example = [
            'TEST-PO-001', 'TEST-PR-001', 'V001', 'จ้างบริหารข้อมูลหลักลูกค้า',
            'hire', 'agreement_price',
            '2024-12-30', '2024-12-30', 36000, 'THB', '', 0,
            '2024-12-30', '2025-12-31', 4, 'Innobic Nutrition', 'Net 30',
            'C002', 'closed', '4500001234', '',
        ];
        $this->writeExampleRow($sheet, 4, $example);

        $this->addDropdown($sheet, 'E', self::WORK_TYPES, 4, 500);
        $this->addDropdown($sheet, 'F', self::PROCUREMENT_METHODS, 4, 500);
        $this->addDropdown($sheet, 'J', self::CURRENCIES, 4, 500);
        $this->addDropdown($sheet, 'S', self::PO_STATUSES, 4, 500);

        $this->autoSize($sheet, 'A', 'V');
    }

    private function buildGoodsReceiptsSheet(Spreadsheet $wb): void
    {
        $sheet = $wb->createSheet();
        $sheet->setTitle('GoodsReceipts');

        $headers = [
            ['gr_number', 'REQ', 'เลขที่ GR (unique) เช่น EF50000117/2025'],                         // A
            ['po_number', 'REQ', 'อ้างอิง PurchaseOrders.po_number'],                                // B
            ['milestone_number', 'OPT', 'งวดที่ เช่น 1, 2 (default: 1)'],                            // C
            ['total_phases', 'OPT', 'งวดทั้งหมด เช่น 4'],                                            // D
            ['receipt_date', 'REQ', 'YYYY-MM-DD วันที่ตรวจรับ (เว้นว่างได้ถ้ามี delivery_date)'],     // E
            ['delivery_date', 'OPT', 'YYYY-MM-DD วันที่ส่งมอบ'],                                     // F
            ['expected_delivery_date', 'OPT', 'YYYY-MM-DD กำหนดส่งมอบตามสัญญา'],                     // G
            ['received_amount', 'REQ', 'จำนวนเงินที่รับ/จ่ายในงวดนี้ (บันทึกลง milestone_amount)'],   // H
            ['milestone_percentage', 'OPT', '%งวด กรอกเองได้ - เว้นว่างระบบคำนวณจากยอด PO'],         // I
            ['document_reference', 'OPT', 'เลขเอกสารอ้างอิง เช่น INV.7500003901'],                   // J
            ['document_date', 'OPT', 'YYYY-MM-DD วันที่เอกสารอ้างอิง'],                              // K
            ['inspection_status', 'OPT', 'pending / passed / failed / partial (default: passed)'],   // L
            ['received_by_email', 'OPT', 'อีเมลผู้รับ (ถ้าเว้นว่างใช้ผู้ขอ PR)'],                     // M
            ['status', 'REQ', 'default: completed'],                                                 // N
            ['notes', 'OPT', 'หมายเหตุ'],                                                            // O
        ];

        $this->writeHeaders($sheet, $headers);

        $example = [
            'TEST-GR-001', 'TEST-PO-001', 1, 4, '2025-04-21', '2025-04-17',
            '2025-04-17', 2860, 25, 'INV.7500003901', '2025-04-17',
            'passed', 'kanokwan.k@innobicasia.com', 'completed', '',
        ];
        $this->writeExampleRow($sheet, 4, $example);

        $this->addDropdown($sheet, 'L', self::INSPECTION_STATUSES, 4, 1000);
        $this->addDropdown($sheet, 'N', self::GR_STATUSES, 4, 1000);

        $this->autoSize($sheet, 'A', 'P');
    }

    private function buildPaymentMilestonesSheet(Spreadsheet $wb): void
    {
        $sheet = $wb->createSheet();
        $sheet->setTitle('PaymentMilestones');

        $headers = [
            ['po_number', 'REQ', 'อ้างอิง PurchaseOrders.po_number'],                                // A
            ['milestone_number', 'REQ', 'งวดที่ (integer)'],                                          // B
            ['milestone_title', 'OPT', 'ชื่องวด (default: "งวดที่ n/m")'],                            // C
            ['percentage', 'OPT', 'เปอร์เซ็นต์ของ PO เช่น 25'],                                       // D
            ['amount', 'REQ', 'จำนวนเงินงวดนี้'],                                                     // E
            ['due_date', 'OPT', 'YYYY-MM-DD วันครบกำหนด (เว้นว่างใช้ paid_date/วันสิ้นสุดสัญญา)'],    // F
            ['paid_date', 'OPT', 'YYYY-MM-DD วันที่จ่ายจริง'],                                        // G
            ['paid_amount', 'OPT', 'จำนวนเงินที่จ่ายจริง'],                                           // H
            ['payment_reference', 'OPT', 'เลขอ้างอิงการจ่าย'],                                        // I
            ['status', 'REQ', 'pending / due / paid / overdue / cancelled'],                          // J
            ['notes', 'OPT', 'หมายเหตุ'],                                                             // K
        ];

        $this->writeHeaders($sheet, $headers);

        // Sheet-level note: this whole sheet is optional
        $sheet->setCellValue('M1', 'SHEET นี้ OPTIONAL - เว้นว่างทั้ง sheet ได้ ระบบสร้างงวดจาก GoodsReceipts ให้อัตโนมัติ');
        $sheet->getStyle('M1')->getFont()->setBold(true)->getColor()->setRGB('C00000');

        $example = [
            'TEST-PO-001', 1, 'งวดที่ 1/4', 25, 9000,
            '2025-03-31', '2025-04-17', 2860, 'PAY-001', 'paid', '',
        ];
        $this->writeExampleRow($sheet, 4, $example);

        $this->addDropdown($sheet, 'J', self::MILESTONE_STATUSES, 4, 1000);

        $this->autoSize($sheet, 'A', 'L');
    }

    // --------------------------------------------------------------
    // Styling helpers
    // --------------------------------------------------------------

    /**
     * Write the header block (3 rows: name, REQ/OPT tag, description).
     *
     * @param  array<int, array{0:string,1:string,2:string}>  $headers
     */
    private function writeHeaders(Worksheet $sheet, array $headers): void
    {
        $col = 'A';
        foreach ($headers as $header) {
            [$name, $requirement, $description] = $header;

            $sheet->setCellValue($col.'1', $name);
            $sheet->setCellValue($col.'2', $requirement);
            $sheet->setCellValue($col.'3', $description);

            $color = match ($requirement) {
                'REQ' => 'FFCCCC',
                'OPT' => 'FFF2CC',
                'AUTO' => 'D9D9D9',
                default => 'FFFFFF',
            };

            $sheet->getStyle($col.'1')->getFont()->setBold(true);
            $sheet->getStyle($col.'1:'.$col.'3')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($color);

            $sheet->getStyle($col.'1:'.$col.'3')->getBorders()
                ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $sheet->getStyle($col.'3')->getAlignment()->setWrapText(true);
            $sheet->getStyle($col.'3')->getFont()->setItalic(true)->setSize(9);

            $col = $this->nextColumn($col);
        }

        $sheet->freezePane('A4');
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(3)->setRowHeight(45);
    }

    private function writeExampleRow(Worksheet $sheet, int $row, array $values): void
    {
        $col = 'A';
        foreach ($values as $v) {
            $sheet->setCellValue($col.$row, $v);
            $col = $this->nextColumn($col);
        }
        // Note in the next column
        $sheet->setCellValue($col.$row, '<- EXAMPLE (ลบออกก่อน import)');
        $sheet->getStyle($col.$row)->getFont()->setBold(true)->getColor()->setRGB('C00000');

        // Shade the example data
        $endCol = $this->prevColumn($col);
        $sheet->getStyle("A{$row}:{$endCol}{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E7E6E6');
        $sheet->getStyle("A{$row}:{$endCol}{$row}")->getFont()->setItalic(true);
    }

    private function styleHeader(Worksheet $sheet, string $range, string $rgb): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($rgb);
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function addDropdown(Worksheet $sheet, string $col, array $values, int $startRow, int $endRow): void
    {
        $formula = '"'.implode(',', $values).'"';

        for ($row = $startRow; $row <= $endRow; $row++) {
            $validation = $sheet->getCell($col.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setErrorTitle('Invalid value');
            $validation->setError('เลือกค่าจากรายการเท่านั้น');
            $validation->setPromptTitle('เลือกค่า');
            $validation->setPrompt('กรุณาเลือกจาก dropdown');
            $validation->setFormula1($formula);
        }
    }

    private function autoSize(Worksheet $sheet, string $fromCol, string $toCol): void
    {
        $fromIdx = Coordinate::columnIndexFromString($fromCol);
        $toIdx = Coordinate::columnIndexFromString($toCol);
        for ($i = $fromIdx; $i <= $toIdx; $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function nextColumn(string $col): string
    {
        $idx = Coordinate::columnIndexFromString($col);

        return Coordinate::stringFromColumnIndex($idx + 1);
    }

    private function prevColumn(string $col): string
    {
        $idx = Coordinate::columnIndexFromString($col);

        return Coordinate::stringFromColumnIndex(max(1, $idx - 1));
    }
}
