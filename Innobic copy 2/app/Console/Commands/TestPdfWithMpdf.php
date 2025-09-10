<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderPdfService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TestPdfWithMpdf extends Command
{
    protected $signature = 'test:pdf-mpdf {po_number}';
    protected $description = 'Test PDF generation with mPDF for Thai text support';

    public function handle()
    {
        $poNumber = $this->argument('po_number');
        
        $purchaseOrder = PurchaseOrder::where('po_number', $poNumber)->first();
        
        if (!$purchaseOrder) {
            $this->error("Purchase Order {$poNumber} not found!");
            return 1;
        }
        
        $this->info("Testing PDF generation with mPDF for PO: {$poNumber}");
        
        try {
            $pdfService = new PurchaseOrderPdfService();
            
            $this->info("Generating PDF...");
            $pdfContent = $pdfService->generatePdf($purchaseOrder);
            $filename = $pdfService->generateFilename($purchaseOrder);
            
            // Save to test directory
            $testPath = "pdf-tests/{$filename}";
            Storage::put($testPath, $pdfContent);
            
            $fullPath = storage_path("app/{$testPath}");
            
            $this->info("PDF generated successfully!");
            $this->info("File saved to: {$fullPath}");
            $this->info("File size: " . number_format(strlen($pdfContent)) . " bytes");
            
            // Log Thai text test
            Log::info('mPDF Thai text test completed', [
                'po_number' => $poNumber,
                'work_type' => $purchaseOrder->work_type,
                'filename' => $filename,
                'file_size' => strlen($pdfContent),
                'file_path' => $fullPath,
            ]);
            
            $this->info("Check the PDF file to see if Thai text is displaying correctly.");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Failed to generate PDF: " . $e->getMessage());
            Log::error('PDF generation test failed', [
                'po_number' => $poNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return 1;
        }
    }
}