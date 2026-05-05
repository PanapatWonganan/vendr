<?php

namespace App\Console\Commands;

use App\Models\CommitteeMember;
use App\Models\Department;
use App\Models\GoodsReceipt;
use App\Models\PaymentMilestone;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * V2 of the historical procurement bulk-import command.
 *
 * Differences from V1 (procurement:import-excel):
 *  - PO item `status` uses 'fully_received' (V1 used 'pending' which is not in
 *    the purchase_order_items.status enum and caused MySQL truncation warnings).
 *  - GR item now sets `purchase_order_item_id` to the freshly-created PO item
 *    (V1 omitted that NOT-NULL FK, causing 1364 errors).
 *  - Caches the freshly-created po_item id per PO so each GR item can FK to it.
 *
 * Behaviour otherwise identical to V1:
 *  - --force to commit; default is dry-run with full rollback
 *  - Wraps everything in a single transaction (rollback on any error)
 *  - Validates enums, dates, required columns, FK refs
 *  - Uses saveQuietly() to bypass observers/events for historical records
 *  - Tags every created record with "Imported from Excel" notes
 */
class ImportProcurementExcelV2 extends Command
{
    protected $signature = 'procurement:import-excel-v2
                            {file : Path to the Excel file}
                            {--force : Actually persist changes (default is dry-run)}
                            {--company= : Default company_id when sheet rows omit it}';

    protected $description = 'V2: Import historical PR / PO / GR / PaymentMilestone data from the Excel template (fixed PO/GR item issues)';

    private const PR_STATUSES = ['draft','pending_approval','approved','rejected','completed','cancelled'];
    private const PO_STATUSES = ['draft','pending_approval','approved','rejected','sent_to_supplier','acknowledged','partially_received','fully_received','closed','cancelled'];
    private const GR_STATUSES = ['draft','pending_review','completed','returned','partially_returned','cancelled'];
    private const INSPECTION_STATUSES = ['pending','passed','failed','partial'];
    private const MILESTONE_STATUSES = ['pending','due','paid','overdue','cancelled'];
    private const VENDOR_STATUSES = ['pending','approved','rejected','suspended'];
    private const PROCUREMENT_METHODS = ['agreement_price','invitation_bid','open_bid','special_1','special_2','selection'];
    private const WORK_TYPES = ['buy','hire','rent'];
    private const FORM_CATEGORIES = ['act_based','law_based'];
    private const CURRENCIES = ['THB','USD','EUR','JPY','CNY'];
    private const COMMITTEE_ROLES = ['procurement','inspection','approver'];

    private array $errors = [];
    private array $vendorCodeMap = [];
    private array $committeeUserMap = [];
    private array $committeeRoleMap = [];
    private array $prMap = [];
    private array $poMap = [];
    /** @var array<int,int>  po_id => po_item_id (single line item created per PO) */
    private array $poItemMap = [];
    private array $userEmailMap = [];
    private int $defaultCompanyId = 2;

    public function handle(): int
    {
        $file = $this->argument('file');
        $force = (bool) $this->option('force');
        if ($this->option('company')) $this->defaultCompanyId = (int) $this->option('company');

        if (!is_file($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $this->info(($force ? '[REAL IMPORT V2]' : '[DRY-RUN V2]') . " Loading {$file} ...");

        try {
            $reader = IOFactory::createReaderForFile($file);
            $reader->setReadDataOnly(true);
            $ss = $reader->load($file);
        } catch (\Throwable $e) {
            $this->error('Failed to open spreadsheet: ' . $e->getMessage());
            return self::FAILURE;
        }

        foreach (User::query()->select('id','email')->get() as $u) {
            $this->userEmailMap[strtolower($u->email)] = $u->id;
        }

        DB::beginTransaction();
        try {
            $this->importVendors($ss);
            $this->importCommittees($ss);
            $this->importPurchaseRequisitions($ss);
            $this->importPurchaseOrders($ss);
            $this->importGoodsReceipts($ss);
            $this->importPaymentMilestones($ss);

            if (!empty($this->errors)) {
                DB::rollBack();
                $this->printErrors();
                $this->error('Import aborted: ' . count($this->errors) . ' error(s) found.');
                return self::FAILURE;
            }

            if ($force) {
                DB::commit();
                $this->info('✅ Import committed successfully.');
            } else {
                DB::rollBack();
                $this->info('✅ Dry-run completed with no errors. Re-run with --force to persist.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Fatal error: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    // -------- Vendors --------
    private function importVendors($ss): void
    {
        $sh = $ss->getSheetByName('Vendors');
        if (!$sh) { $this->addError('Vendors', 0, 'Sheet missing'); return; }
        $highest = $sh->getHighestRowAndColumn();
        $created = $updated = 0;
        $existingByTax = Vendor::query()->whereNotNull('tax_id')->pluck('id','tax_id')->all();

        for ($r = 4; $r <= $highest['row']; $r++) {
            if ($this->rowIsEmpty($sh, $r, $highest['column'])) continue;

            $code = $this->str($sh,'A',$r); $name = $this->str($sh,'B',$r); $taxId = $this->str($sh,'C',$r);
            $workCat=$this->str($sh,'D',$r); $contact=$this->str($sh,'E',$r); $phone=$this->str($sh,'F',$r);
            $email=$this->str($sh,'G',$r); $address=$this->str($sh,'H',$r);
            $status=$this->str($sh,'I',$r) ?: 'approved';

            if ($code === '')  { $this->addError('Vendors', $r, 'vendor_code is required'); continue; }
            if ($name === '')  { $this->addError('Vendors', $r, 'company_name is required'); continue; }
            if (!in_array($status, self::VENDOR_STATUSES, true)) { $this->addError('Vendors', $r, "invalid status '{$status}'"); continue; }
            if (isset($this->vendorCodeMap[$code])) { $this->addError('Vendors', $r, "duplicate vendor_code '{$code}' in file"); continue; }

            $effectiveTax = $taxId !== '' ? $taxId : 'IMPORT-' . $code;
            $payload = [
                'company_id'=>$this->defaultCompanyId,'company_name'=>$name,'tax_id'=>$effectiveTax,
                'address'=>$address ?: null,'work_category'=>$workCat ?: null,
                'contact_name'=>$contact ?: null,'contact_phone'=>$phone ?: null,
                'contact_email'=>$email ?: null,'status'=>$status,
            ];

            if (isset($existingByTax[$effectiveTax])) {
                $vendorId = $existingByTax[$effectiveTax];
                Vendor::where('id',$vendorId)->update($payload); $updated++;
            } else {
                $vendor = Vendor::create($payload); $vendorId = $vendor->id;
                $existingByTax[$effectiveTax] = $vendorId; $created++;
            }
            $this->vendorCodeMap[$code] = $vendorId;
        }
        $this->info("Vendors: created={$created}, updated={$updated}, mapped=" . count($this->vendorCodeMap));
    }

    // -------- Committees --------
    private function importCommittees($ss): void
    {
        $sh = $ss->getSheetByName('Committees');
        if (!$sh) { $this->addError('Committees', 0, 'Sheet missing'); return; }
        $highest = $sh->getHighestRowAndColumn();
        $resolved = 0;

        for ($r = 4; $r <= $highest['row']; $r++) {
            if ($this->rowIsEmpty($sh, $r, $highest['column'])) continue;

            $code=$this->str($sh,'A',$r); $name=$this->str($sh,'B',$r);
            $email=strtolower($this->str($sh,'C',$r));
            $role=$this->str($sh,'D',$r) ?: 'procurement';

            if ($code === '')  { $this->addError('Committees', $r, 'committee_code is required'); continue; }
            if ($email === '') { $this->addError('Committees', $r, 'email is required'); continue; }
            if (!in_array($role, self::COMMITTEE_ROLES, true)) {
                $this->addError('Committees', $r, "invalid default_role '{$role}'"); continue;
            }
            if (isset($this->committeeUserMap[$code])) {
                $this->addError('Committees', $r, "duplicate committee_code '{$code}' in file"); continue;
            }
            if (!isset($this->userEmailMap[$email])) {
                $this->addError('Committees', $r, "email '{$email}' has no matching user (committee_code={$code}, name={$name})");
                continue;
            }
            $this->committeeUserMap[$code] = $this->userEmailMap[$email];
            $this->committeeRoleMap[$code] = $role;
            $resolved++;
        }
        $this->info("Committees: resolved={$resolved}");
    }

    // -------- PR --------
    private function importPurchaseRequisitions($ss): void
    {
        $sh = $ss->getSheetByName('PurchaseRequisitions');
        if (!$sh) { $this->addError('PurchaseRequisitions', 0, 'Sheet missing'); return; }
        $highest = $sh->getHighestRowAndColumn();
        $created = 0;

        for ($r = 4; $r <= $highest['row']; $r++) {
            if ($this->rowIsEmpty($sh, $r, $highest['column'])) continue;

            $prNumber=$this->str($sh,'A',$r); $title=$this->str($sh,'B',$r); $desc=$this->str($sh,'C',$r);
            $companyId=(int)($this->str($sh,'D',$r) ?: $this->defaultCompanyId);
            $deptId=(int)$this->str($sh,'E',$r);
            $reqEmail=strtolower($this->str($sh,'F',$r));
            $method=$this->str($sh,'G',$r);
            $category=$this->str($sh,'H',$r) ?: null;
            $workType=$this->str($sh,'I',$r) ?: null;
            $formCat=$this->str($sh,'J',$r) ?: null;
            $clause=$this->str($sh,'K',$r);
            $currency=strtoupper($this->str($sh,'L',$r) ?: 'THB');
            $budget=$this->num($sh,'M',$r); $negPrice=$this->num($sh,'N',$r);
            $reqDate=$this->date($sh,'O',$r); $submitted=$this->date($sh,'P',$r);
            $approved=$this->date($sh,'Q',$r); $required=$this->date($sh,'R',$r);
            $apprEmail=strtolower($this->str($sh,'S',$r));
            $procCom=$this->str($sh,'T',$r); $inspCom=$this->str($sh,'U',$r);
            $status=$this->str($sh,'V',$r) ?: 'completed';
            $notes=$this->str($sh,'W',$r);

            $bad=false;
            if ($prNumber === '') { $this->addError('PR', $r, 'pr_number required'); $bad=true; }
            if ($title === '')    { $this->addError('PR', $r, 'title required'); $bad=true; }
            if ($deptId === 0 || !Department::find($deptId)) { $this->addError('PR', $r, "department_id {$deptId} not found"); $bad=true; }
            if (!isset($this->userEmailMap[$reqEmail])) { $this->addError('PR', $r, "requester_email '{$reqEmail}' has no user"); $bad=true; }
            if (!in_array($method, self::PROCUREMENT_METHODS, true)) { $this->addError('PR', $r, "invalid procurement_method '{$method}'"); $bad=true; }
            if (!in_array($status, self::PR_STATUSES, true)) { $this->addError('PR', $r, "invalid status '{$status}'"); $bad=true; }
            if (!in_array($currency, self::CURRENCIES, true)) { $this->addError('PR', $r, "invalid currency '{$currency}'"); $bad=true; }
            if ($workType !== null && !in_array($workType, self::WORK_TYPES, true)) { $this->addError('PR', $r, "invalid work_type '{$workType}'"); $bad=true; }
            if ($formCat !== null && !in_array($formCat, self::FORM_CATEGORIES, true)) { $this->addError('PR', $r, "invalid form_category '{$formCat}'"); $bad=true; }
            if ($reqDate === null) { $this->addError('PR', $r, 'request_date required'); $bad=true; }
            if ($required === null){ $this->addError('PR', $r, 'required_date required'); $bad=true; }
            if ($budget === null)  { $this->addError('PR', $r, 'procurement_budget required'); $bad=true; }
            if (isset($this->prMap[$prNumber])) { $this->addError('PR', $r, "duplicate pr_number '{$prNumber}' in file"); $bad=true; }

            $procComId = $inspComId = $apprUserId = null;
            if ($procCom !== '') {
                if (!isset($this->committeeUserMap[$procCom])) { $this->addError('PR', $r, "procurement_committee_code '{$procCom}' not in Committees"); $bad=true; }
                else $procComId = $this->committeeUserMap[$procCom];
            }
            if ($inspCom !== '') {
                if (!isset($this->committeeUserMap[$inspCom])) { $this->addError('PR', $r, "inspection_committee_code '{$inspCom}' not in Committees"); $bad=true; }
                else $inspComId = $this->committeeUserMap[$inspCom];
            }
            if ($apprEmail !== '') {
                if (!isset($this->userEmailMap[$apprEmail])) { $this->addError('PR', $r, "pr_approver_email '{$apprEmail}' has no user"); $bad=true; }
                else $apprUserId = $this->userEmailMap[$apprEmail];
            }
            if ($bad) continue;

            if (PurchaseRequisition::where('pr_number',$prNumber)->where('company_id',$companyId)->exists()) {
                $this->addError('PR', $r, "pr_number '{$prNumber}' already exists in DB (company_id={$companyId})");
                continue;
            }

            $requesterId = $this->userEmailMap[$reqEmail];
            $pr = new PurchaseRequisition();
            $pr->company_id = $companyId;
            $pr->pr_number  = $prNumber;
            $pr->title      = $title;
            $pr->description= $desc ?: null;
            $pr->department_id = $deptId;
            $pr->requester_id  = $requesterId;
            $pr->created_by    = $requesterId;
            $pr->request_date  = $reqDate;
            $pr->required_date = $required;
            $pr->procurement_method = $method;
            $pr->category   = $category;
            $pr->work_type  = $workType;
            $pr->form_category = $formCat;
            $pr->currency   = $currency;
            $pr->procurement_budget = $budget;
            $pr->total_amount       = $budget;
            $pr->procurement_committee_id = $procComId;
            $pr->inspection_committee_id  = $inspComId;
            $pr->pr_approver_id = $apprUserId;
            $pr->status         = $status;
            $pr->submitted_at   = $submitted ?: $reqDate;
            $pr->pr_approved_at = $approved;
            $pr->approved_at    = $approved;
            $pr->approved_by    = $apprUserId;
            $pr->notes = trim('Imported from Excel. ' . ($notes ?? ''));
            $pr->saveQuietly();
            $this->prMap[$prNumber] = $pr->id;

            try {
                DB::table('purchase_requisition_items')->insert([
                    'purchase_requisition_id' => $pr->id,
                    'item_code' => 'IMPORT',
                    'description' => $title,
                    'quantity' => 1,
                    'unit_of_measure' => 'lot',
                    'estimated_unit_price' => $budget,
                    'estimated_amount' => $budget,
                    'required_date' => $required,
                    'specification' => $desc,
                    'status' => 'pending',
                    'line_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                $this->addError('PR', $r, 'failed to insert PR item: ' . $e->getMessage());
            }

            $created++;
        }
        $this->info("PR: created={$created}, mapped=" . count($this->prMap));
    }

    // -------- PO --------
    private function importPurchaseOrders($ss): void
    {
        $sh = $ss->getSheetByName('PurchaseOrders');
        if (!$sh) { $this->addError('PurchaseOrders', 0, 'Sheet missing'); return; }
        $highest = $sh->getHighestRowAndColumn();
        $created = 0;

        for ($r = 4; $r <= $highest['row']; $r++) {
            if ($this->rowIsEmpty($sh, $r, $highest['column'])) continue;

            $poNumber=$this->str($sh,'A',$r); $prNumber=$this->str($sh,'B',$r); $vendorCode=$this->str($sh,'C',$r);
            $poTitle=$this->str($sh,'D',$r);
            $workType=$this->str($sh,'E',$r) ?: null;
            $method=$this->str($sh,'F',$r) ?: null;
            $orderDate=$this->date($sh,'G',$r); $approvedAt=$this->date($sh,'H',$r);
            $totalAmt=$this->num($sh,'I',$r);
            $currency=strtoupper($this->str($sh,'J',$r) ?: 'THB');
            $exchange=$this->num($sh,'K',$r); $stamp=$this->num($sh,'L',$r);
            $startDate=$this->date($sh,'M',$r); $endDate=$this->date($sh,'N',$r);
            $totalPhases=(int)$this->str($sh,'O',$r);
            $deliveryLoc=$this->str($sh,'P',$r);
            $payTerms=$this->str($sh,'Q',$r);
            $inspCom=$this->str($sh,'R',$r);
            $status=$this->str($sh,'S',$r) ?: 'closed';
            $notes=$this->str($sh,'T',$r);

            $bad=false;
            if ($poNumber === '')  { $this->addError('PO', $r, 'po_number required'); $bad=true; }
            if ($prNumber === '')  { $this->addError('PO', $r, 'pr_number required'); $bad=true; }
            if ($vendorCode === ''){ $this->addError('PO', $r, 'vendor_code required'); $bad=true; }
            if ($orderDate === null) { $this->addError('PO', $r, 'order_date required'); $bad=true; }
            if ($totalAmt === null){ $this->addError('PO', $r, 'total_amount required'); $bad=true; }
            if (!in_array($currency, self::CURRENCIES, true)) { $this->addError('PO', $r, "invalid currency '{$currency}'"); $bad=true; }
            if (!in_array($status, self::PO_STATUSES, true)) { $this->addError('PO', $r, "invalid status '{$status}'"); $bad=true; }
            if ($method !== null && !in_array($method, self::PROCUREMENT_METHODS, true)) { $this->addError('PO', $r, "invalid procurement_method '{$method}'"); $bad=true; }
            if ($workType !== null && !in_array($workType, self::WORK_TYPES, true)) { $this->addError('PO', $r, "invalid work_type '{$workType}'"); $bad=true; }
            if ($currency !== 'THB' && ($exchange === null || $exchange <= 0)) { $this->addError('PO', $r, "exchange_rate required when currency != THB"); $bad=true; }
            if (!isset($this->prMap[$prNumber])) { $this->addError('PO', $r, "pr_number '{$prNumber}' not found in PR sheet"); $bad=true; }
            if (!isset($this->vendorCodeMap[$vendorCode])) { $this->addError('PO', $r, "vendor_code '{$vendorCode}' not found in Vendors sheet"); $bad=true; }
            if (isset($this->poMap[$poNumber])) { $this->addError('PO', $r, "duplicate po_number '{$poNumber}' in file"); $bad=true; }

            $inspComId = null;
            if ($inspCom !== '') {
                if (!isset($this->committeeUserMap[$inspCom])) { $this->addError('PO', $r, "inspection_committee_code '{$inspCom}' not in Committees"); $bad=true; }
                else $inspComId = $this->committeeUserMap[$inspCom];
            }
            if ($bad) continue;

            if (PurchaseOrder::where('po_number',$poNumber)->exists()) {
                $this->addError('PO', $r, "po_number '{$poNumber}' already exists in DB"); continue;
            }

            $prId = $this->prMap[$prNumber];
            $pr   = PurchaseRequisition::find($prId);
            $vendorId = $this->vendorCodeMap[$vendorCode];
            $vendor   = Vendor::find($vendorId);

            $po = new PurchaseOrder();
            $po->company_id = $pr->company_id;
            $po->purchase_requisition_id = $prId;
            $po->pr_id = $prId;
            $po->po_number = $poNumber;
            $po->vendor_id = $vendorId;
            $po->vendor_name = $vendor?->company_name;
            $po->po_title = $poTitle ?: $pr->title;
            $po->work_type = $workType ?: $pr->work_type;
            $po->procurement_method = $method ?: $pr->procurement_method;
            $po->form_category = $pr->form_category;
            $po->department_id = $pr->department_id;
            $po->order_date = $orderDate;
            $po->expected_delivery_date = $endDate ?: $orderDate;
            $po->subtotal = $totalAmt;
            $po->total_amount = $totalAmt;
            $po->stamp_duty = $stamp ?? 0;
            $po->currency = $currency;
            $po->exchange_rate = $exchange ?: ($currency === 'THB' ? 1 : null);
            $po->delivery_address = $deliveryLoc ?: null;
            $po->payment_terms = $payTerms ?: null;
            $po->status = $status;
            $po->inspection_committee_id = $inspComId;
            $po->approved_by = $pr->pr_approver_id;
            $po->approved_at = $approvedAt;
            $po->po_created_at = $orderDate;
            $po->po_approved_at = $approvedAt;
            $po->closed_at = in_array($status, ['closed','fully_received']) ? ($endDate ?: $approvedAt) : null;
            $po->created_by = $pr->requester_id;
            $po->notes = trim('Imported from Excel. ' . ($notes ?? ''));
            $po->saveQuietly();
            $this->poMap[$poNumber] = $po->id;

            // PO item snapshot — status must be in ('ordered','partially_received','fully_received','cancelled')
            // Historical imports represent closed POs so we use 'fully_received'.
            try {
                $itemStatus = match ($po->status) {
                    'cancelled' => 'cancelled',
                    'partially_received' => 'partially_received',
                    'fully_received', 'closed' => 'fully_received',
                    default => 'ordered',
                };
                $poItemId = DB::table('purchase_order_items')->insertGetId([
                    'purchase_order_id' => $po->id,
                    'item_code' => 'IMPORT',
                    'description' => $poTitle ?: $pr->title,
                    'quantity' => 1,
                    'unit_of_measure' => 'lot',
                    'unit_price' => $totalAmt,
                    'line_total' => $totalAmt,
                    'expected_delivery_date' => $endDate ?: $orderDate,
                    'status' => $itemStatus,
                    'line_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->poItemMap[$po->id] = $poItemId;
            } catch (\Throwable $e) {
                $this->addError('PO', $r, 'failed to insert PO item: ' . $e->getMessage());
            }

            if ($inspComId) {
                CommitteeMember::create([
                    'company_id' => $po->company_id,
                    'purchase_order_id' => $po->id,
                    'user_id' => $inspComId,
                    'role' => 'chairman',
                    'assigned_date' => $orderDate,
                    'is_active' => true,
                ]);
            }
            if ($pr->procurement_committee_id && $pr->procurement_committee_id !== $inspComId) {
                CommitteeMember::create([
                    'company_id' => $po->company_id,
                    'purchase_order_id' => $po->id,
                    'user_id' => $pr->procurement_committee_id,
                    'role' => 'member',
                    'assigned_date' => $orderDate,
                    'is_active' => true,
                ]);
            }

            $created++;
        }
        $this->info("PO: created={$created}, mapped=" . count($this->poMap));
    }

    // -------- GR --------
    private function importGoodsReceipts($ss): void
    {
        $sh = $ss->getSheetByName('GoodsReceipts');
        if (!$sh) { $this->addError('GoodsReceipts', 0, 'Sheet missing'); return; }
        $highest = $sh->getHighestRowAndColumn();
        $created = 0;
        $seen = [];

        for ($r = 4; $r <= $highest['row']; $r++) {
            if ($this->rowIsEmpty($sh, $r, $highest['column'])) continue;

            $grNumber=$this->str($sh,'A',$r); $poNumber=$this->str($sh,'B',$r);
            $milestone=(int)$this->str($sh,'C',$r);
            $totalPhases=(int)$this->str($sh,'D',$r);
            $receiptDate=$this->date($sh,'E',$r);
            $delivDate=$this->date($sh,'F',$r);
            $expectedDt=$this->date($sh,'G',$r);
            $amount=$this->num($sh,'H',$r);
            $docRef=$this->str($sh,'I',$r); $docDate=$this->date($sh,'J',$r);
            $inspect=$this->str($sh,'K',$r) ?: 'passed';
            $rcvEmail=strtolower($this->str($sh,'L',$r));
            $status=$this->str($sh,'M',$r) ?: 'completed';
            $notes=$this->str($sh,'N',$r);

            $bad=false;
            if ($grNumber === '') { $this->addError('GR', $r, 'gr_number required'); $bad=true; }
            if ($poNumber === '') { $this->addError('GR', $r, 'po_number required'); $bad=true; }
            if ($receiptDate === null) { $this->addError('GR', $r, 'receipt_date required'); $bad=true; }
            if ($amount === null) { $this->addError('GR', $r, 'received_amount required'); $bad=true; }
            if (!in_array($inspect, self::INSPECTION_STATUSES, true)) { $this->addError('GR', $r, "invalid inspection_status '{$inspect}'"); $bad=true; }
            if (!in_array($status, self::GR_STATUSES, true)) { $this->addError('GR', $r, "invalid status '{$status}'"); $bad=true; }
            if (!isset($this->poMap[$poNumber])) { $this->addError('GR', $r, "po_number '{$poNumber}' not found"); $bad=true; }
            if (isset($seen[$grNumber])) { $this->addError('GR', $r, "duplicate gr_number '{$grNumber}' in file"); $bad=true; }
            $rcvUserId = null;
            if ($rcvEmail !== '') {
                if (!isset($this->userEmailMap[$rcvEmail])) { $this->addError('GR', $r, "received_by_email '{$rcvEmail}' has no user"); $bad=true; }
                else $rcvUserId = $this->userEmailMap[$rcvEmail];
            }
            if ($bad) continue;

            if (GoodsReceipt::where('gr_number',$grNumber)->exists()) {
                $this->addError('GR', $r, "gr_number '{$grNumber}' already exists in DB"); continue;
            }

            $poId = $this->poMap[$poNumber];
            $po   = PurchaseOrder::find($poId);
            $poItemId = $this->poItemMap[$poId] ?? null;
            if ($poItemId === null) {
                $this->addError('GR', $r, "internal: PO item id missing for po_id={$poId}"); continue;
            }

            $gr = new GoodsReceipt();
            $gr->company_id = $po->company_id;
            $gr->gr_number = $grNumber;
            $gr->receipt_number = $grNumber;
            $gr->purchase_order_id = $poId;
            $gr->vendor_id = $po->vendor_id;
            $gr->inspection_committee_id = $po->inspection_committee_id;
            $gr->receipt_date = $receiptDate;
            $gr->delivery_milestone = $milestone ?: 1;
            $gr->milestone_description = $totalPhases ? "งวดที่ {$milestone}/{$totalPhases}" : null;
            $gr->milestone_percentage = $totalPhases ? round(100 / $totalPhases, 2) : null;
            $gr->inspection_status = $inspect;
            $gr->inspection_notes = $docRef ? "เอกสารอ้างอิง: {$docRef}" . ($docDate ? " ({$docDate})" : '') : null;
            $gr->delivery_note_number = $docRef ?: null;
            $gr->status = $status;
            $gr->received_by = $rcvUserId;
            $gr->reviewed_by = $rcvUserId;
            $gr->reviewed_at = $receiptDate;
            $gr->is_quality_checked = $inspect === 'passed';
            $gr->quality_checked_by = $rcvUserId;
            $gr->quality_checked_at = $receiptDate;
            $gr->created_by = $rcvUserId;
            $gr->notes = trim("Imported from Excel. delivery_date={$delivDate}, expected_delivery={$expectedDt}, amount={$amount}. " . ($notes ?? ''));
            $gr->saveQuietly();
            $seen[$grNumber] = true;

            // GR item — must FK to the PO item we just created
            try {
                DB::table('goods_receipt_items')->insert([
                    'goods_receipt_id' => $gr->id,
                    'purchase_order_item_id' => $poItemId,
                    'item_code' => 'IMPORT',
                    'description' => $po->po_title,
                    'received_quantity' => 1,
                    'unit_of_measure' => 'lot',
                    'accepted_quantity' => $inspect === 'passed' ? 1 : 0,
                    'rejected_quantity' => $inspect === 'failed' ? 1 : 0,
                    'quality_status' => $inspect,
                    'remarks' => "received_amount={$amount}",
                    'line_number' => 1,
                    'is_inspected' => true,
                    'requires_inspection' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                $this->addError('GR', $r, 'failed to insert GR item: ' . $e->getMessage());
            }

            $created++;
        }
        $this->info("GR: created={$created}");
    }

    // -------- PaymentMilestones --------
    private function importPaymentMilestones($ss): void
    {
        $sh = $ss->getSheetByName('PaymentMilestones');
        if (!$sh) { $this->addError('PaymentMilestones', 0, 'Sheet missing'); return; }
        $highest = $sh->getHighestRowAndColumn();
        $created = 0;

        for ($r = 4; $r <= $highest['row']; $r++) {
            if ($this->rowIsEmpty($sh, $r, $highest['column'])) continue;

            $poNumber=$this->str($sh,'A',$r);
            $mNumber=(int)$this->str($sh,'B',$r);
            $title=$this->str($sh,'C',$r);
            $percent=$this->num($sh,'D',$r);
            $amount=$this->num($sh,'E',$r);
            $dueDate=$this->date($sh,'F',$r);
            $paidDate=$this->date($sh,'G',$r);
            $paidAmt=$this->num($sh,'H',$r);
            $payRef=$this->str($sh,'I',$r);
            $status=$this->str($sh,'J',$r) ?: 'paid';
            $notes=$this->str($sh,'K',$r);

            $bad=false;
            if ($poNumber === '') { $this->addError('PM', $r, 'po_number required'); $bad=true; }
            if ($mNumber < 1)     { $this->addError('PM', $r, 'milestone_number must be >= 1'); $bad=true; }
            if ($amount === null) { $this->addError('PM', $r, 'amount required'); $bad=true; }
            if (!in_array($status, self::MILESTONE_STATUSES, true)) { $this->addError('PM', $r, "invalid status '{$status}'"); $bad=true; }
            if (!isset($this->poMap[$poNumber])) { $this->addError('PM', $r, "po_number '{$poNumber}' not found"); $bad=true; }
            if ($bad) continue;

            $poId = $this->poMap[$poNumber];
            $po   = PurchaseOrder::find($poId);

            $existing = PaymentMilestone::where('purchase_order_id',$poId)
                ->where('milestone_number',$mNumber)->first();
            if ($existing) {
                $this->addError('PM', $r, "milestone {$mNumber} for PO '{$poNumber}' already exists"); continue;
            }

            $pm = new PaymentMilestone();
            $pm->company_id = $po->company_id;
            $pm->purchase_order_id = $poId;
            $pm->milestone_number = $mNumber;
            $pm->milestone_title = $title ?: "งวดที่ {$mNumber}";
            $pm->amount = $amount;
            $pm->percentage = $percent;
            $pm->due_date = $dueDate;
            $pm->paid_date = $paidDate;
            $pm->paid_amount = $paidAmt;
            $pm->payment_reference = $payRef ?: null;
            $pm->status = $status;
            $pm->payment_notes = trim('Imported from Excel. ' . ($notes ?? ''));
            $pm->created_by = $po->created_by;
            $pm->paid_by = $status === 'paid' ? $po->created_by : null;
            $pm->saveQuietly();
            $created++;
        }
        $this->info("PaymentMilestones: created={$created}");
    }

    // -------- helpers --------
    private function rowIsEmpty($sh, int $row, string $lastCol): bool {
        $cells = $sh->rangeToArray("A{$row}:{$lastCol}{$row}", null, true, false, false);
        foreach ($cells[0] ?? [] as $v) if ($v !== null && trim((string)$v) !== '') return false;
        return true;
    }

    private function str($sh, string $col, int $row): string {
        $v = $sh->getCell($col.$row)->getValue();
        if (is_bool($v)) return $v ? '1' : '0';
        if (is_array($v)) return '';
        return trim((string) ($v ?? ''));
    }

    private function num($sh, string $col, int $row): ?float {
        $v = $sh->getCell($col.$row)->getValue();
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) return (float) $v;
        $s = preg_replace('/[^0-9.\-]/', '', (string) $v);
        return $s === '' ? null : (float) $s;
    }

    private function date($sh, string $col, int $row): ?string {
        $v = $sh->getCell($col.$row)->getValue();
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) {
            try { return ExcelDate::excelToDateTimeObject((float)$v)->format('Y-m-d'); }
            catch (\Throwable $e) { return null; }
        }
        $s = trim((string) $v);
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $s)) return substr($s, 0, 10);
        $ts = strtotime($s);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    private function addError(string $sheet, int $row, string $msg): void {
        $this->errors[] = [$sheet, $row, $msg];
    }

    private function printErrors(): void {
        $this->newLine();
        $this->error('Errors found:');
        $bySheet = [];
        foreach ($this->errors as [$sheet, $row, $msg]) $bySheet[$sheet][] = "  R{$row}: {$msg}";
        foreach ($bySheet as $sheet => $msgs) {
            $this->line("[$sheet] " . count($msgs));
            foreach ($msgs as $m) $this->line($m);
        }
    }
}
