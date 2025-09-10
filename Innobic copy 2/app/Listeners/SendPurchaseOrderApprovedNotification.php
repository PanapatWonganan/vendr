<?php

namespace App\Listeners;

use App\Events\PurchaseOrderApproved;
use App\Mail\PurchaseOrderApprovedMail;
use App\Services\PurchaseOrderPdfService;
use App\Services\DeliveryNotePdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SendPurchaseOrderApprovedNotification
{
    // Removed ShouldQueue to send emails immediately
    
    private static $processedEvents = [];

    /**
     * Handle the event.
     */
    public function handle(PurchaseOrderApproved $event): void
    {
        try {
            // Create unique event identifier to prevent duplicates (5 minute window)
            $eventKey = "po_approved_" . $event->purchaseOrderId . '_' . $event->approverId;
            
            // Check if this event was already processed recently
            if (Cache::has($eventKey)) {
                Log::warning('🚫 DUPLICATE EMAIL PREVENTED', [
                    'event_key' => $eventKey,
                    'po_id' => $event->purchaseOrderId,
                    'cached_at' => Cache::get($eventKey)
                ]);
                return;
            }
            
            // Mark event as processed (prevent duplicates for 5 minutes)
            Cache::put($eventKey, now()->toDateTimeString(), 300);
            
            // Use the connection info from the event
            $connectionName = $event->connectionName ?? 'mysql';
            
            Log::info('🔥 DUPLICATE EMAIL DEBUG', [
                'po_id' => $event->purchaseOrderId ?? 'NULL',
                'approver_id' => $event->approverId ?? 'NULL',
                'connection' => $connectionName,
                'listener_class' => get_class($this),
                'timestamp' => now()->toDateTimeString(),
                'memory' => memory_get_usage(true),
                'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ]);

            $purchaseOrder = null;
            
            try {
                // Create a new model instance without BaseModel's session dependency
                $purchaseOrder = new \App\Models\PurchaseOrder();
                $purchaseOrder->setConnection($connectionName);
                
                // Get raw data and fill the model
                $poData = \Illuminate\Support\Facades\DB::connection($connectionName)
                    ->table('purchase_orders')
                    ->where('id', $event->purchaseOrderId)
                    ->first();
                
                if ($poData) {
                    foreach ((array)$poData as $key => $value) {
                        $purchaseOrder->{$key} = $value;
                    }
                    $purchaseOrder->exists = true;
                } else {
                    $purchaseOrder = null;
                }
            } catch (\Exception $e) {
                Log::error('PO Approval: Failed to find PO on specified connection', [
                    'po_id' => $event->purchaseOrderId,
                    'connection' => $connectionName,
                    'error' => $e->getMessage()
                ]);
                
                // Fallback to search all connections
                $connections = ['mysql', 'innobic_asia', 'innobic_nutrition', 'innobic_ll'];
                
                foreach ($connections as $connection) {
                    try {
                        $poData = \Illuminate\Support\Facades\DB::connection($connection)
                            ->table('purchase_orders')
                            ->where('id', $event->purchaseOrderId)
                            ->first();
                        
                        if ($poData) {
                            $purchaseOrder = new \App\Models\PurchaseOrder();
                            $purchaseOrder->setConnection($connection);
                            
                            foreach ((array)$poData as $key => $value) {
                                $purchaseOrder->{$key} = $value;
                            }
                            $purchaseOrder->exists = true;
                            $connectionName = $connection;
                            break;
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
            
            $approver = \App\Models\User::find($event->approverId);

            Log::info('PO Approval Models Found', [
                'po_found' => $purchaseOrder ? 'YES' : 'NO',
                'approver_found' => $approver ? 'YES' : 'NO',
                'po_id' => $event->purchaseOrderId,
                'approver_id' => $event->approverId,
                'po_connection' => $purchaseOrder ? $purchaseOrder->getConnectionName() : 'NONE',
            ]);

            if (!$purchaseOrder || !$approver) {
                Log::error('PO Approval: Models not found', [
                    'po_id' => $event->purchaseOrderId,
                    'approver_id' => $event->approverId,
                    'po_found' => $purchaseOrder ? 'YES' : 'NO',
                    'approver_found' => $approver ? 'YES' : 'NO',
                ]);
                return;
            }

            // Additional safety check
            if (!$purchaseOrder instanceof \App\Models\PurchaseOrder) {
                Log::error('PO Approval: Invalid PO object', [
                    'po_type' => gettype($purchaseOrder),
                    'po_class' => is_object($purchaseOrder) ? get_class($purchaseOrder) : 'not_object',
                ]);
                return;
            }

            // Load necessary relationships based on database schema
            // Use the connectionName we already have
            $hasNewFields = \Illuminate\Support\Facades\Schema::connection($connectionName)
                ->hasColumn('purchase_orders', 'vendor_id');
            
            // Load relationships manually since we created the model manually
            try {
                // Load creator (should exist in main database)
                $creator = \App\Models\User::find($purchaseOrder->created_by);
                $purchaseOrder->setRelation('creator', $creator);
                
                if ($hasNewFields) {
                    // Load vendor manually if vendor_id exists
                    if ($purchaseOrder->vendor_id) {
                        $vendorData = \Illuminate\Support\Facades\DB::connection($connectionName)
                            ->table('vendors')
                            ->where('id', $purchaseOrder->vendor_id)
                            ->first();
                        
                        if ($vendorData) {
                            $vendor = new \App\Models\Vendor();
                            $vendor->setConnection($connectionName);
                            foreach ((array)$vendorData as $key => $value) {
                                $vendor->{$key} = $value;
                            }
                            $vendor->exists = true;
                            $purchaseOrder->setRelation('vendor', $vendor);
                        }
                    }
                    
                    // Load inspection committee
                    if ($purchaseOrder->inspection_committee_id) {
                        $committee = \App\Models\User::find($purchaseOrder->inspection_committee_id);
                        $purchaseOrder->setRelation('inspectionCommittee', $committee);
                    }
                }
            } catch (\Exception $e) {
                Log::error('PO Approval: Failed to load relationships', [
                    'po_id' => $event->purchaseOrderId,
                    'error' => $e->getMessage()
                ]);
            }
            
            // Creator is already loaded in the manual relationship loading above

            // For backward compatibility with existing code
            $isCompanyDatabase = $hasNewFields;
            
            // Debug logging
            $vendorEmail = null;
            if ($hasNewFields) {
                $vendorEmail = $purchaseOrder->vendor?->contact_email ?? $purchaseOrder->contact_email;
            } else {
                $vendorEmail = $purchaseOrder->vendor_contact;
            }
            
            Log::info('PO Approval Email Debug', [
                'po_number' => $purchaseOrder->po_number,
                'database_type' => $hasNewFields ? 'new_schema' : 'legacy',
                'connection_name' => $connectionName,
                'vendor_id' => $purchaseOrder->vendor_id ?? 'N/A',
                'vendor_email' => $vendorEmail,
                'committee_id' => $purchaseOrder->inspection_committee_id ?? 'N/A',
                'committee_email' => $purchaseOrder->inspectionCommittee?->email ?? 'N/A',
                'creator_email' => $purchaseOrder->creator?->email,
            ]);

            // Generate PDFs for the Purchase Order
            $pdfContent = null;
            $pdfFilename = null;
            $deliveryNotePdfContent = null;
            $deliveryNotePdfFilename = null;
            
            // Debug log to check if we reach PDF generation
            Log::info('Starting PDF generation for PO', [
                'po_number' => $purchaseOrder->po_number ?? 'NO_NUMBER',
                'po_class' => get_class($purchaseOrder),
                'has_po' => $purchaseOrder !== null,
            ]);
            
            // Generate SOW PDF
            try {
                $pdfService = new PurchaseOrderPdfService();
                $pdfContent = $pdfService->generatePdf($purchaseOrder);
                $pdfFilename = $pdfService->generateFilename($purchaseOrder);
                
                Log::info('SOW PDF generated for Purchase Order', [
                    'po_number' => $purchaseOrder->po_number,
                    'filename' => $pdfFilename,
                    'size' => strlen($pdfContent) . ' bytes',
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to generate SOW PDF for Purchase Order', [
                    'po_number' => $purchaseOrder->po_number,
                    'error' => $e->getMessage(),
                ]);
                // Continue with delivery note generation
            }
            
            // Generate Delivery Note PDF
            try {
                $deliveryNoteService = new DeliveryNotePdfService();
                $deliveryNotePdfContent = $deliveryNoteService->generatePdf($purchaseOrder);
                $deliveryNotePdfFilename = $deliveryNoteService->generateFilename($purchaseOrder);
                
                Log::info('Delivery Note PDF generated for Purchase Order', [
                    'po_number' => $purchaseOrder->po_number,
                    'filename' => $deliveryNotePdfFilename,
                    'size' => strlen($deliveryNotePdfContent) . ' bytes',
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to generate Delivery Note PDF for Purchase Order', [
                    'po_number' => $purchaseOrder->po_number,
                    'error' => $e->getMessage(),
                ]);
                // Continue sending email without delivery note attachment
            }

            if ($purchaseOrder->creator && $purchaseOrder->creator->email) {
                // Check if user wants to receive approval emails
                if (($purchaseOrder->creator->email_po_approved ?? true) && ($purchaseOrder->creator->email_po_notifications ?? true)) {
                    // Send email to the PO creator (requester) with PDF attachments
                    Mail::to($purchaseOrder->creator->email)
                        ->send(new PurchaseOrderApprovedMail(
                            $purchaseOrder, 
                            $approver, 
                            $purchaseOrder->creator, 
                            $pdfContent, 
                            $pdfFilename,
                            $deliveryNotePdfContent,
                            $deliveryNotePdfFilename
                        ));

                    Log::info('Purchase Order approved email sent with PDFs', [
                        'po_number' => $purchaseOrder->po_number,
                        'recipient' => $purchaseOrder->creator->email,
                        'approver' => $approver->name,
                        'sow_pdf_attached' => $pdfContent !== null,
                        'delivery_note_pdf_attached' => $deliveryNotePdfContent !== null,
                    ]);
                } else {
                    Log::info('Purchase Order approved email skipped - user preferences disabled', [
                        'po_number' => $purchaseOrder->po_number,
                        'recipient' => $purchaseOrder->creator->email,
                        'email_po_approved' => $purchaseOrder->creator->email_po_approved ?? 'NULL',
                        'email_po_notifications' => $purchaseOrder->creator->email_po_notifications ?? 'NULL',
                    ]);
                }
            } else {
                Log::warning('Cannot send approval email - creator not found or no email', [
                    'po_number' => $purchaseOrder->po_number,
                    'creator_id' => $purchaseOrder->created_by,
                ]);
            }

            // Send notification to vendor (handle different schemas)
            $vendorEmail = '';
            $vendorName = '';
            
            if ($isCompanyDatabase) {
                // Company database: use vendor_contact field only (no supplier relationships)
                if ($purchaseOrder->vendor_contact) {
                    $vendorEmail = $purchaseOrder->vendor_contact;
                    $vendorName = $purchaseOrder->vendor_name;
                }
            } else {
                // Main database: use vendor relationship or contact_email field
                if ($purchaseOrder->vendor && $purchaseOrder->vendor->contact_email) {
                    $vendorEmail = $purchaseOrder->vendor->contact_email;
                    $vendorName = $purchaseOrder->vendor->company_name;
                } elseif ($purchaseOrder->contact_email) {
                    $vendorEmail = $purchaseOrder->contact_email;
                    $vendorName = $purchaseOrder->vendor_name;
                }
            }

            if ($vendorEmail) {
                try {
                    Mail::to($vendorEmail)
                        ->send(new PurchaseOrderApprovedMail(
                            $purchaseOrder, 
                            $approver, 
                            null, 
                            $pdfContent, 
                            $pdfFilename,
                            $deliveryNotePdfContent,
                            $deliveryNotePdfFilename
                        ));

                    Log::info('Purchase Order approved email sent to vendor with PDFs', [
                        'po_number' => $purchaseOrder->po_number,
                        'vendor_email' => $vendorEmail,
                        'vendor_name' => $vendorName,
                        'database_type' => $isCompanyDatabase ? 'company' : 'main',
                        'sow_pdf_attached' => $pdfContent !== null,
                        'delivery_note_pdf_attached' => $deliveryNotePdfContent !== null,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send PO approved email to vendor', [
                        'po_number' => $purchaseOrder->po_number,
                        'vendor_email' => $vendorEmail,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                Log::warning('No vendor email found for PO approval notification', [
                    'po_number' => $purchaseOrder->po_number,
                    'database_type' => $isCompanyDatabase ? 'company' : 'main',
                ]);
            }

            // Send notification to inspection committee (handle different schemas)
            $inspectionCommittee = null;
            
            if ($isCompanyDatabase) {
                // Company database: use inspection_committee_id directly
                if ($purchaseOrder->inspection_committee_id) {
                    $inspectionCommittee = $purchaseOrder->inspectionCommittee;
                }
            } else {
                // Main database: use inspection committee from PR
                if ($purchaseOrder->purchaseRequisition && $purchaseOrder->purchaseRequisition->inspectionCommittee) {
                    $inspectionCommittee = $purchaseOrder->purchaseRequisition->inspectionCommittee;
                }
            }
            
            if ($inspectionCommittee && $inspectionCommittee->email) {
                try {
                    Mail::to($inspectionCommittee->email)
                        ->send(new PurchaseOrderApprovedMail(
                            $purchaseOrder, 
                            $approver, 
                            $inspectionCommittee, 
                            $pdfContent, 
                            $pdfFilename,
                            $deliveryNotePdfContent,
                            $deliveryNotePdfFilename
                        ));

                    Log::info('Purchase Order approved email sent to inspection committee with PDFs', [
                        'po_number' => $purchaseOrder->po_number,
                        'pr_number' => $isCompanyDatabase ? 'N/A' : ($purchaseOrder->purchaseRequisition->pr_number ?? 'N/A'),
                        'committee_email' => $inspectionCommittee->email,
                        'committee_name' => $inspectionCommittee->name,
                        'database_type' => $isCompanyDatabase ? 'company' : 'main',
                        'sow_pdf_attached' => $pdfContent !== null,
                        'delivery_note_pdf_attached' => $deliveryNotePdfContent !== null,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send PO approved email to inspection committee', [
                        'po_number' => $purchaseOrder->po_number,
                        'committee_email' => $inspectionCommittee->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                Log::warning('No inspection committee email found for PO approval notification', [
                    'po_number' => $purchaseOrder->po_number,
                    'database_type' => $isCompanyDatabase ? 'company' : 'main',
                ]);
            }

            // Warning if no external emails were sent
            $vendorEmailSent = !empty($vendorEmail);
            $committeeEmailSent = $inspectionCommittee && $inspectionCommittee->email;
            
            if (!$vendorEmailSent && !$committeeEmailSent) {
                Log::warning('PO Approved: No vendor or committee emails available', [
                    'po_number' => $purchaseOrder->po_number,
                    'database_type' => $isCompanyDatabase ? 'company' : 'main',
                    'vendor_id' => $isCompanyDatabase ? 'N/A' : $purchaseOrder->vendor_id,
                    'committee_id' => $isCompanyDatabase ? $purchaseOrder->inspection_committee_id : $purchaseOrder->purchaseRequisition?->inspection_committee_id,
                    'message' => 'Only internal notification sent to PO creator',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send Purchase Order approved email', [
                'po_number' => $event->purchaseOrder->po_number,
                'error' => $e->getMessage(),
            ]);

            // Don't re-throw the exception to prevent job failure
            // The main approval process should continue even if email fails
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(PurchaseOrderApproved $event, \Throwable $exception): void
    {
        Log::error('Purchase Order approved email job failed', [
            'po_number' => $event->purchaseOrder->po_number,
            'error' => $exception->getMessage(),
        ]);
    }
} 