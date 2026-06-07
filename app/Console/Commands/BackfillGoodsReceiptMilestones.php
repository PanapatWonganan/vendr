<?php

namespace App\Console\Commands;

use App\Models\GoodsReceipt;
use App\Models\PaymentMilestone;
use App\Models\PurchaseOrder;
use Illuminate\Console\Command;

/**
 * ข้อ 8 + ข้อ 12 (customer revise 02/06/2026)
 *
 * Imported data created GoodsReceipts using only the legacy `delivery_milestone`
 * integer, without any PaymentMilestone records. As a result:
 *   - the GR create form's milestone dropdown is always empty (ข้อ 8)
 *   - POs that were never received have no GR row at all (ข้อ 12)
 *
 * This command, idempotently and per-company:
 *   1. Derives PaymentMilestone rows from existing GRs' delivery_milestone /
 *      milestone_percentage and links each GR to its milestone.
 *   2. Creates a draft placeholder GR for every PO that has no GR yet, so the
 *      receiving screen lists work that still needs inspecting.
 *
 * Safe to re-run. Use --dry-run first.
 */
class BackfillGoodsReceiptMilestones extends Command
{
    protected $signature = 'gr:backfill-milestones
        {--company= : Limit to a single company_id}
        {--dry-run : Show what would change without writing}
        {--placeholders : Also create draft placeholder GRs for POs with no GR (ข้อ 12)}';

    protected $description = 'Backfill PaymentMilestones from imported GRs and (optionally) create placeholder GRs for un-received POs';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $companyId = $this->option('company') ? (int) $this->option('company') : null;

        if ($dry) {
            $this->warn('DRY RUN — no data will be written.');
        }

        $poQuery = PurchaseOrder::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $milestonesCreated = 0;
        $grsLinked = 0;
        $placeholdersCreated = 0;

        $pos = $poQuery->get();
        $this->info("Scanning {$pos->count()} purchase orders".($companyId ? " (company {$companyId})" : '').'...');

        foreach ($pos as $po) {
            $grs = GoodsReceipt::where('purchase_order_id', $po->id)
                ->orderBy('delivery_milestone')
                ->orderBy('id')
                ->get();

            // ---- 1. Derive milestones from existing GRs (only if PO has none) ----
            $hasMilestones = PaymentMilestone::where('purchase_order_id', $po->id)->exists();

            if ($grs->isNotEmpty() && ! $hasMilestones) {
                // group GRs by งวด number, in receipt order
                $byNumber = [];
                $auto = 0;
                foreach ($grs as $gr) {
                    $num = (int) ($gr->delivery_milestone ?: (++$auto));
                    $byNumber[$num] ??= [];
                    $byNumber[$num][] = $gr;
                }
                ksort($byNumber);

                foreach ($byNumber as $num => $grsForNumber) {
                    /** @var GoodsReceipt $first */
                    $first = $grsForNumber[0];
                    $pct = $first->milestone_percentage;
                    $amount = $pct !== null && $po->total_amount !== null
                        ? round($po->total_amount * $pct / 100, 2)
                        : 0;

                    $this->line("  PO {$po->po_number}: milestone #{$num} ({$pct}% = {$amount})");

                    if (! $dry) {
                        $milestone = PaymentMilestone::create([
                            'company_id' => $po->company_id,
                            'purchase_order_id' => $po->id,
                            'milestone_number' => $num,
                            'milestone_title' => "งวดที่ {$num}",
                            'percentage' => $pct,
                            'amount' => $amount,
                            'due_date' => $first->receipt_date ?? now(),
                            'status' => 'paid',
                            'created_by' => $first->created_by ?? $first->received_by ?? 1,
                        ]);

                        // Link the first GR of this number to the milestone
                        // (one GR <-> one milestone). Extra GRs with the same
                        // number stay as-is to avoid violating the 1:1 relation.
                        $first->payment_milestone_id = $milestone->id;
                        $first->saveQuietly();
                        $grsLinked++;
                    }
                    $milestonesCreated++;
                }
            }

            // ---- 2. Placeholder GR for un-received POs (ข้อ 12) ----
            if ($this->option('placeholders') && $grs->isEmpty()) {
                // Skip cancelled POs — nothing to receive.
                if ($po->status === PurchaseOrder::STATUS_CANCELLED) {
                    continue;
                }

                $this->line("  PO {$po->po_number}: + placeholder draft GR (no receipts yet)");

                if (! $dry) {
                    $gr = new GoodsReceipt;
                    $gr->company_id = $po->company_id;
                    $gr->purchase_order_id = $po->id;
                    $gr->vendor_id = $po->vendor_id;
                    $gr->inspection_committee_id = $po->inspection_committee_id;
                    $gr->receipt_date = now();
                    $gr->delivery_milestone = 1;
                    $gr->milestone_percentage = 0;
                    $gr->inspection_status = 'pending';
                    $gr->status = 'draft';
                    $gr->received_by = $po->created_by ?? 1;
                    $gr->created_by = $po->created_by ?? 1;
                    $gr->notes = 'สร้างอัตโนมัติเพื่อรอการตรวจรับ (ข้อ 12)';
                    $gr->save(); // triggers gr_number generation
                }
                $placeholdersCreated++;
            }
        }

        $this->newLine();
        $this->info('Summary'.($dry ? ' (dry run)' : '').':');
        $this->table(['Action', 'Count'], [
            ['PaymentMilestones created', $milestonesCreated],
            ['GRs linked to milestones', $grsLinked],
            ['Placeholder draft GRs created', $placeholdersCreated],
        ]);

        return self::SUCCESS;
    }
}
