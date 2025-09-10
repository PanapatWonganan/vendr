<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderPdfService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestPurchaseOrderPdf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'po:test-pdf {po_id? : The PO ID to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test PDF generation for Purchase Orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $poId = $this->argument('po_id');
        
        // If no PO ID provided, get the latest one
        if (!$poId) {
            $purchaseOrder = PurchaseOrder::latest()->first();
            
            if (!$purchaseOrder) {
                $this->error('No Purchase Orders found in the database.');
                return 1;
            }
        } else {
            $purchaseOrder = PurchaseOrder::find($poId);
            
            if (!$purchaseOrder) {
                $this->error("Purchase Order with ID {$poId} not found.");
                return 1;
            }
        }
        
        $this->info("Testing PDF generation for PO: {$purchaseOrder->po_number}");
        $this->info("Work Type: {$purchaseOrder->work_type}");
        
        try {
            $pdfService = new PurchaseOrderPdfService();
            
            // Generate PDF
            $this->info('Generating PDF...');
            $pdfContent = $pdfService->generatePdf($purchaseOrder);
            $filename = $pdfService->generateFilename($purchaseOrder);
            
            // Save to storage for testing
            $path = 'pdf-tests/' . $filename;
            Storage::put($path, $pdfContent);
            
            $this->info('✓ PDF generated successfully!');
            $this->info('  Filename: ' . $filename);
            $this->info('  Size: ' . number_format(strlen($pdfContent) / 1024, 2) . ' KB');
            $this->info('  Saved to: storage/app/' . $path);
            
            // Check which template was used
            $template = $purchaseOrder->work_type === 'hire' ? 'hire' : 'purchase';
            $this->info('  Template: ' . $template);
            
            // Display PO details
            $this->table(
                ['Field', 'Value'],
                [
                    ['PO Number', $purchaseOrder->po_number],
                    ['Title', $purchaseOrder->po_title],
                    ['Work Type', $purchaseOrder->work_type],
                    ['Vendor', $purchaseOrder->vendor_name ?? '-'],
                    ['Total Amount', number_format($purchaseOrder->total_amount, 2) . ' ' . $purchaseOrder->currency],
                    ['Status', $purchaseOrder->status],
                ]
            );
            
            $this->info('');
            $this->info('You can view the PDF at: storage/app/' . $path);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Failed to generate PDF: ' . $e->getMessage());
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}