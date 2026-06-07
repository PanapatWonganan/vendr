<?php

namespace App\Console\Commands;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Illuminate\Console\Command;

/**
 * ข้อ 3 + ข้อ 9 + ข้อ 10 (customer revise 02/06/2026)
 *
 * The Excel import wrote a wrong "totalPhases" for some POs, producing bogus
 * GR percentages (e.g. 1.69% = 100/59 on EF41000072). It also mis-numbered a
 * couple of GRs' delivery_milestone.
 *
 * This command, per-company and idempotently:
 *   - ข้อ 3: recomputes each GR's milestone_percentage as an equal split,
 *            100 / (number of GRs on that PO), rounded to 2 dp, when the
 *            current values do not already sum to ~100%.
 *   - ข้อ 9: EF41000072 — renumber GRs by งวด:
 *            EF50000039 -> งวดที่ 4, EF50000033 -> งวดที่ 5.
 *   - ข้อ 10: EF31000562 — EF50000322 -> งวดที่ 2.
 *
 * Use --dry-run first.
 */
class FixImportedGrData extends Command
{
    protected $signature = 'gr:fix-imported-data
        {--company=2 : company_id to scope the percentage recompute}
        {--dry-run : Show changes without writing}';

    protected $description = 'Fix imported GR percentages (ข้อ3) and specific งวด numbers (ข้อ9, ข้อ10)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $companyId = (int) $this->option('company');

        if ($dry) {
            $this->warn('DRY RUN — no data will be written.');
        }

        $this->recomputePercentages($companyId, $dry);
        $this->fixSpecificMilestones($dry);

        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * ข้อ 3: equal-split percentage = 100 / (#GRs on PO).
     * Only rewrites when the existing percentages don't already sum to ~100%.
     */
    private function recomputePercentages(int $companyId, bool $dry): void
    {
        $this->newLine();
        $this->info("ข้อ 3 — recomputing GR percentages (company {$companyId})");

        $poIds = GoodsReceipt::where('company_id', $companyId)
            ->distinct()
            ->pluck('purchase_order_id')
            ->filter();

        $changed = 0;
        $skipped = 0;

        foreach ($poIds as $poId) {
            $grs = GoodsReceipt::where('purchase_order_id', $poId)->get();
            $count = $grs->count();
            if ($count === 0) {
                continue;
            }

            $currentSum = round((float) $grs->sum('milestone_percentage'), 2);

            // Already balanced (within rounding tolerance) -> leave it alone.
            if (abs($currentSum - 100.0) <= 1.0) {
                $skipped++;

                continue;
            }

            // A single GR may legitimately be a partial receipt (e.g. 25%),
            // so we can't infer an equal split from one row. Only normalise a
            // lone GR when its value is clearly a divide-by-phases artifact
            // (an oddly small number) — otherwise leave it for manual review.
            if ($count === 1) {
                $only = (float) $grs->first()->milestone_percentage;
                // 1.69, 2.5, etc. — implausible single-receipt percentages
                if ($only >= 5.0) {
                    $skipped++;

                    continue;
                }
            }

            $pct = round(100 / $count, 2);
            $poNumber = optional(PurchaseOrder::find($poId))->po_number ?? "po#{$poId}";
            $this->line("  PO {$poNumber}: {$count} GRs, sum was {$currentSum}% -> {$pct}% each");

            if (! $dry) {
                foreach ($grs as $gr) {
                    $gr->milestone_percentage = $pct;
                    $gr->saveQuietly();
                }
            }
            $changed++;
        }

        $this->line("  POs rewritten: {$changed} | already balanced (skipped): {$skipped}");
    }

    /**
     * ข้อ 9 / ข้อ 10: specific งวด corrections.
     */
    private function fixSpecificMilestones(bool $dry): void
    {
        $this->newLine();
        $this->info('ข้อ 9 / ข้อ 10 — specific งวด corrections');

        // ข้อ 9: EF41000072
        $this->setMilestone('EF50000039/2026', 4, $dry); // ลูกค้าระบุ -> งวด 4
        $this->setMilestone('EF50000033/2026', 5, $dry); // เลื่อนเป็นงวด 5 (slot ที่เหลือ)

        // ข้อ 10: EF31000562
        $this->setMilestone('EF50000322/2025', 2, $dry); // -> งวด 2
    }

    private function setMilestone(string $grNumber, int $milestone, bool $dry): void
    {
        $gr = GoodsReceipt::where('gr_number', $grNumber)->first();
        if (! $gr) {
            $this->warn("  GR {$grNumber} not found — skipped");

            return;
        }

        $this->line("  GR {$grNumber}: งวด {$gr->delivery_milestone} -> {$milestone}");

        if (! $dry) {
            $gr->delivery_milestone = $milestone;
            // keep milestone_description in sync if it was set
            if ($gr->milestone_description) {
                $gr->milestone_description = preg_replace('/งวดที่\s*\d+/u', "งวดที่ {$milestone}", $gr->milestone_description);
            }
            $gr->saveQuietly();
        }
    }
}
