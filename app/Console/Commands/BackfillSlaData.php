<?php

namespace App\Console\Commands;

use App\Models\PurchaseRequisition;
use App\Models\PurchaseOrder;
use App\Services\SlaService;
use Illuminate\Console\Command;

class BackfillSlaData extends Command
{
    protected $signature = 'sla:backfill';
    protected $description = 'Backfill SLA data for existing approved PRs and POs';

    public function handle(SlaService $slaService): int
    {
        $this->info('Starting SLA backfill process...');

        // ========================
        // Step 1: Update PR timestamps
        // ========================
        $this->info('Updating PR timestamps...');

        $approvedPrs = PurchaseRequisition::whereIn('status', ['approved', 'completed'])
            ->whereNull('submitted_at')
            ->get();

        $prCount = 0;
        foreach ($approvedPrs as $pr) {
            // Use created_at as submitted_at
            $pr->submitted_at = $pr->created_at;

            // Use updated_at as pr_approved_at (or created_at + 1 day as approximation)
            $pr->pr_approved_at = $pr->updated_at ?? $pr->created_at->addDay();

            $pr->saveQuietly(); // Save without triggering observers
            $prCount++;
        }

        $this->info("Updated {$prCount} PRs with timestamps");

        // ========================
        // Step 2: Update PO timestamps
        // ========================
        $this->info('Updating PO timestamps...');

        $approvedPos = PurchaseOrder::whereIn('status', ['approved', 'completed'])
            ->whereNull('po_created_at')
            ->get();

        $poCount = 0;
        foreach ($approvedPos as $po) {
            // Use created_at as po_created_at
            $po->po_created_at = $po->created_at;

            // Use updated_at as po_approved_at
            $po->po_approved_at = $po->updated_at ?? $po->created_at->addDay();

            $po->saveQuietly(); // Save without triggering observers
            $poCount++;
        }

        $this->info("Updated {$poCount} POs with timestamps");

        // ========================
        // Step 3: Calculate SLA for PRs
        // ========================
        $this->info('Calculating SLA for PRs...');

        $prsForSla = PurchaseRequisition::whereIn('status', ['approved', 'completed'])
            ->whereNotNull('submitted_at')
            ->whereNotNull('pr_approved_at')
            ->get();

        $prSlaCount = 0;
        foreach ($prsForSla as $pr) {
            $slaService->trackPrSubmissionToApproval($pr);
            $prSlaCount++;
        }

        $this->info("Created SLA tracking for {$prSlaCount} PRs");

        // ========================
        // Step 4: Calculate SLA for POs
        // ========================
        $this->info('Calculating SLA for POs...');

        $posForSla = PurchaseOrder::whereIn('status', ['approved', 'completed'])
            ->whereNotNull('po_created_at')
            ->whereNotNull('po_approved_at')
            ->get();

        $poSlaCount = 0;
        $fullCycleCount = 0;
        foreach ($posForSla as $po) {
            // Track PO stage
            $slaService->trackPoCreationToApproval($po);
            $poSlaCount++;

            // Track full cycle if PR exists
            $fullCycle = $slaService->trackFullCycle($po);
            if ($fullCycle) {
                $fullCycleCount++;
            }
        }

        $this->info("Created SLA tracking for {$poSlaCount} POs");
        $this->info("Created full cycle SLA tracking for {$fullCycleCount} orders");

        // ========================
        // Summary
        // ========================
        $this->newLine();
        $this->info('=================================');
        $this->info('SLA Backfill Summary:');
        $this->info('=================================');
        $this->info("PRs updated: {$prCount}");
        $this->info("POs updated: {$poCount}");
        $this->info("PR SLA records created: {$prSlaCount}");
        $this->info("PO SLA records created: {$poSlaCount}");
        $this->info("Full cycle SLA records: {$fullCycleCount}");
        $this->info('=================================');

        $this->newLine();
        $this->info('✅ SLA backfill completed successfully!');

        return Command::SUCCESS;
    }
}
