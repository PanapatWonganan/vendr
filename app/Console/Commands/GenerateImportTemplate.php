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

class GenerateImportTemplate extends Command
{
    protected $signature = 'procurement:generate-import-template
                            {--company= : Company code filter for Lookups sheet (optional)}
                            {--out= : Output file path (defaults to storage/app)}';

    protected $description = 'Generate an Excel template for bulk importing historical procurement records (PR/PO/GR)';

    private const PR_STATUSES = [
        'draft', 'pending_approval', 'approved', 'rejected', 'completed', 'cancelled',
    ];

    private const PO_STATUSES = [
        'draft', 'pending_approval', 'approved', 'rejected', 'sent_to_supplier',
        'acknowledged', 'partially_received', 'fully_received', 'closed', 'cancelled',
    ];

    private const GR_STATUSES = [
        'draft', 'pending_review', 'completed', 'returned', 'partially_returned', 'cancelled',
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
            ?: storage_path('app/procurement_import_template_' . now()->format('Ymd_His') . '.xlsx');

        $this->info('Generating procurement import template...');

        $spreadsheet = new Spreadsheet();
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
        $this->insTitle($sheet, $row++, 'VENDR - คู่มือการกรอก Template นำเข้าข้อมูลจัดซื้อจัดจ้าง');
        $this->insLine($sheet, $row++, '', 'เวอร์ชัน 1.0 | สำหรับ import ข้อมูล PR/PO/GR ย้อนหลัง');
        $row++;

        // --- 1. Overview ---
        $this->insSection($sheet, $row++, '1. ภาพรวม (Overview)');
        $this->insLine($sheet, $row++, 'วัตถุประสงค์', 'ใช้สำหรับนำเข้าข้อมูลใบขอซื้อ (PR) / ใบสั่งซื้อ (PO) / ใบตรวจรับ (GR) ย้อนหลัง เข้าระบบ VENDR');
        $this->insLine($sheet, $row++, 'เหมาะกับใคร', 'เจ้าหน้าที่จัดซื้อที่มีข้อมูลเดิมอยู่ในไฟล์ Excel / SAP / เอกสารอื่น ๆ และต้องการย้ายเข้า VENDR');
        $this->insLine($sheet, $row++, 'Historical flag', 'ระบบจะ tag ข้อมูลที่ import เข้ามาว่าเป็น "historical" — ข้าม workflow อนุมัติและไม่ส่ง notification');
        $this->insLine($sheet, $row++, 'ต้องเตรียมอะไรบ้าง', '1) ไฟล์นี้ (template)  2) ไฟล์เดิมที่มีข้อมูล  3) ข้อมูล code ของบริษัท/แผนก/user ใน VENDR (ดู sheet _Lookups)');
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
        $this->insLine($sheet, $row++, 'PaymentMilestones', 'งวดการชำระเงิน (optional)');
        $row++;

        // --- 3. Step-by-step ---
        $this->insSection($sheet, $row++, '3. ขั้นตอนการกรอก (Step-by-Step)');
        $this->insLine($sheet, $row++, 'Step 1: Vendors', 'รวบรวมรายชื่อผู้ขายทั้งหมดที่มีในข้อมูลเดิม → กรอกในชีต Vendors → ตั้ง vendor_code ของเราเอง (เช่น V001, V002)');
        $this->insLine($sheet, $row++, 'Step 2: Committees', 'รวบรวมชื่อกรรมการจัดหา/ตรวจรับ/ผู้อนุมัติ → กรอกในชีต Committees → ตั้ง committee_code (เช่น C001, C002)');
        $this->insLine($sheet, $row++, 'Step 3: PurchaseRequisitions', 'กรอก PR ทั้งหมด - อ้างอิง committee_code จาก step 2 - อ้างอิง company_id / department_id จาก _Lookups - อ้างอิง requester_email จาก _Lookups > Users');
        $this->insLine($sheet, $row++, 'Step 4: PurchaseOrders', 'กรอก PO ทั้งหมด - อ้างอิง pr_number จาก step 3 - อ้างอิง vendor_code จาก step 1');
        $this->insLine($sheet, $row++, 'Step 5: GoodsReceipts', 'กรอกใบตรวจรับแต่ละงวด - อ้างอิง po_number จาก step 4 - งวดเดียว = 1 แถว, หลายงวด = หลายแถว');
        $this->insLine($sheet, $row++, 'Step 6: PaymentMilestones', 'กรอกงวดชำระเงินถ้าอยากแยก (optional) - อ้างอิง po_number');
        $this->insLine($sheet, $row++, 'Step 7: ตรวจสอบ', 'ใช้ checklist ในข้อ 7 ก่อนส่งไฟล์');
        $this->insLine($sheet, $row++, 'Step 8: ลบ example', 'ลบแถวตัวอย่าง (สีเทา) ทุก sheet ก่อน save');
        $row++;

        // --- 4. Legend / Color code ---
        $this->insSection($sheet, $row++, '4. สัญลักษณ์ & สี (Legend)');
        $this->insLine($sheet, $row++, 'REQ (หัว column สีแดง)', 'Required = ต้องกรอก ห้ามเว้นว่าง');
        $legendReq = $row - 1;
        $this->insLine($sheet, $row++, 'OPT (หัว column สีเหลือง)', 'Optional = กรอกถ้ามีข้อมูล ไม่มีให้เว้นว่าง');
        $legendOpt = $row - 1;
        $this->insLine($sheet, $row++, 'แถวที่ 3 (italic เล็ก)', 'คำอธิบาย column');
        $this->insLine($sheet, $row++, 'แถวสีเทา (แถวที่ 4)', 'EXAMPLE = ตัวอย่าง ลบออกก่อน import');
        $legendExample = $row - 1;
        $this->insLine($sheet, $row++, 'Dropdown (ลูกศรลง)', 'column ที่เป็น enum - เลือกจากรายการ ห้ามพิมพ์เอง');
        $row++;

        // --- 5. Data format rules ---
        $this->insSection($sheet, $row++, '5. รูปแบบข้อมูล (Data Format)');
        $this->insLine($sheet, $row++, 'วันที่ (Date)', 'ใช้ YYYY-MM-DD เท่านั้น เช่น 2025-04-17 | ห้ามใช้ 17/4/2025, 2025-04-17, Apr 17 2025, Excel serial (45660)');
        $this->insLine($sheet, $row++, '   วิธีตั้งใน Excel', 'คลุม column วันที่ → Format Cells → Custom → พิมพ์ yyyy-mm-dd → OK');
        $this->insLine($sheet, $row++, 'ตัวเลข (Number)', 'ห้ามใส่ comma เช่น 36000 (ไม่ใช่ 36,000) | ทศนิยมใช้จุด (.) เช่น 2860.50');
        $this->insLine($sheet, $row++, 'ช่องว่าง (Empty)', 'Optional ที่ไม่มีข้อมูล = เว้นว่าง ห้ามใส่ขีด (-) N/A ไม่มี หรือ ว่าง');
        $this->insLine($sheet, $row++, 'Email', 'พิมพ์เล็ก ห้ามมีช่องว่างนำหน้า/ตามหลัง (เช่น "user@example.com ")');
        $this->insLine($sheet, $row++, 'รหัส (Code)', 'กำหนดเอง ใช้ตัวอักษร+ตัวเลข เช่น V001, C001 - ห้ามซ้ำในไฟล์เดียวกัน');
        $this->insLine($sheet, $row++, 'Currency', 'THB / USD / EUR / JPY / CNY (ใช้ dropdown) - ถ้าไม่ใช่ THB ต้องกรอก exchange_rate ใน PO');
        $this->insLine($sheet, $row++, 'Status', 'เลือกจาก dropdown ห้ามพิมพ์เอง - ดูรายการใน _Lookups');
        $row++;

        // --- 6. Scenarios ---
        $this->insSection($sheet, $row++, '6. ตัวอย่าง Case การกรอก (Scenarios)');

        $this->insLine($sheet, $row++, 'Case 1: PR-PO-GR งวดเดียว', 'งานเล็ก เช่น จ้างทำของพรีเมียม 1 งวด');
        $this->insLine($sheet, $row++, '   Vendors', '1 แถว (บริษัท ABC)');
        $this->insLine($sheet, $row++, '   PurchaseRequisitions', '1 แถว');
        $this->insLine($sheet, $row++, '   PurchaseOrders', '1 แถว (total_phases = 1)');
        $this->insLine($sheet, $row++, '   GoodsReceipts', '1 แถว (milestone_number = 1)');
        $row++;

        $this->insLine($sheet, $row++, 'Case 2: PR-PO-GR หลายงวด', 'งานต่อเนื่อง เช่น จ้างบริหารข้อมูลรายไตรมาส 4 งวด');
        $this->insLine($sheet, $row++, '   PurchaseRequisitions', '1 แถว');
        $this->insLine($sheet, $row++, '   PurchaseOrders', '1 แถว (total_phases = 4)');
        $this->insLine($sheet, $row++, '   GoodsReceipts', '4 แถว (milestone_number = 1, 2, 3, 4) ใช้ po_number เดียวกันทุกแถว');
        $this->insLine($sheet, $row++, '   PaymentMilestones', '4 แถว (optional) หรือข้ามได้ถ้าไม่แยกงวดจ่าย');
        $row++;

        $this->insLine($sheet, $row++, 'Case 3: PO ที่ยกเลิก', 'PR อนุมัติแล้วแต่ PO ถูก cancel');
        $this->insLine($sheet, $row++, '   PurchaseRequisitions', 'status = approved หรือ cancelled ตามความเป็นจริง');
        $this->insLine($sheet, $row++, '   PurchaseOrders', 'status = cancelled, notes = เหตุผลการยกเลิก');
        $this->insLine($sheet, $row++, '   GoodsReceipts', 'ไม่ต้องกรอก');
        $row++;

        $this->insLine($sheet, $row++, 'Case 4: สกุลเงินต่างประเทศ', 'ซื้อของจากต่างประเทศด้วย USD');
        $this->insLine($sheet, $row++, '   PurchaseRequisitions', 'currency = USD, procurement_budget = 1000 (เป็น USD)');
        $this->insLine($sheet, $row++, '   PurchaseOrders', 'currency = USD, total_amount = 1000, exchange_rate = 36.50 (THB per USD ณ วันออก PO)');
        $this->insLine($sheet, $row++, '   GoodsReceipts', 'received_amount = จำนวนเงินสกุล USD (ไม่ต้องแปลงเป็น THB)');
        $row++;

        $this->insLine($sheet, $row++, 'Case 5: ไม่มี tax_id ของ vendor', 'vendor บุคคลธรรมดา หรือ vendor ที่ยังหา tax_id ไม่ได้');
        $this->insLine($sheet, $row++, '   Vendors.tax_id', 'เว้นว่างได้ - ระบบจะสร้าง placeholder (IMPORT-{vendor_code}) ให้อัตโนมัติ');
        $row++;

        $this->insLine($sheet, $row++, 'Case 6: ผู้อนุมัติ/กรรมการ ไม่มี user ในระบบ', 'บุคคลภายนอกหรือ user เก่าที่ถูกลบ');
        $this->insLine($sheet, $row++, '   Committees', 'กรอกข้อมูลได้ แต่ email ต้องตรงกับ user ที่มีในระบบ (ดู _Lookups > Users)');
        $this->insLine($sheet, $row++, '   ถ้าไม่มี user', 'เว้น committee_code ใน PR ไว้ หรือแจ้งทีม IT ให้สร้าง user ก่อน import');
        $row++;

        // --- 7. Checklist ---
        $this->insSection($sheet, $row++, '7. Checklist ก่อนส่งไฟล์');
        $this->insLine($sheet, $row++, '[ ] ลบแถว EXAMPLE', 'ทุก sheet ต้องไม่มีแถวตัวอย่าง (สีเทา)');
        $this->insLine($sheet, $row++, '[ ] วันที่เป็น YYYY-MM-DD', 'ไม่มีวันที่เป็น text format อื่น หรือ Excel serial');
        $this->insLine($sheet, $row++, '[ ] ไม่มี code ซ้ำ', 'vendor_code, committee_code, pr_number, po_number, gr_number ไม่ซ้ำในไฟล์');
        $this->insLine($sheet, $row++, '[ ] FK ครบ', 'ทุก pr_number/po_number/vendor_code/committee_code ที่อ้างอิง มีอยู่จริงใน sheet ที่ referenced');
        $this->insLine($sheet, $row++, '[ ] REQ ครบทุก column', 'ช่องสีแดงไม่มี cell ว่าง');
        $this->insLine($sheet, $row++, '[ ] Email มี user จริง', 'requester_email, pr_approver_email มีใน _Lookups > Users');
        $this->insLine($sheet, $row++, '[ ] Status ตรงกับความเป็นจริง', 'PR ที่อนุมัติแล้วใช้ approved/completed ไม่ใช่ draft');
        $this->insLine($sheet, $row++, '[ ] ไม่เปลี่ยน header', 'ชื่อ column แถวที่ 1 ของทุก sheet ต้องตรงกับต้นฉบับ');
        $this->insLine($sheet, $row++, '[ ] ยอดเงินตรวจสอบแล้ว', 'SUM ของ received_amount ของ GR ทุกงวด ≈ total_amount ของ PO');
        $this->insLine($sheet, $row++, '[ ] Save เป็น .xlsx', 'ไม่ใช่ .xls หรือ .csv');
        $row++;

        // --- 8. FAQ ---
        $this->insSection($sheet, $row++, '8. คำถามที่พบบ่อย (FAQ)');

        $this->insQa($sheet, $row++, 'Q1', 'PR ใบเดียวแต่มี PO หลายใบ ทำไง?');
        $this->insLine($sheet, $row++, '   A', 'กรอกใน PurchaseOrders หลายแถว ใช้ pr_number เดียวกันทุกแถว แต่ po_number ต่างกัน');
        $row++;

        $this->insQa($sheet, $row++, 'Q2', 'มี GR หลายงวดต่อ PO จะกรอกยังไง?');
        $this->insLine($sheet, $row++, '   A', 'กรอกใน GoodsReceipts หลายแถว ใช้ po_number เดียวกัน, milestone_number = 1, 2, 3... ไม่ซ้ำ, gr_number ต่างกันทุกแถว');
        $row++;

        $this->insQa($sheet, $row++, 'Q3', 'ไม่รู้ company_id กับ department_id ดูจากไหน?');
        $this->insLine($sheet, $row++, '   A', 'ไปที่ sheet _Lookups column Companies (A) และ Departments (D) มี id + ชื่อ list ไว้');
        $row++;

        $this->insQa($sheet, $row++, 'Q4', 'ถ้า user ที่เป็น requester ไม่มีในระบบ?');
        $this->insLine($sheet, $row++, '   A', 'แจ้งทีม IT สร้าง user ก่อน หรือใช้ email admin ชั่วคราว แล้วแก้ภายหลัง');
        $row++;

        $this->insQa($sheet, $row++, 'Q5', 'ไฟล์เดิมมีคำว่า "วิธีตกลงราคา" แต่ dropdown เป็นภาษาอังกฤษ?');
        $this->insLine($sheet, $row++, '   A', 'แปลงเอง: วิธีตกลงราคา → agreement_price, วิธีพิเศษ → special_1, วิธีประมูลโดยเชิญ → invitation_bid, วิธีประมูลทั่วไป → open_bid, วิธีคัดเลือก → selection');
        $row++;

        $this->insQa($sheet, $row++, 'Q6', 'เลข PR/PO ใน Excel เดิมไม่ unique (ซ้ำกัน) ทำไง?');
        $this->insLine($sheet, $row++, '   A', 'ตรวจสอบกับทีมก่อนว่าควรเก็บตัวไหน แล้วเติม suffix เช่น EF10000535-R1 สำหรับตัวที่ revision');
        $row++;

        $this->insQa($sheet, $row++, 'Q7', 'คอลัมน์ที่ไม่มีในไฟล์เดิม (เช่น department_id) ต้องกรอกทุกแถวเลยเหรอ?');
        $this->insLine($sheet, $row++, '   A', 'ใช่ ถ้าเป็น REQ ต้องกรอก - แนะนำหา default (เช่น department ของผู้รับผิดชอบ) แล้ว fill ลงทุกแถวด้วย copy');
        $row++;

        $this->insQa($sheet, $row++, 'Q8', 'ทำไม dropdown ของ date ไม่มี?');
        $this->insLine($sheet, $row++, '   A', 'date ไม่มี dropdown พิมพ์ตรง ๆ ในรูปแบบ YYYY-MM-DD - ระบบจะเช็คตอน import');
        $row++;

        $this->insQa($sheet, $row++, 'Q9', 'ต้องการเพิ่มแถวมากกว่า 500 แถว ทำได้มั้ย?');
        $this->insLine($sheet, $row++, '   A', 'ได้ แต่ dropdown validation ที่ setup ไว้จะครอบคลุมแค่ 500-1000 แถวแรก - copy cell ที่มี dropdown แล้ว paste ลงแถวใหม่');
        $row++;

        $this->insQa($sheet, $row++, 'Q10', 'ส่งไฟล์กลับทีม IT แล้ว ระบบจะใช้เวลาเท่าไหร่?');
        $this->insLine($sheet, $row++, '   A', 'dry-run ใช้เวลาไม่ถึง 1 นาที import จริงขึ้นอยู่กับจำนวนแถว - 100 PR ใช้เวลา ~30 วินาที');
        $row++;

        // --- 9. Glossary ---
        $this->insSection($sheet, $row++, '9. คำศัพท์ (Glossary)');
        $this->insLine($sheet, $row++, 'PR (Purchase Requisition)', 'ใบขอซื้อ/ขอจ้าง - เอกสารขออนุมัติจัดซื้อจากแผนก');
        $this->insLine($sheet, $row++, 'PO (Purchase Order)', 'ใบสั่งซื้อ/สั่งจ้าง - เอกสารที่ส่งให้ vendor หลัง PR อนุมัติ');
        $this->insLine($sheet, $row++, 'GR (Goods Receipt)', 'ใบตรวจรับ - เอกสารยืนยันว่ารับของ/รับงานแล้ว');
        $this->insLine($sheet, $row++, 'TOR (Terms of Reference)', 'ข้อกำหนดของงาน - ระบุขอบเขต/เงื่อนไขการจัดหา (ไม่ได้ import ในไฟล์นี้)');
        $this->insLine($sheet, $row++, 'Vendor', 'ผู้ขาย/ผู้รับจ้าง (บริษัทหรือบุคคล)');
        $this->insLine($sheet, $row++, 'Committee', 'กรรมการ - กรรมการจัดหา (procurement) / ตรวจรับ (inspection) / ผู้อนุมัติ (approver)');
        $this->insLine($sheet, $row++, 'Milestone (งวด)', 'งวดส่งมอบ/ชำระเงิน - 1 PO อาจแบ่งเป็นหลายงวด');
        $this->insLine($sheet, $row++, 'SLA', 'Service Level Agreement - มาตรฐานเวลาทำงาน (ระบบคำนวณจาก submitted_at / pr_approved_at)');
        $this->insLine($sheet, $row++, 'Enum', 'ค่าที่กำหนดล่วงหน้า - ต้องเลือกจากรายการ ห้ามพิมพ์เอง');
        $this->insLine($sheet, $row++, 'FK (Foreign Key)', 'คีย์อ้างอิง - ใช้เชื่อมข้อมูลข้าม sheet (เช่น pr_number ใน PO ต้องตรงกับ pr_number ใน PR)');
        $this->insLine($sheet, $row++, 'Dry-run', 'ทดลองรัน import โดยไม่บันทึกลง DB - ใช้ตรวจ error ก่อน');
        $this->insLine($sheet, $row++, 'Historical record', 'ข้อมูลเก่าที่ import เข้ามา - ข้าม workflow อนุมัติและไม่ส่ง notification');
        $row++;

        // --- 10. Contact ---
        $this->insSection($sheet, $row++, '10. ติดต่อ / สนับสนุน');
        $this->insLine($sheet, $row++, 'ทีม IT', 'หากไฟล์มีปัญหาหรือต้องการเพิ่ม user/department ในระบบ');
        $this->insLine($sheet, $row++, 'ทีม Procurement', 'หากต้องการความช่วยเหลือเรื่องข้อมูล/วิธีกรอก');
        $this->insLine($sheet, $row++, 'คำสั่ง import (สำหรับ IT)', 'php artisan procurement:import-excel {path} --dry-run  (ตรวจสอบก่อน)');
        $this->insLine($sheet, $row++, '', 'php artisan procurement:import-excel {path}             (import จริง)');

        $totalRows = $row;

        // --- Styling ---
        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(110);
        $sheet->getStyle('A1:B' . $totalRows)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:B' . $totalRows)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

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
        $sheet->setCellValue('A' . $row, $title);
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
            $sheet->setCellValue('A' . $row, $label);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        }
        $sheet->setCellValue('B' . $row, $text);
    }

    /** Write a title row (spans both columns). */
    private function insTitle(Worksheet $sheet, int $row, string $text): void
    {
        $sheet->setCellValue('A' . $row, $text);
    }

    /** Write a Q&A style row (Q label in orange). */
    private function insQa(Worksheet $sheet, int $row, string $qLabel, string $question): void
    {
        $sheet->setCellValue('A' . $row, $qLabel);
        $sheet->setCellValue('B' . $row, $question);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->getColor()->setRGB('C65911');
        $sheet->getStyle('B' . $row)->getFont()->setBold(true);
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
            $sheet->setCellValue('A3', '(error loading: ' . $e->getMessage() . ')');
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
        $sheet->setCellValue($startCol . '1', $title);
        $this->styleHeader($sheet, "{$startCol}1", 'FFE699');

        $row = 2;
        foreach ($values as $v) {
            $sheet->setCellValue($startCol . $row, $v);
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

        $this->addDropdown($sheet, 'I', self::VENDOR_STATUSES, 5, 500);

        $this->autoSize($sheet, 'A', 'J');
    }

    private function buildCommitteesSheet(Spreadsheet $wb): void
    {
        $sheet = $wb->createSheet();
        $sheet->setTitle('Committees');

        $headers = [
            ['committee_code', 'REQ', 'Unique code เช่น C001, C002'],
            ['name_th', 'REQ', 'ชื่อ-นามสกุล (ภาษาไทย)'],
            ['email', 'REQ', 'อีเมล (ใช้ match user ในระบบ)'],
            ['default_role', 'OPT', 'procurement / inspection / approver'],
            ['position', 'OPT', 'ตำแหน่ง (optional)'],
        ];

        $this->writeHeaders($sheet, $headers);

        $example = ['C001', 'นางสาวตัวอย่าง ทดสอบ', 'example.t@innobicasia.com', 'procurement', 'ผู้จัดการ'];
        $this->writeExampleRow($sheet, 4, $example);

        $this->addDropdown($sheet, 'D', self::COMMITTEE_ROLES, 5, 500);

        $this->autoSize($sheet, 'A', 'F');
    }

    private function buildPurchaseRequisitionsSheet(Spreadsheet $wb): void
    {
        $sheet = $wb->createSheet();
        $sheet->setTitle('PurchaseRequisitions');

        $headers = [
            ['pr_number', 'REQ', 'เลขที่ PR (unique) เช่น EF10000535'],
            ['title', 'REQ', 'ชื่องาน'],
            ['description', 'OPT', 'รายละเอียด'],
            ['company_id', 'REQ', 'อ้างอิง _Lookups > Companies'],
            ['department_id', 'REQ', 'อ้างอิง _Lookups > Departments'],
            ['requester_email', 'REQ', 'อีเมลผู้ขอ (ต้องมี user ในระบบ)'],
            ['procurement_method', 'REQ', 'agreement_price / invitation_bid / open_bid / special_1 / special_2 / selection'],
            ['category', 'OPT', 'ดู _Lookups > Categories'],
            ['work_type', 'OPT', 'buy / hire / rent'],
            ['form_category', 'OPT', 'act_based / law_based'],
            ['clause_number', 'OPT', 'ข้อที่ (ถ้ามี)'],
            ['currency', 'REQ', 'THB / USD / EUR'],
            ['procurement_budget', 'REQ', 'วงเงินก่อน Vat (ตัวเลข)'],
            ['negotiated_price', 'OPT', 'ราคาที่ต่อรองได้'],
            ['request_date', 'REQ', 'YYYY-MM-DD วันที่ยื่น PR'],
            ['submitted_at', 'OPT', 'YYYY-MM-DD (SLA) วันที่รับเรื่อง'],
            ['pr_approved_at', 'OPT', 'YYYY-MM-DD วันที่อนุมัติ PR'],
            ['required_date', 'REQ', 'YYYY-MM-DD วันที่ต้องการของ'],
            ['pr_approver_email', 'OPT', 'อีเมลผู้อนุมัติ PR'],
            ['procurement_committee_code', 'OPT', 'อ้างอิง Committees.committee_code'],
            ['inspection_committee_code', 'OPT', 'อ้างอิง Committees.committee_code'],
            ['status', 'REQ', 'historical ใช้ approved หรือ completed'],
            ['notes', 'OPT', 'หมายเหตุ'],
        ];

        $this->writeHeaders($sheet, $headers);

        $example = [
            'EF10000535', 'จ้างบริหารข้อมูลหลักลูกค้าและผลิตภัณฑ์', '',
            1, 1, 'kanokwan.k@innobicasia.com',
            'agreement_price', 'general_services', 'hire', 'act_based', '0',
            'THB', 36000, 0,
            '2024-12-25', '2024-12-28', '2024-12-30', '2025-12-31',
            'kittinat.s@innobicasia.com', 'C001', 'C002',
            'completed', 'Imported from Excel 2568',
        ];
        $this->writeExampleRow($sheet, 4, $example);

        $this->addDropdown($sheet, 'G', self::PROCUREMENT_METHODS, 5, 500);
        $this->addDropdown($sheet, 'H', self::CATEGORIES, 5, 500);
        $this->addDropdown($sheet, 'I', self::WORK_TYPES, 5, 500);
        $this->addDropdown($sheet, 'J', self::FORM_CATEGORIES, 5, 500);
        $this->addDropdown($sheet, 'L', self::CURRENCIES, 5, 500);
        $this->addDropdown($sheet, 'V', self::PR_STATUSES, 5, 500);

        $this->autoSize($sheet, 'A', 'X');
    }

    private function buildPurchaseOrdersSheet(Spreadsheet $wb): void
    {
        $sheet = $wb->createSheet();
        $sheet->setTitle('PurchaseOrders');

        $headers = [
            ['po_number', 'REQ', 'เลขที่ PO (unique) เช่น EF31000494'],
            ['pr_number', 'REQ', 'อ้างอิง PurchaseRequisitions.pr_number'],
            ['vendor_code', 'REQ', 'อ้างอิง Vendors.vendor_code'],
            ['po_title', 'OPT', 'ชื่อ PO (default: copy จาก PR.title)'],
            ['work_type', 'OPT', 'buy / hire / rent'],
            ['procurement_method', 'OPT', 'copy จาก PR ถ้าเว้นว่าง'],
            ['order_date', 'REQ', 'YYYY-MM-DD'],
            ['po_approved_at', 'OPT', 'YYYY-MM-DD วันที่อนุมัติ PO'],
            ['total_amount', 'REQ', 'จำนวนเงินรวม'],
            ['currency', 'REQ', 'THB / USD / EUR'],
            ['exchange_rate', 'OPT', 'ถ้าไม่ใช่ THB ต้องกรอก'],
            ['stamp_duty', 'OPT', 'ค่าอากรแสตมป์'],
            ['start_date', 'OPT', 'YYYY-MM-DD วันที่เริ่มสัญญา'],
            ['end_date', 'OPT', 'YYYY-MM-DD วันที่สิ้นสุดสัญญา'],
            ['total_phases', 'OPT', 'จำนวนงวด เช่น 4, 6'],
            ['delivery_location', 'OPT', 'สถานที่ส่งมอบ'],
            ['payment_terms', 'OPT', 'เงื่อนไขการชำระเงิน'],
            ['inspection_committee_code', 'OPT', 'อ้างอิง Committees.committee_code'],
            ['status', 'REQ', 'closed / fully_received / partially_received / cancelled'],
            ['notes', 'OPT', 'หมายเหตุ'],
        ];

        $this->writeHeaders($sheet, $headers);

        $example = [
            'EF31000494', 'EF10000535', 'V001', 'จ้างบริหารข้อมูลหลักลูกค้า',
            'hire', 'agreement_price',
            '2024-12-30', '2024-12-30', 36000, 'THB', '', 0,
            '2024-12-30', '2025-12-31', 4, 'Innobic Nutrition', 'Net 30',
            'C002', 'closed', '',
        ];
        $this->writeExampleRow($sheet, 4, $example);

        $this->addDropdown($sheet, 'E', self::WORK_TYPES, 5, 500);
        $this->addDropdown($sheet, 'F', self::PROCUREMENT_METHODS, 5, 500);
        $this->addDropdown($sheet, 'J', self::CURRENCIES, 5, 500);
        $this->addDropdown($sheet, 'S', self::PO_STATUSES, 5, 500);

        $this->autoSize($sheet, 'A', 'U');
    }

    private function buildGoodsReceiptsSheet(Spreadsheet $wb): void
    {
        $sheet = $wb->createSheet();
        $sheet->setTitle('GoodsReceipts');

        $headers = [
            ['gr_number', 'REQ', 'เลขที่ GR (unique) เช่น EF50000117/2025'],
            ['po_number', 'REQ', 'อ้างอิง PurchaseOrders.po_number'],
            ['milestone_number', 'OPT', 'งวดที่ เช่น 1, 2'],
            ['total_phases', 'OPT', 'งวดทั้งหมด เช่น 4'],
            ['receipt_date', 'REQ', 'YYYY-MM-DD วันที่ตรวจรับในระบบ SAP'],
            ['delivery_date', 'OPT', 'YYYY-MM-DD วันที่ส่งมอบ'],
            ['expected_delivery_date', 'OPT', 'YYYY-MM-DD กำหนดส่งมอบตามสัญญา'],
            ['received_amount', 'REQ', 'จำนวนเงินที่จ่ายในงวดนี้'],
            ['document_reference', 'OPT', 'เลขเอกสารอ้างอิง เช่น INV.7500003901'],
            ['document_date', 'OPT', 'YYYY-MM-DD วันที่เอกสารอ้างอิง'],
            ['inspection_status', 'OPT', 'pending / passed / failed / partial'],
            ['received_by_email', 'OPT', 'อีเมลผู้รับ (ถ้าเว้นว่างใช้ผู้ขอ PR)'],
            ['status', 'REQ', 'default: completed'],
            ['notes', 'OPT', 'หมายเหตุ'],
        ];

        $this->writeHeaders($sheet, $headers);

        $example = [
            'EF50000117/2025', 'EF31000494', 1, 4, '2025-04-21', '2025-04-17',
            '2025-04-17', 2860, 'INV.7500003901', '2025-04-17',
            'passed', 'kanokwan.k@innobicasia.com', 'completed', '',
        ];
        $this->writeExampleRow($sheet, 4, $example);

        $this->addDropdown($sheet, 'K', self::INSPECTION_STATUSES, 5, 1000);
        $this->addDropdown($sheet, 'M', self::GR_STATUSES, 5, 1000);

        $this->autoSize($sheet, 'A', 'O');
    }

    private function buildPaymentMilestonesSheet(Spreadsheet $wb): void
    {
        $sheet = $wb->createSheet();
        $sheet->setTitle('PaymentMilestones');

        $headers = [
            ['po_number', 'REQ', 'อ้างอิง PurchaseOrders.po_number'],
            ['milestone_number', 'REQ', 'งวดที่ (integer)'],
            ['milestone_title', 'OPT', 'ชื่องวด เช่น งวดที่ 1/4'],
            ['percentage', 'OPT', 'เปอร์เซ็นต์ของ PO เช่น 25'],
            ['amount', 'REQ', 'จำนวนเงินงวดนี้'],
            ['due_date', 'OPT', 'YYYY-MM-DD วันครบกำหนด'],
            ['paid_date', 'OPT', 'YYYY-MM-DD วันที่จ่ายจริง'],
            ['paid_amount', 'OPT', 'จำนวนเงินที่จ่ายจริง'],
            ['payment_reference', 'OPT', 'เลขอ้างอิงการจ่าย'],
            ['status', 'REQ', 'pending / due / paid / overdue / cancelled'],
            ['notes', 'OPT', 'หมายเหตุ'],
        ];

        $this->writeHeaders($sheet, $headers);

        $example = [
            'EF31000494', 1, 'งวดที่ 1/4', 25, 9000,
            '2025-03-31', '2025-04-17', 2860, 'PAY-001', 'paid', '',
        ];
        $this->writeExampleRow($sheet, 4, $example);

        $this->addDropdown($sheet, 'J', self::MILESTONE_STATUSES, 5, 1000);

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

            $sheet->setCellValue($col . '1', $name);
            $sheet->setCellValue($col . '2', $requirement);
            $sheet->setCellValue($col . '3', $description);

            $color = match ($requirement) {
                'REQ' => 'FFCCCC',
                'OPT' => 'FFF2CC',
                'AUTO' => 'D9D9D9',
                default => 'FFFFFF',
            };

            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1:' . $col . '3')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($color);

            $sheet->getStyle($col . '1:' . $col . '3')->getBorders()
                ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $sheet->getStyle($col . '3')->getAlignment()->setWrapText(true);
            $sheet->getStyle($col . '3')->getFont()->setItalic(true)->setSize(9);

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
            $sheet->setCellValue($col . $row, $v);
            $col = $this->nextColumn($col);
        }
        // Note in the next column
        $sheet->setCellValue($col . $row, '<- EXAMPLE (ลบออกก่อน import)');
        $sheet->getStyle($col . $row)->getFont()->setBold(true)->getColor()->setRGB('C00000');

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
        $formula = '"' . implode(',', $values) . '"';

        for ($row = $startRow; $row <= $endRow; $row++) {
            $validation = $sheet->getCell($col . $row)->getDataValidation();
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
