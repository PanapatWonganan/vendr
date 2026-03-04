<?php

namespace App\Services;

use App\Events\PurchaseOrderAmended;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PaymentMilestone;
use App\Models\PoAmendment;
use App\Models\PurchaseOrder;
use App\Models\ValueAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AmendmentService
{
    /**
     * Create a VA revision from an approved PO that needs amendment.
     * This is step 1 of the amendment flow: PO → VA Revision → VA Approval → PO Amendment
     */
    public function createVaRevisionFromPo(
        PurchaseOrder $po,
        string $amendmentType,
        string $amendmentReason,
        int $createdBy
    ): ValueAnalysis {
        // Find the current VA for this PO's PR
        $currentVa = $this->getCurrentVaForPo($po);

        if (!$currentVa) {
            throw new \RuntimeException('ไม่พบ VA สำหรับ PO นี้ ไม่สามารถสร้าง revision ได้');
        }

        return $currentVa->createRevision($amendmentType, $amendmentReason, $createdBy);
    }

    /**
     * Apply an approved VA revision to amend the PO.
     * This is step 2: After VA revision is approved, update the PO.
     */
    public function applyVaRevisionToPo(ValueAnalysis $vaRevision, int $approvedBy): PoAmendment
    {
        if (!$vaRevision->isRevision()) {
            throw new \RuntimeException('VA นี้ไม่ใช่ revision ไม่สามารถใช้ amend PO ได้');
        }

        if ($vaRevision->status !== 'approved') {
            throw new \RuntimeException('VA revision ยังไม่ได้รับการอนุมัติ');
        }

        $pr = $vaRevision->purchaseRequisition;
        $po = PurchaseOrder::where('purchase_requisition_id', $pr->id)
            ->whereNotIn('status', ['cancelled'])
            ->latest()
            ->first();

        if (!$po) {
            throw new \RuntimeException('ไม่พบ PO ที่เชื่อมกับ PR นี้');
        }

        return DB::transaction(function () use ($po, $vaRevision, $approvedBy) {
            $previousTotal = $po->total_amount;

            // Build changes snapshot before modifying
            $changesSnapshot = $this->buildChangesSnapshot($po, $vaRevision);

            // Update PO items from VA revision items
            $this->updatePoItemsFromVa($po, $vaRevision);

            // Recalculate PO total
            $newTotal = $po->items()->sum('line_total');

            // Increment amendment number
            $amendmentNumber = $po->amendment_number + 1;

            // Update PO
            $po->update([
                'value_analysis_id' => $vaRevision->id,
                'amendment_number' => $amendmentNumber,
                'total_amount' => $newTotal,
                'last_amended_at' => now(),
                'status' => 'pending_approval',
                'updated_by' => $approvedBy,
            ]);

            // Create amendment record
            $amendment = PoAmendment::create([
                'company_id' => $po->company_id,
                'purchase_order_id' => $po->id,
                'value_analysis_id' => $vaRevision->id,
                'amendment_number' => $amendmentNumber,
                'amendment_type' => $vaRevision->amendment_type,
                'amendment_reason' => $vaRevision->amendment_reason,
                'previous_total_amount' => $previousTotal,
                'new_total_amount' => $newTotal,
                'changes_snapshot' => $changesSnapshot,
                'status' => PoAmendment::STATUS_PENDING,
                'requested_by' => $vaRevision->created_by,
                'requested_at' => now(),
            ]);

            return $amendment;
        });
    }

    /**
     * After PO amendment is approved, update GR prices and payment milestones.
     */
    public function finalizeAmendment(PoAmendment $amendment, int $approverId): void
    {
        DB::transaction(function () use ($amendment, $approverId) {
            $po = $amendment->purchaseOrder;

            // Mark amendment as approved
            $amendment->update([
                'status' => PoAmendment::STATUS_APPROVED,
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            // Retroactively update GR item prices
            $this->updateGrPricesRetroactively($po);

            // Recalculate payment milestones
            $this->recalculatePaymentMilestones($po);

            // Fire event
            $approver = \App\Models\User::find($approverId);
            if ($approver) {
                event(new PurchaseOrderAmended($po, $approver, $amendment));
            }

            Log::info("PO Amendment finalized", [
                'po_id' => $po->id,
                'po_number' => $po->po_number,
                'amendment_number' => $amendment->amendment_number,
                'previous_total' => $amendment->previous_total_amount,
                'new_total' => $amendment->new_total_amount,
            ]);
        });
    }

    /**
     * Update PO items to match VA revision items.
     */
    protected function updatePoItemsFromVa(PurchaseOrder $po, ValueAnalysis $vaRevision): void
    {
        // Delete existing PO items
        $po->items()->delete();

        // Create new items from VA revision
        foreach ($vaRevision->items as $vaItem) {
            $po->items()->create([
                'purchase_requisition_item_id' => $vaItem->purchase_requisition_item_id,
                'line_number' => $vaItem->line_number,
                'item_code' => $vaItem->item_code,
                'description' => $vaItem->description,
                'quantity' => $vaItem->quantity,
                'unit_of_measure' => $vaItem->unit_of_measure,
                'unit_price' => $vaItem->agreed_unit_price,
                'line_total' => $vaItem->agreed_amount,
                'status' => 'ordered',
            ]);
        }
    }

    /**
     * Update GR item prices retroactively to match new PO prices.
     */
    protected function updateGrPricesRetroactively(PurchaseOrder $po): void
    {
        $poItems = $po->items->keyBy('description');

        $goodsReceipts = $po->goodsReceipts;

        foreach ($goodsReceipts as $gr) {
            foreach ($gr->items as $grItem) {
                $matchingPoItem = $poItems->get($grItem->description);
                if ($matchingPoItem && (float) $grItem->unit_price !== (float) $matchingPoItem->unit_price) {
                    $grItem->update([
                        'unit_price' => $matchingPoItem->unit_price,
                        'total_price' => $grItem->quantity * $matchingPoItem->unit_price,
                    ]);
                }
            }
        }
    }

    /**
     * Recalculate payment milestones based on new PO total.
     */
    protected function recalculatePaymentMilestones(PurchaseOrder $po): void
    {
        $newTotal = $po->total_amount;

        foreach ($po->paymentMilestones as $milestone) {
            // Only recalculate unpaid milestones
            if ($milestone->status === PaymentMilestone::STATUS_PAID) {
                continue;
            }

            if ($milestone->percentage && $milestone->percentage > 0) {
                $milestone->update([
                    'amount' => ($milestone->percentage / 100) * $newTotal,
                ]);
            }
        }
    }

    /**
     * Build a snapshot of changes for audit trail.
     */
    protected function buildChangesSnapshot(PurchaseOrder $po, ValueAnalysis $vaRevision): array
    {
        $snapshot = [
            'total_amount' => [
                'old' => (float) $po->total_amount,
                'new' => (float) $vaRevision->agreed_amount,
            ],
            'items' => [
                'old' => $po->items->map(fn ($item) => [
                    'line_number' => $item->line_number,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ])->toArray(),
                'new' => $vaRevision->items->map(fn ($item) => [
                    'line_number' => $item->line_number,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->agreed_unit_price,
                    'line_total' => (float) $item->agreed_amount,
                ])->toArray(),
            ],
        ];

        return $snapshot;
    }

    /**
     * Find the current (latest approved) VA for a PO.
     */
    protected function getCurrentVaForPo(PurchaseOrder $po): ?ValueAnalysis
    {
        // First check direct link
        if ($po->value_analysis_id) {
            return ValueAnalysis::find($po->value_analysis_id);
        }

        // Fall back to PR → VA link
        $pr = $po->purchaseRequisition;
        if (!$pr) {
            return null;
        }

        // Get the latest approved VA for this PR (could be original or a revision)
        return ValueAnalysis::where('purchase_requisition_id', $pr->id)
            ->where('status', 'approved')
            ->latest('revision_number')
            ->first();
    }
}
