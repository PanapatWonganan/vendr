<?php

namespace App\Listeners;

use App\Events\PurchaseOrderApproved;
use App\Mail\PurchaseOrderApprovedMail;
use App\Models\Company;
use App\Services\PurchaseOrderPdfService;
use App\Services\DeliveryNotePdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SendPurchaseOrderApprovedNotification implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Handle the event.
     */
    public function handle(PurchaseOrderApproved $event): void
    {
        try {
            // Prevent duplicate emails within 5-minute window
            $eventKey = "po_approved_{$event->purchaseOrderId}_{$event->approverId}";

            if (Cache::has($eventKey)) {
                Log::warning('PO Approval: Duplicate event prevented', [
                    'po_id' => $event->purchaseOrderId,
                ]);
                return;
            }

            Cache::put($eventKey, now()->toDateTimeString(), 300);

            $connectionName = $event->connectionName ?? 'mysql';

            // Load Purchase Order
            $purchaseOrder = $this->loadPurchaseOrder($event->purchaseOrderId, $connectionName);

            if (!$purchaseOrder) {
                Log::error('PO Approval: Purchase Order not found', [
                    'po_id' => $event->purchaseOrderId,
                    'connection' => $connectionName,
                ]);
                return;
            }

            // Update connection name if fallback was used
            $connectionName = $purchaseOrder->getConnectionName();

            $approver = \App\Models\User::find($event->approverId);

            if (!$approver) {
                Log::error('PO Approval: Approver not found', ['approver_id' => $event->approverId]);
                return;
            }

            // Load relationships
            $this->loadRelationships($purchaseOrder, $connectionName);

            // Load company
            $company = Company::find($event->companyId);

            // Generate PDFs
            [$pdfContent, $pdfFilename] = $this->generateSowPdf($purchaseOrder);
            [$deliveryNotePdfContent, $deliveryNotePdfFilename] = $this->generateDeliveryNotePdf($purchaseOrder);

            // Resolve inspection committee (from PR first, fallback to PO)
            $inspectionCommittee = $this->resolveInspectionCommittee($purchaseOrder);

            // Send to inspection committee
            if ($inspectionCommittee?->email) {
                $this->sendEmail(
                    $inspectionCommittee->email,
                    $purchaseOrder, $approver, $inspectionCommittee,
                    $pdfContent, $pdfFilename,
                    $deliveryNotePdfContent, $deliveryNotePdfFilename,
                    $company,
                    'inspection_committee'
                );
            }

            // Send to vendor
            [$vendorEmail, $vendorName] = $this->resolveVendorEmail($purchaseOrder);

            if ($vendorEmail) {
                $this->sendEmail(
                    $vendorEmail,
                    $purchaseOrder, $approver, null,
                    $pdfContent, $pdfFilename,
                    $deliveryNotePdfContent, $deliveryNotePdfFilename,
                    $company,
                    'vendor'
                );
            }

            if (!$vendorEmail && !$inspectionCommittee?->email) {
                Log::warning('PO Approval: No external emails sent', [
                    'po_number' => $purchaseOrder->po_number,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('PO Approval: Failed to send emails', [
                'po_id' => $event->purchaseOrderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Load PO from specified connection, fallback to all active company connections.
     */
    private function loadPurchaseOrder(int $poId, string $connectionName): ?\App\Models\PurchaseOrder
    {
        // Try specified connection first
        $po = $this->findPoOnConnection($poId, $connectionName);
        if ($po) {
            return $po;
        }

        // Fallback: search all active company connections from DB
        $connections = Company::where('is_active', true)
            ->pluck('database_connection')
            ->prepend('mysql')
            ->unique()
            ->toArray();

        foreach ($connections as $connection) {
            if ($connection === $connectionName) {
                continue; // Already tried
            }
            $po = $this->findPoOnConnection($poId, $connection);
            if ($po) {
                return $po;
            }
        }

        return null;
    }

    /**
     * Find PO on a specific connection.
     */
    private function findPoOnConnection(int $poId, string $connection): ?\App\Models\PurchaseOrder
    {
        try {
            $poData = DB::connection($connection)
                ->table('purchase_orders')
                ->where('id', $poId)
                ->first();

            if (!$poData) {
                return null;
            }

            $po = new \App\Models\PurchaseOrder();
            $po->setConnection($connection);
            foreach ((array) $poData as $key => $value) {
                $po->{$key} = $value;
            }
            $po->exists = true;

            return $po;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Load relationships manually (since model was built from raw query).
     */
    private function loadRelationships(\App\Models\PurchaseOrder $po, string $connectionName): void
    {
        try {
            $creator = \App\Models\User::find($po->created_by);
            if ($creator) {
                $po->setRelation('creator', $creator);
            }

            $hasVendorId = Schema::connection($connectionName)->hasColumn('purchase_orders', 'vendor_id');

            if ($hasVendorId && $po->vendor_id) {
                $vendorData = DB::connection($connectionName)
                    ->table('vendors')
                    ->where('id', $po->vendor_id)
                    ->first();

                if ($vendorData) {
                    $vendor = new \App\Models\Vendor();
                    $vendor->setConnection($connectionName);
                    foreach ((array) $vendorData as $key => $value) {
                        $vendor->{$key} = $value;
                    }
                    $vendor->exists = true;
                    $po->setRelation('vendor', $vendor);
                }
            }

            if ($hasVendorId && $po->inspection_committee_id) {
                $committee = \App\Models\User::find($po->inspection_committee_id);
                if ($committee) {
                    $po->setRelation('inspectionCommittee', $committee);
                }
            }
        } catch (\Exception $e) {
            Log::error('PO Approval: Failed to load relationships', [
                'po_id' => $po->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve inspection committee from PR or PO.
     */
    private function resolveInspectionCommittee(\App\Models\PurchaseOrder $po): ?\App\Models\User
    {
        // Try PR first
        if ($po->purchaseRequisition?->inspection_committee_id) {
            return \App\Models\User::find($po->purchaseRequisition->inspection_committee_id);
        }

        // Fallback to PO
        return $po->inspectionCommittee;
    }

    /**
     * Resolve vendor email from various sources.
     */
    private function resolveVendorEmail(\App\Models\PurchaseOrder $po): array
    {
        if ($po->vendor?->contact_email) {
            return [$po->vendor->contact_email, $po->vendor->company_name];
        }

        if (!empty($po->vendor_contact)) {
            return [$po->vendor_contact, $po->vendor_name ?? 'Vendor'];
        }

        if (!empty($po->contact_email)) {
            return [$po->contact_email, $po->vendor_name ?? 'Vendor'];
        }

        return [null, null];
    }

    /**
     * Generate SOW PDF.
     */
    private function generateSowPdf(\App\Models\PurchaseOrder $po): array
    {
        try {
            $service = new PurchaseOrderPdfService();
            return [$service->generatePdf($po), $service->generateFilename($po)];
        } catch (\Exception $e) {
            Log::error('PO Approval: SOW PDF generation failed', [
                'po_number' => $po->po_number,
                'error' => $e->getMessage(),
            ]);
            return [null, null];
        }
    }

    /**
     * Generate Delivery Note PDF.
     */
    private function generateDeliveryNotePdf(\App\Models\PurchaseOrder $po): array
    {
        try {
            $service = new DeliveryNotePdfService();
            return [$service->generatePdf($po), $service->generateFilename($po)];
        } catch (\Exception $e) {
            Log::error('PO Approval: Delivery Note PDF generation failed', [
                'po_number' => $po->po_number,
                'error' => $e->getMessage(),
            ]);
            return [null, null];
        }
    }

    /**
     * Send email to a recipient.
     */
    private function sendEmail(
        string $toEmail,
        \App\Models\PurchaseOrder $po,
        \App\Models\User $approver,
        ?\App\Models\User $recipient,
        ?string $pdfContent,
        ?string $pdfFilename,
        ?string $deliveryNotePdfContent,
        ?string $deliveryNotePdfFilename,
        ?Company $company,
        string $recipientType
    ): void {
        try {
            Mail::to($toEmail)->send(new PurchaseOrderApprovedMail(
                $po,
                $approver,
                $recipient,
                $pdfContent,
                $pdfFilename,
                $deliveryNotePdfContent,
                $deliveryNotePdfFilename,
                $company
            ));

            Log::info("PO Approval: Email sent to {$recipientType}", [
                'po_number' => $po->po_number,
                'recipient' => $toEmail,
            ]);
        } catch (\Exception $e) {
            Log::error("PO Approval: Failed to send email to {$recipientType}", [
                'po_number' => $po->po_number,
                'recipient' => $toEmail,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(PurchaseOrderApproved $event, \Throwable $exception): void
    {
        Log::error('PO approved notification job failed permanently', [
            'po_id' => $event->purchaseOrderId,
            'error' => $exception->getMessage(),
        ]);
    }
}
