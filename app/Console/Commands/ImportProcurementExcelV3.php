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
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * V3 — one-shot import: a single command run does everything.
 *
 * Differences from V2 (procurement:import-excel-v2):
 *  - Commits by default when the file validates cleanly (no --force dance);
 *    --dry-run is the opt-in for validate-without-persist.
 *  - --replace-existing and --auto-create-users behaviour is ON by default
 *    (opt out with --no-replace / --no-auto-users).
 *  - Template columns follow the current schema: PR gains purpose,
 *    budget_code, project_code, received_date (SLA Excel stage); GR gains
 *    milestone_percentage override and persists received_amount into
 *    goods_receipts.milestone_amount; PO gains sap_po_number.
 *  - PaymentMilestones sheet is optional — POs with GRs but no milestone rows
 *    get milestones auto-generated from their GRs ("งวดที่ n/m" titles), and
 *    GRs are linked to milestones via payment_milestone_id.
 *  - GR status list matches the DB enum (V2 accepted 'pending_review' which
 *    the enum does not contain).
 *  - payment_milestones.due_date is NOT NULL — falls back to
 *    paid_date → PO end date → order_date.
 *  - Runs sla:backfill automatically after a committed import (--skip-sla to
 *    opt out).
 *
 * Still identical to V2 in spirit:
 *  - Single transaction, all-or-nothing (any validation error aborts).
 *  - saveQuietly() everywhere — no observers/events/notifications.
 *  - Every record tagged with "Imported from Excel".
 */
class ImportProcurementExcelV3 extends Command
{
    protected $signature = 'procurement:import-excel-v3
                            {file : Path to the Excel file (V3 template)}
                            {--dry-run : Validate everything and roll back (no data persisted)}
                            {--company= : Default company_id when sheet rows omit it (default: 2)}
                            {--no-replace : Skip PR/PO/GR numbers that already exist instead of replacing them}
                            {--no-auto-users : Error on unknown emails instead of auto-creating users}
                            {--skip-sla : Do not run sla:backfill after import}';

    protected $description = 'V3: One-shot import of historical PR / PO / GR / PaymentMilestone data (validates, imports, links milestones, backfills SLA in a single run)';

    private const FX_FALLBACK = ['USD' => 35.0, 'EUR' => 38.0, 'JPY' => 0.24, 'CNY' => 5.0];

    private const PR_STATUSES = ['draft', 'pending_approval', 'approved', 'rejected', 'completed', 'cancelled'];

    private const PO_STATUSES = ['draft', 'pending_approval', 'approved', 'rejected', 'sent_to_supplier', 'acknowledged', 'partially_received', 'fully_received', 'closed', 'cancelled'];

    // Matches the goods_receipts.status DB enum ('pending_review' is not in it).
    private const GR_STATUSES = ['draft', 'completed', 'returned', 'partially_returned', 'cancelled'];

    private const INSPECTION_STATUSES = ['pending', 'passed', 'failed', 'partial'];

    private const MILESTONE_STATUSES = ['pending', 'due', 'paid', 'overdue', 'cancelled'];

    private const VENDOR_STATUSES = ['pending', 'approved', 'rejected', 'suspended'];

    private const PROCUREMENT_METHODS = ['agreement_price', 'invitation_bid', 'open_bid', 'special_1', 'special_2', 'selection'];

    private const WORK_TYPES = ['buy', 'hire', 'rent'];

    private const FORM_CATEGORIES = ['act_based', 'law_based'];

    private const CURRENCIES = ['THB', 'USD', 'EUR', 'JPY', 'CNY'];

    private const COMMITTEE_ROLES = ['procurement', 'inspection', 'approver'];

    private array $errors = [];

    private array $vendorCodeMap = [];

    private array $committeeUserMap = [];

    private array $prMap = [];

    private array $poMap = [];

    /** @var array<int,int> po_id => po_item_id (single line item created per PO) */
    private array $poItemMap = [];

    /** @var array<int, array<int, array{gr_id:int, milestone:int, total_phases:int, amount:float, percentage:?float, receipt_date:string, status:string}>> po_id => GR rows */
    private array $grByPo = [];

    /** @var array<int, true> po_id => has explicit rows in the PaymentMilestones sheet */
    private array $poHasExplicitMilestones = [];

    private array $userEmailMap = [];

    private int $defaultCompanyId = 2;

    private bool $replaceExisting = true;

    private bool $autoCreateUsers = true;

    public function handle(): int
    {
        $file = $this->argument('file');
        $dryRun = (bool) $this->option('dry-run');
        if ($this->option('company')) {
            $this->defaultCompanyId = (int) $this->option('company');
        }
        $this->replaceExisting = ! $this->option('no-replace');
        $this->autoCreateUsers = ! $this->option('no-auto-users');

        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $this->info(($dryRun ? '[DRY-RUN V3]' : '[ONE-SHOT IMPORT V3]')." Loading {$file} ...");

        try {
            $reader = IOFactory::createReaderForFile($file);
            $reader->setReadDataOnly(true);
            $ss = $reader->load($file);
        } catch (\Throwable $e) {
            $this->error('Failed to open spreadsheet: '.$e->getMessage());

            return self::FAILURE;
        }

        foreach (User::query()->select('id', 'email')->get() as $u) {
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
            $this->autoGenerateMilestonesFromGrs();
            $this->linkGrsToMilestones();

            if (! empty($this->errors)) {
                DB::rollBack();
                $this->printErrors();
                $this->error('Import aborted: '.count($this->errors).' error(s) found. Nothing was saved.');

                return self::FAILURE;
            }

            if ($dryRun) {
                DB::rollBack();
                $this->info('✅ Dry-run completed with no errors. Re-run without --dry-run to persist.');

                return self::SUCCESS;
            }

            DB::commit();
            $this->info('✅ Import committed successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Fatal error: '.$e->getMessage());
            $this->line($e->getTraceAsString());

            return self::FAILURE;
        }

        if (! $this->option('skip-sla')) {
            $this->info('Running SLA backfill ...');
            $this->call('sla:backfill');
        }

        return self::SUCCESS;
    }

    // -------- Vendors --------
    private function importVendors($ss): void
    {
        $sh = $ss->getSheetByName('Vendors');
        if (! $sh) {
            $this->addError('Vendors', 0, 'Sheet missing');

            return;
        }
        $highest = $sh->getHighestRowAndColumn();
        $created = $updated = 0;
        $existingByTax = Vendor::query()->whereNotNull('tax_id')->pluck('id', 'tax_id')->all();

        for ($r = 4; $r <= $highest['row']; $r++) {
            if ($this->rowIsEmpty($sh, $r, $highest['column'])) {
                continue;
            }

            $code = $this->str($sh, 'A', $r);
            $name = $this->str($sh, 'B', $r);
            $taxId = $this->str($sh, 'C', $r);
            $workCat = $this->str($sh, 'D', $r);
            $contact = $this->str($sh, 'E', $r);
            $phone = $this->str($sh, 'F', $r);
            $email = $this->str($sh, 'G', $r);
            $address = $this->str($sh, 'H', $r);
            $status = $this->str($sh, 'I', $r) ?: 'approved';

            if ($code === '') {
                $this->addError('Vendors', $r, 'vendor_code is required');

                continue;
            }
            if ($name === '') {
                $this->addError('Vendors', $r, 'company_name is required');

                continue;
            }
            if (! in_array($status, self::VENDOR_STATUSES, true)) {
                $this->addError('Vendors', $r, "invalid status '{$status}'");

                continue;
            }
            if (isset($this->vendorCodeMap[$code])) {
                $this->addError('Vendors', $r, "duplicate vendor_code '{$code}' in file");

                continue;
            }

            $effectiveTax = $taxId !== '' ? $taxId : 'IMPORT-'.$code;
            $payload = [
                'company_id' => $this->defaultCompanyId, 'company_name' => $name, 'tax_id' => $effectiveTax,
                'address' => $address !== '' ? $address : '-', 'work_category' => $workCat ?: null,
                'contact_name' => $contact ?: null, 'contact_phone' => $phone ?: null,
                'contact_email' => $email ?: null, 'status' => $status,
            ];

            if (isset($existingByTax[$effectiveTax])) {
                $vendorId = $existingByTax[$effectiveTax];
                Vendor::where('id', $vendorId)->update($payload);
                $updated++;
            } else {
                $vendor = Vendor::create($payload);
                $vendorId = $vendor->id;
                $existingByTax[$effectiveTax] = $vendorId;
                $created++;
            }
            $this->vendorCodeMap[$code] = $vendorId;
        }
        $this->info("Vendors: created={$created}, updated={$updated}, mapped=".count($this->vendorCodeMap));
    }

    // -------- Committees --------
    private function importCommittees($ss): void
    {
        $sh = $ss->getSheetByName('Committees');
        if (! $sh) {
            $this->addError('Committees', 0, 'Sheet missing');

            return;
        }
        $highest = $sh->getHighestRowAndColumn();
        $resolved = 0;

        for ($r = 4; $r <= $highest['row']; $r++) {
            if ($this->rowIsEmpty($sh, $r, $highest['column'])) {
                continue;
            }

            $code = $this->str($sh, 'A', $r);
            $name = $this->str($sh, 'B', $r);
            $email = strtolower($this->str($sh, 'C', $r));
            $role = $this->str($sh, 'D', $r) ?: 'procurement';

            if ($code === '') {
                $this->addError('Committees', $r, 'committee_code is required');

                continue;
            }
            if ($email === '') {
                $this->addError('Committees', $r, 'email is required');

                continue;
            }
            if (! in_array($role, self::COMMITTEE_ROLES, true)) {
                $this->addError('Committees', $r, "invalid default_role '{$role}'");

                continue;
            }
            if (isset($this->committeeUserMap[$code])) {
                $this->addError('Committees', $r, "duplicate committee_code '{$code}' in file");

                continue;
            }
            $userId = $this->resolveUser($email, 'Committees', $r, $name);
            if ($userId === null) {
                continue;
            }
            $this->committeeUserMap[$code] = $userId;
            $resolved++;
        }
        $this->info("Committees: resolved={$resolved}");
    }

    // -------- PR --------
    private function importPurchaseRequisitions($ss): void
    {
        $sh = $ss->getSheetByName('PurchaseRequisitions');
        if (! $sh) {
            $this->addError('PurchaseRequisitions', 0, 'Sheet missing');

            return;
        }
        $highest = $sh->getHighestRowAndColumn();
        $created = 0;

        for ($r = 4; $r <= $highest['row']; $r++) {
            if ($this->rowIsEmpty($sh, $r, $highest['column'])) {
                continue;
            }

            $prNumber = $this->str($sh, 'A', $r);
            $title = $this->str($sh, 'B', $r);
            $purpose = $this->str($sh, 'C', $r);
            $desc = $this->str($sh, 'D', $r);
            $companyId = (int) ($this->str($sh, 'E', $r) ?: $this->defaultCompanyId);
            $deptId = (int) $this->str($sh, 'F', $r);
            $reqEmail = strtolower($this->str($sh, 'G', $r));
            $method = $this->str($sh, 'H', $r);
            $category = $this->str($sh, 'I', $r) ?: null;
            $workType = $this->str($sh, 'J', $r);
            $formCat = $this->str($sh, 'K', $r) ?: null;
            $budgetCode = $this->str($sh, 'L', $r);
            $projectCode = $this->str($sh, 'M', $r);
            $currency = strtoupper($this->str($sh, 'N', $r) ?: 'THB');
            $budget = $this->num($sh, 'O', $r);
            $reqDate = $this->date($sh, 'P', $r);
            $receivedDate = $this->date($sh, 'Q', $r);
            $submitted = $this->date($sh, 'R', $r);
            $approved = $this->date($sh, 'S', $r);
            $required = $this->date($sh, 'T', $r);
            $apprEmail = strtolower($this->str($sh, 'U', $r));
            $procCom = $this->str($sh, 'V', $r);
            $inspCom = $this->str($sh, 'W', $r);
            $status = $this->str($sh, 'X', $r) ?: 'completed';
            $notes = $this->str($sh, 'Y', $r);

            $bad = false;
            if ($prNumber === '') {
                $this->addError('PR', $r, 'pr_number required');
                $bad = true;
            }
            if ($title === '') {
                $this->addError('PR', $r, 'title required');
                $bad = true;
            }
            if ($deptId === 0 || ! Department::find($deptId)) {
                $this->addError('PR', $r, "department_id {$deptId} not found");
                $bad = true;
            }
            $requesterId = $this->resolveUser($reqEmail, 'PR', $r);
            if ($requesterId === null) {
                $bad = true;
            }
            if (! in_array($method, self::PROCUREMENT_METHODS, true)) {
                $this->addError('PR', $r, "invalid procurement_method '{$method}'");
                $bad = true;
            }
            if (! in_array($status, self::PR_STATUSES, true)) {
                $this->addError('PR', $r, "invalid status '{$status}'");
                $bad = true;
            }
            if (! in_array($currency, self::CURRENCIES, true)) {
                $this->addError('PR', $r, "invalid currency '{$currency}'");
                $bad = true;
            }
            // purchase_requisitions.work_type is a NOT NULL enum — required here.
            if (! in_array($workType, self::WORK_TYPES, true)) {
                $this->addError('PR', $r, "invalid or missing work_type '{$workType}' (buy/hire/rent)");
                $bad = true;
            }
            if ($formCat !== null && ! in_array($formCat, self::FORM_CATEGORIES, true)) {
                $this->addError('PR', $r, "invalid form_category '{$formCat}'");
                $bad = true;
            }
            if ($reqDate === null) {
                $this->addError('PR', $r, 'request_date required');
                $bad = true;
            }
            if ($required === null) {
                $this->addError('PR', $r, 'required_date required');
                $bad = true;
            }
            if ($budget === null) {
                $this->addError('PR', $r, 'procurement_budget required');
                $bad = true;
            }
            if (isset($this->prMap[$prNumber])) {
                $this->addError('PR', $r, "duplicate pr_number '{$prNumber}' in file");
                $bad = true;
            }

            $procComId = $inspComId = $apprUserId = null;
            if ($procCom !== '') {
                if (! isset($this->committeeUserMap[$procCom])) {
                    $this->addError('PR', $r, "procurement_committee_code '{$procCom}' not in Committees");
                    $bad = true;
                } else {
                    $procComId = $this->committeeUserMap[$procCom];
                }
            }
            if ($inspCom !== '') {
                if (! isset($this->committeeUserMap[$inspCom])) {
                    $this->addError('PR', $r, "inspection_committee_code '{$inspCom}' not in Committees");
                    $bad = true;
                } else {
                    $inspComId = $this->committeeUserMap[$inspCom];
                }
            }
            if ($apprEmail !== '') {
                $apprUserId = $this->resolveUser($apprEmail, 'PR', $r);
                if ($apprUserId === null) {
                    $bad = true;
                }
            }
            if ($bad) {
                continue;
            }

            $existingPr = PurchaseRequisition::where('pr_number', $prNumber)->where('company_id', $companyId)->first();
            if ($existingPr) {
                if (! $this->replaceExisting) {
                    $this->line("  ~ PR R{$r}: skipping existing pr_number '{$prNumber}' (--no-replace)");
                    $this->prMap[$prNumber] = $existingPr->id;

                    continue;
                }
                $this->cascadeDeletePr($existingPr->id, $r, $prNumber);
            }

            $pr = new PurchaseRequisition;
            $pr->company_id = $companyId;
            $pr->pr_number = $prNumber;
            $pr->title = $title;
            $pr->purpose = $purpose ?: null;
            $pr->description = $desc ?: null;
            $pr->department_id = $deptId;
            $pr->requester_id = $requesterId;
            $pr->created_by = $requesterId;
            $pr->request_date = $reqDate;
            $pr->received_date = $receivedDate;
            $pr->required_date = $required;
            $pr->procurement_method = $method;
            $pr->category = $category;
            $pr->work_type = $workType;
            $pr->form_category = $formCat;
            $pr->budget_code = $budgetCode ?: null;
            $pr->project_code = $projectCode ?: null;
            $pr->currency = $currency;
            $pr->procurement_budget = $budget;
            $pr->total_amount = $budget;
            $pr->procurement_committee_id = $procComId;
            $pr->inspection_committee_id = $inspComId;
            $pr->pr_approver_id = $apprUserId;
            $pr->status = $status;
            $pr->submitted_at = $submitted ?: $reqDate;
            $pr->pr_approved_at = $approved;
            $pr->approved_at = $approved;
            $pr->approved_by = $apprUserId;
            $pr->notes = trim('Imported from Excel. '.($notes ?? ''));
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
                $this->addError('PR', $r, 'failed to insert PR item: '.$e->getMessage());
            }

            $created++;
        }
        $this->info("PR: created={$created}, mapped=".count($this->prMap));
    }

    // -------- PO --------
    private function importPurchaseOrders($ss): void
    {
        $sh = $ss->getSheetByName('PurchaseOrders');
        if (! $sh) {
            $this->addError('PurchaseOrders', 0, 'Sheet missing');

            return;
        }
        $highest = $sh->getHighestRowAndColumn();
        $created = 0;

        for ($r = 4; $r <= $highest['row']; $r++) {
            if ($this->rowIsEmpty($sh, $r, $highest['column'])) {
                continue;
            }

            $poNumber = $this->str($sh, 'A', $r);
            $prNumber = $this->str($sh, 'B', $r);
            $vendorCode = $this->str($sh, 'C', $r);
            $poTitle = $this->str($sh, 'D', $r);
            $workType = $this->str($sh, 'E', $r) ?: null;
            $method = $this->str($sh, 'F', $r) ?: null;
            $orderDate = $this->date($sh, 'G', $r);
            $approvedAt = $this->date($sh, 'H', $r);
            $totalAmt = $this->num($sh, 'I', $r);
            $currency = strtoupper($this->str($sh, 'J', $r) ?: 'THB');
            $exchange = $this->num($sh, 'K', $r);
            $stamp = $this->num($sh, 'L', $r);
            $startDate = $this->date($sh, 'M', $r);
            $endDate = $this->date($sh, 'N', $r);
            $totalPhases = (int) $this->str($sh, 'O', $r);
            $deliveryLoc = $this->str($sh, 'P', $r);
            $payTerms = $this->str($sh, 'Q', $r);
            $inspCom = $this->str($sh, 'R', $r);
            $status = $this->str($sh, 'S', $r) ?: 'closed';
            $sapPoNumber = $this->str($sh, 'T', $r);
            $notes = $this->str($sh, 'U', $r);

            $bad = false;
            if ($poNumber === '') {
                $this->addError('PO', $r, 'po_number required');
                $bad = true;
            }
            if ($prNumber === '') {
                $this->addError('PO', $r, 'pr_number required');
                $bad = true;
            }
            if ($vendorCode === '') {
                $this->addError('PO', $r, 'vendor_code required');
                $bad = true;
            }
            if ($orderDate === null) {
                $this->addError('PO', $r, 'order_date required');
                $bad = true;
            }
            if ($totalAmt === null) {
                $this->addError('PO', $r, 'total_amount required');
                $bad = true;
            }
            if (! in_array($currency, self::CURRENCIES, true)) {
                $this->addError('PO', $r, "invalid currency '{$currency}'");
                $bad = true;
            }
            if (! in_array($status, self::PO_STATUSES, true)) {
                $this->addError('PO', $r, "invalid status '{$status}'");
                $bad = true;
            }
            if ($method !== null && ! in_array($method, self::PROCUREMENT_METHODS, true)) {
                $this->addError('PO', $r, "invalid procurement_method '{$method}'");
                $bad = true;
            }
            if ($workType !== null && ! in_array($workType, self::WORK_TYPES, true)) {
                $this->addError('PO', $r, "invalid work_type '{$workType}'");
                $bad = true;
            }
            if ($currency !== 'THB' && ($exchange === null || $exchange <= 0)) {
                $fallback = self::FX_FALLBACK[$currency] ?? null;
                if ($fallback !== null) {
                    $exchange = $fallback;
                    $this->line("  ~ PO R{$r}: applied fallback exchange_rate {$currency}={$exchange}");
                } else {
                    $this->addError('PO', $r, "exchange_rate required when currency != THB and no fallback for {$currency}");
                    $bad = true;
                }
            }
            if (! isset($this->prMap[$prNumber])) {
                $this->addError('PO', $r, "pr_number '{$prNumber}' not found in PR sheet");
                $bad = true;
            }
            if (! isset($this->vendorCodeMap[$vendorCode])) {
                $this->addError('PO', $r, "vendor_code '{$vendorCode}' not found in Vendors sheet");
                $bad = true;
            }
            if (isset($this->poMap[$poNumber])) {
                $this->addError('PO', $r, "duplicate po_number '{$poNumber}' in file");
                $bad = true;
            }

            $inspComId = null;
            if ($inspCom !== '') {
                if (! isset($this->committeeUserMap[$inspCom])) {
                    $this->addError('PO', $r, "inspection_committee_code '{$inspCom}' not in Committees");
                    $bad = true;
                } else {
                    $inspComId = $this->committeeUserMap[$inspCom];
                }
            }
            if ($bad) {
                continue;
            }

            $existingPo = PurchaseOrder::where('po_number', $poNumber)->first();
            if ($existingPo) {
                if (! $this->replaceExisting) {
                    $this->line("  ~ PO R{$r}: skipping existing po_number '{$poNumber}' (--no-replace)");
                    $this->poMap[$poNumber] = $existingPo->id;

                    continue;
                }
                $this->cascadeDeletePo($existingPo->id, $r, $poNumber);
            }

            $prId = $this->prMap[$prNumber];
            $pr = PurchaseRequisition::find($prId);
            $vendorId = $this->vendorCodeMap[$vendorCode];
            $vendor = Vendor::find($vendorId);

            $po = new PurchaseOrder;
            $po->company_id = $pr->company_id;
            $po->purchase_requisition_id = $prId;
            $po->pr_id = $prId;
            $po->po_number = $poNumber;
            $po->sap_po_number = $sapPoNumber ?: null;
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
            $po->closed_at = in_array($status, ['closed', 'fully_received']) ? ($endDate ?: $approvedAt) : null;
            $po->created_by = $pr->requester_id;
            $po->notes = trim('Imported from Excel. '.($notes ?? ''));
            $po->saveQuietly();
            $this->poMap[$poNumber] = $po->id;

            // PO item snapshot — status must be in ('ordered','partially_received','fully_received','cancelled').
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
                $this->addError('PO', $r, 'failed to insert PO item: '.$e->getMessage());
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
        $this->info("PO: created={$created}, mapped=".count($this->poMap));
    }

    // -------- GR --------
    private function importGoodsReceipts($ss): void
    {
        $sh = $ss->getSheetByName('GoodsReceipts');
        if (! $sh) {
            $this->addError('GoodsReceipts', 0, 'Sheet missing');

            return;
        }
        $highest = $sh->getHighestRowAndColumn();
        $created = 0;
        $seen = [];

        for ($r = 4; $r <= $highest['row']; $r++) {
            if ($this->rowIsEmpty($sh, $r, $highest['column'])) {
                continue;
            }

            $grNumber = $this->str($sh, 'A', $r);
            $poNumber = $this->str($sh, 'B', $r);
            $milestone = (int) $this->str($sh, 'C', $r);
            $totalPhases = (int) $this->str($sh, 'D', $r);
            $receiptDate = $this->date($sh, 'E', $r);
            $delivDate = $this->date($sh, 'F', $r);
            $expectedDt = $this->date($sh, 'G', $r);
            $amount = $this->num($sh, 'H', $r);
            $pctOverride = $this->num($sh, 'I', $r);
            $docRef = $this->str($sh, 'J', $r);
            $docDate = $this->date($sh, 'K', $r);
            $inspect = $this->str($sh, 'L', $r) ?: 'passed';
            $rcvEmail = strtolower($this->str($sh, 'M', $r));
            $status = $this->str($sh, 'N', $r) ?: 'completed';
            $notes = $this->str($sh, 'O', $r);

            $bad = false;
            if ($grNumber === '') {
                $this->addError('GR', $r, 'gr_number required');
                $bad = true;
            }
            if ($poNumber === '') {
                $this->addError('GR', $r, 'po_number required');
                $bad = true;
            }
            if ($receiptDate === null) {
                if ($delivDate !== null) {
                    $receiptDate = $delivDate;
                    $this->line("  ~ GR R{$r}: receipt_date missing, fell back to delivery_date {$receiptDate}");
                } else {
                    $this->addError('GR', $r, 'receipt_date required (and delivery_date also missing)');
                    $bad = true;
                }
            }
            if ($amount === null) {
                $this->addError('GR', $r, 'received_amount required');
                $bad = true;
            }
            if ($pctOverride !== null && ($pctOverride < 0 || $pctOverride > 100)) {
                $this->addError('GR', $r, "milestone_percentage must be 0-100, got {$pctOverride}");
                $bad = true;
            }
            if (! in_array($inspect, self::INSPECTION_STATUSES, true)) {
                $this->addError('GR', $r, "invalid inspection_status '{$inspect}'");
                $bad = true;
            }
            if (! in_array($status, self::GR_STATUSES, true)) {
                $this->addError('GR', $r, "invalid status '{$status}'");
                $bad = true;
            }
            if (! isset($this->poMap[$poNumber])) {
                $this->addError('GR', $r, "po_number '{$poNumber}' not found");
                $bad = true;
            }
            if (isset($seen[$grNumber])) {
                $this->addError('GR', $r, "duplicate gr_number '{$grNumber}' in file");
                $bad = true;
            }
            $rcvUserId = null;
            if ($rcvEmail !== '') {
                $rcvUserId = $this->resolveUser($rcvEmail, 'GR', $r);
                if ($rcvUserId === null) {
                    $bad = true;
                }
            }
            if ($bad) {
                continue;
            }

            $existingGr = GoodsReceipt::where('gr_number', $grNumber)->first();
            if ($existingGr) {
                if (! $this->replaceExisting) {
                    $this->addError('GR', $r, "gr_number '{$grNumber}' already exists in DB (--no-replace)");

                    continue;
                }
                DB::table('goods_receipt_items')->where('goods_receipt_id', $existingGr->id)->delete();
                DB::table('goods_receipts')->where('id', $existingGr->id)->delete();
                $this->line("  ~ GR R{$r}: replaced existing gr_number '{$grNumber}' (gr_id={$existingGr->id})");
            }

            $poId = $this->poMap[$poNumber];
            $po = PurchaseOrder::find($poId);
            $poItemId = $this->poItemMap[$poId] ?? null;
            if ($poItemId === null) {
                // PO row existed already and was kept (--no-replace) — reuse its first item.
                $poItemId = DB::table('purchase_order_items')->where('purchase_order_id', $poId)->value('id');
            }
            if ($poItemId === null) {
                $this->addError('GR', $r, "internal: PO item id missing for po_id={$poId}");

                continue;
            }

            $milestone = $milestone ?: 1;
            $percentage = $pctOverride;
            if ($percentage === null && $po && (float) $po->total_amount > 0) {
                $percentage = round($amount / (float) $po->total_amount * 100, 2);
            }
            if ($percentage === null && $totalPhases > 0) {
                $percentage = round(100 / $totalPhases, 2);
            }

            $gr = new GoodsReceipt;
            $gr->company_id = $po->company_id;
            $gr->gr_number = $grNumber;
            $gr->receipt_number = $grNumber;
            $gr->purchase_order_id = $poId;
            $gr->vendor_id = $po->vendor_id;
            $gr->inspection_committee_id = $po->inspection_committee_id;
            $gr->receipt_date = $receiptDate;
            $gr->delivery_milestone = $milestone;
            $gr->milestone_description = $totalPhases ? "งวดที่ {$milestone}/{$totalPhases}" : "งวดที่ {$milestone}";
            $gr->milestone_percentage = $percentage ?? 0;
            $gr->milestone_amount = $amount;
            $gr->inspection_status = $inspect;
            $gr->inspection_notes = $docRef ? "เอกสารอ้างอิง: {$docRef}".($docDate ? " ({$docDate})" : '') : null;
            $gr->delivery_note_number = $docRef ?: null;
            $gr->status = $status;
            $gr->received_by = $rcvUserId;
            $gr->reviewed_by = $rcvUserId;
            $gr->reviewed_at = $receiptDate;
            $gr->is_quality_checked = $inspect === 'passed';
            $gr->quality_checked_by = $rcvUserId;
            $gr->quality_checked_at = $receiptDate;
            $gr->created_by = $rcvUserId;
            $gr->notes = trim("Imported from Excel. delivery_date={$delivDate}, expected_delivery={$expectedDt}. ".($notes ?? ''));
            $gr->saveQuietly();
            $seen[$grNumber] = true;

            $this->grByPo[$poId][] = [
                'gr_id' => $gr->id,
                'milestone' => $milestone,
                'total_phases' => $totalPhases,
                'amount' => (float) $amount,
                'percentage' => $percentage,
                'receipt_date' => $receiptDate,
                'status' => $status,
            ];

            // GR item — must FK to the PO item.
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
                $this->addError('GR', $r, 'failed to insert GR item: '.$e->getMessage());
            }

            $created++;
        }
        $this->info("GR: created={$created}");
    }

    // -------- PaymentMilestones (sheet; entire sheet is optional) --------
    private function importPaymentMilestones($ss): void
    {
        $sh = $ss->getSheetByName('PaymentMilestones');
        if (! $sh) {
            $this->line('PaymentMilestones: sheet missing — will auto-generate from GRs');

            return;
        }
        $highest = $sh->getHighestRowAndColumn();
        $created = 0;

        for ($r = 4; $r <= $highest['row']; $r++) {
            if ($this->rowIsEmpty($sh, $r, $highest['column'])) {
                continue;
            }

            $poNumber = $this->str($sh, 'A', $r);
            $mNumber = (int) $this->str($sh, 'B', $r);
            $title = $this->str($sh, 'C', $r);
            $percent = $this->num($sh, 'D', $r);
            $amount = $this->num($sh, 'E', $r);
            $dueDate = $this->date($sh, 'F', $r);
            $paidDate = $this->date($sh, 'G', $r);
            $paidAmt = $this->num($sh, 'H', $r);
            $payRef = $this->str($sh, 'I', $r);
            $status = $this->str($sh, 'J', $r) ?: 'paid';
            $notes = $this->str($sh, 'K', $r);

            $bad = false;
            if ($poNumber === '') {
                $this->addError('PM', $r, 'po_number required');
                $bad = true;
            }
            if ($mNumber < 1) {
                $this->addError('PM', $r, 'milestone_number must be >= 1');
                $bad = true;
            }
            if ($amount === null) {
                $this->addError('PM', $r, 'amount required');
                $bad = true;
            }
            if (! in_array($status, self::MILESTONE_STATUSES, true)) {
                $this->addError('PM', $r, "invalid status '{$status}'");
                $bad = true;
            }
            if (! isset($this->poMap[$poNumber])) {
                $this->addError('PM', $r, "po_number '{$poNumber}' not found");
                $bad = true;
            }
            if ($bad) {
                continue;
            }

            $poId = $this->poMap[$poNumber];
            $po = PurchaseOrder::find($poId);

            $existing = PaymentMilestone::where('purchase_order_id', $poId)
                ->where('milestone_number', $mNumber)->first();
            if ($existing) {
                // PO replace already wiped old milestones, so a hit here means the
                // PO was kept (--no-replace) — replace just this milestone row.
                if (! $this->replaceExisting) {
                    $this->addError('PM', $r, "milestone {$mNumber} for PO '{$poNumber}' already exists (--no-replace)");

                    continue;
                }
                $existing->delete();
            }

            $this->createMilestone($po, $mNumber, [
                'title' => $title,
                'percentage' => $percent,
                'amount' => $amount,
                'due_date' => $dueDate,
                'paid_date' => $paidDate,
                'paid_amount' => $paidAmt,
                'payment_reference' => $payRef ?: null,
                'status' => $status,
                'notes' => $notes,
            ]);
            $this->poHasExplicitMilestones[$poId] = true;
            $created++;
        }
        $this->info("PaymentMilestones (from sheet): created={$created}");
    }

    /**
     * POs that have GRs but no rows in the PaymentMilestones sheet get one
     * milestone per GR, titled "งวดที่ n/m".
     */
    private function autoGenerateMilestonesFromGrs(): void
    {
        $generated = 0;

        foreach ($this->grByPo as $poId => $grRows) {
            if (isset($this->poHasExplicitMilestones[$poId])) {
                continue;
            }
            if (PaymentMilestone::where('purchase_order_id', $poId)->exists()) {
                continue;
            }

            $po = PurchaseOrder::find($poId);
            if (! $po) {
                continue;
            }

            $total = max(count($grRows), max(array_column($grRows, 'total_phases') ?: [0]));

            foreach ($grRows as $row) {
                $this->createMilestone($po, $row['milestone'], [
                    'title' => $total > 0 ? "งวดที่ {$row['milestone']}/{$total}" : "งวดที่ {$row['milestone']}",
                    'percentage' => $row['percentage'],
                    'amount' => $row['amount'],
                    'due_date' => $row['receipt_date'],
                    'paid_date' => $row['status'] === 'completed' ? $row['receipt_date'] : null,
                    'paid_amount' => $row['status'] === 'completed' ? $row['amount'] : null,
                    'payment_reference' => null,
                    'status' => $row['status'] === 'completed' ? 'paid' : 'pending',
                    'notes' => 'Auto-generated from GR',
                ]);
                $generated++;
            }
        }

        if ($generated > 0) {
            $this->info("PaymentMilestones (auto-generated from GR): created={$generated}");
        }
    }

    /**
     * Link each imported GR to the payment milestone with the same
     * milestone_number on its PO (goods_receipts.payment_milestone_id).
     */
    private function linkGrsToMilestones(): void
    {
        $linked = 0;

        foreach ($this->grByPo as $poId => $grRows) {
            $milestones = PaymentMilestone::where('purchase_order_id', $poId)
                ->pluck('id', 'milestone_number')->all();

            foreach ($grRows as $row) {
                $pmId = $milestones[$row['milestone']] ?? null;
                if ($pmId !== null) {
                    DB::table('goods_receipts')->where('id', $row['gr_id'])
                        ->update(['payment_milestone_id' => $pmId]);
                    $linked++;
                }
            }
        }

        if ($linked > 0) {
            $this->info("GR ↔ PaymentMilestone links: {$linked}");
        }
    }

    /** payment_milestones.due_date and milestone_title are NOT NULL — apply fallbacks here. */
    private function createMilestone(PurchaseOrder $po, int $number, array $data): PaymentMilestone
    {
        $dueDate = $data['due_date']
            ?: $data['paid_date']
            ?: ($po->expected_delivery_date?->format('Y-m-d'))
            ?: ($po->order_date?->format('Y-m-d'))
            ?: now()->format('Y-m-d');

        $pm = new PaymentMilestone;
        $pm->company_id = $po->company_id;
        $pm->purchase_order_id = $po->id;
        $pm->milestone_number = $number;
        $pm->milestone_title = $data['title'] ?: "งวดที่ {$number}";
        $pm->amount = $data['amount'];
        $pm->percentage = $data['percentage'];
        $pm->due_date = $dueDate;
        $pm->paid_date = $data['paid_date'];
        $pm->paid_amount = $data['paid_amount'];
        $pm->payment_reference = $data['payment_reference'];
        $pm->status = $data['status'];
        $pm->payment_notes = trim('Imported from Excel. '.($data['notes'] ?? ''));
        $pm->created_by = $po->created_by;
        $pm->paid_by = $data['status'] === 'paid' ? $po->created_by : null;
        $pm->saveQuietly();

        return $pm;
    }

    // -------- helpers --------

    /**
     * Resolve an email to a user id, auto-creating the user when allowed.
     * Returns null (and records an error) when the user cannot be resolved.
     */
    private function resolveUser(string $email, string $sheet, int $row, string $name = ''): ?int
    {
        if ($email === '') {
            $this->addError($sheet, $row, 'email required');

            return null;
        }
        if (isset($this->userEmailMap[$email])) {
            return $this->userEmailMap[$email];
        }
        if (! $this->autoCreateUsers) {
            $this->addError($sheet, $row, "email '{$email}' has no matching user (--no-auto-users)");

            return null;
        }

        $user = User::create([
            'name' => $name !== '' ? $name : $email,
            'email' => $email,
            'password' => bcrypt(Str::random(24)),
        ]);
        $this->userEmailMap[$email] = $user->id;
        $this->line("  + {$sheet} R{$row}: auto-created user '{$email}' as id={$user->id}");

        return $user->id;
    }

    private function cascadeDeletePr(int $prId, int $row, string $prNumber): void
    {
        $poIds = DB::table('purchase_orders')->where('purchase_requisition_id', $prId)->pluck('id')->all();
        if (! empty($poIds)) {
            $grIds = DB::table('goods_receipts')->whereIn('purchase_order_id', $poIds)->pluck('id')->all();
            if (! empty($grIds)) {
                DB::table('goods_receipt_items')->whereIn('goods_receipt_id', $grIds)->delete();
                DB::table('goods_receipts')->whereIn('id', $grIds)->delete();
            }
            DB::table('payment_milestones')->whereIn('purchase_order_id', $poIds)->delete();
            DB::table('committee_members')->whereIn('purchase_order_id', $poIds)->delete();
            DB::table('po_amendments')->whereIn('purchase_order_id', $poIds)->delete();
            DB::table('purchase_order_items')->whereIn('purchase_order_id', $poIds)->delete();
            DB::table('purchase_order_files')->whereIn('purchase_order_id', $poIds)->delete();
            DB::table('procurement_attachments')
                ->where('attachable_type', 'App\\Models\\PurchaseOrder')
                ->whereIn('attachable_id', $poIds)
                ->delete();
            DB::table('sla_trackings')->whereIn('purchase_order_id', $poIds)->delete();
            DB::table('purchase_orders')->whereIn('id', $poIds)->delete();
        }

        DB::table('purchase_requisition_items')->where('purchase_requisition_id', $prId)->delete();
        DB::table('purchase_requisition_approvals')->where('purchase_requisition_id', $prId)->delete();
        DB::table('procurement_attachments')
            ->where('attachable_type', 'App\\Models\\PurchaseRequisition')
            ->where('attachable_id', $prId)
            ->delete();
        DB::table('sla_trackings')->where('purchase_requisition_id', $prId)->delete();
        DB::table('purchase_requisitions')->where('id', $prId)->delete();

        $poCount = count($poIds);
        $grCount = isset($grIds) ? count($grIds) : 0;
        $this->line("  ~ PR R{$row}: replaced '{$prNumber}' (deleted pr_id={$prId}, po={$poCount}, gr={$grCount})");
    }

    private function cascadeDeletePo(int $poId, int $row, string $poNumber): void
    {
        $grIds = DB::table('goods_receipts')->where('purchase_order_id', $poId)->pluck('id')->all();
        if (! empty($grIds)) {
            DB::table('goods_receipt_items')->whereIn('goods_receipt_id', $grIds)->delete();
            DB::table('goods_receipts')->whereIn('id', $grIds)->delete();
        }
        DB::table('payment_milestones')->where('purchase_order_id', $poId)->delete();
        DB::table('committee_members')->where('purchase_order_id', $poId)->delete();
        DB::table('po_amendments')->where('purchase_order_id', $poId)->delete();
        DB::table('purchase_order_items')->where('purchase_order_id', $poId)->delete();
        DB::table('purchase_order_files')->where('purchase_order_id', $poId)->delete();
        DB::table('procurement_attachments')
            ->where('attachable_type', 'App\\Models\\PurchaseOrder')
            ->where('attachable_id', $poId)
            ->delete();
        DB::table('sla_trackings')->where('purchase_order_id', $poId)->delete();
        DB::table('purchase_orders')->where('id', $poId)->delete();
        $grCount = count($grIds);
        $this->line("  ~ PO R{$row}: replaced '{$poNumber}' (deleted po_id={$poId}, gr={$grCount})");
    }

    private function rowIsEmpty($sh, int $row, string $lastCol): bool
    {
        $cells = $sh->rangeToArray("A{$row}:{$lastCol}{$row}", null, true, false, false);
        foreach ($cells[0] ?? [] as $v) {
            if ($v !== null && trim((string) $v) !== '') {
                return false;
            }
        }

        return true;
    }

    private function str($sh, string $col, int $row): string
    {
        $v = $sh->getCell($col.$row)->getValue();
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }
        if (is_array($v)) {
            return '';
        }

        return trim((string) ($v ?? ''));
    }

    private function num($sh, string $col, int $row): ?float
    {
        $v = $sh->getCell($col.$row)->getValue();
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }
        $s = preg_replace('/[^0-9.\-]/', '', (string) $v);

        return $s === '' ? null : (float) $s;
    }

    private function date($sh, string $col, int $row): ?string
    {
        $v = $sh->getCell($col.$row)->getValue();
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $v)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }
        $s = trim((string) $v);
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $s)) {
            return substr($s, 0, 10);
        }
        $ts = strtotime($s);

        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    private function addError(string $sheet, int $row, string $msg): void
    {
        $this->errors[] = [$sheet, $row, $msg];
    }

    private function printErrors(): void
    {
        $this->newLine();
        $this->error('Errors found:');
        $bySheet = [];
        foreach ($this->errors as [$sheet, $row, $msg]) {
            $bySheet[$sheet][] = "  R{$row}: {$msg}";
        }
        foreach ($bySheet as $sheet => $msgs) {
            $this->line("[$sheet] ".count($msgs));
            foreach ($msgs as $m) {
                $this->line($m);
            }
        }
    }
}
