<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use Illuminate\Support\Facades\Log;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class DeliveryNotePdfService
{
    /**
     * Generate PDF for Delivery Note
     * 
     * @param PurchaseOrder $purchaseOrder
     * @return string PDF content as binary string
     */
    public function generatePdf(PurchaseOrder $purchaseOrder): string
    {
        try {
            // Prepare data for the view
            $data = $this->prepareData($purchaseOrder);
            
            // Get mPDF default configurations
            $defaultConfig = (new ConfigVariables())->getDefaults();
            $fontDirs = $defaultConfig['fontDir'];
            
            $defaultFontConfig = (new FontVariables())->getDefaults();
            $fontData = $defaultFontConfig['fontdata'];
            
            // Generate PDF using mPDF with Unicode font support for Thai
            $config = [
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'tempDir' => storage_path('app/temp'),
                'default_font' => 'freeserif', // freeserif รองรับ Unicode/Thai
                'autoLangToFont' => true,
                'autoScriptToLang' => true
            ];
            
            $pdf = PDF::loadView('pdf.delivery-note', $data, [], $config);
            
            Log::info('Delivery Note PDF generated successfully', [
                'po_number' => $purchaseOrder->po_number,
                'template' => 'pdf.delivery-note',
            ]);
            
            // Return PDF as binary string
            return $pdf->output();
            
        } catch (\Exception $e) {
            Log::error('Failed to generate Delivery Note PDF', [
                'po_number' => $purchaseOrder->po_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Prepare data for PDF view
     * 
     * @param PurchaseOrder $purchaseOrder
     * @return array
     */
    private function prepareData(PurchaseOrder $purchaseOrder): array
    {
        // Load necessary relationships
        $purchaseOrder->load([
            'creator',
            'vendor',
            'inspectionCommittee',
            'purchaseRequisition',
            'items',
            'approver',
        ]);
        
        // Determine vendor information based on database schema
        $vendorName = '';
        $vendorAddress = '';
        
        if ($purchaseOrder->vendor) {
            $vendorName = $purchaseOrder->vendor->company_name ?? $purchaseOrder->vendor->name;
            $vendorAddress = $purchaseOrder->vendor->address ?? '';
        } else {
            $vendorName = $purchaseOrder->vendor_name ?? 'ผู้รับเหมา/ผู้ขาย';
            $vendorAddress = $purchaseOrder->delivery_address ?? '';
        }
        
        return [
            'purchaseOrder' => $purchaseOrder,
            'vendorName' => $vendorName,
            'vendorAddress' => $vendorAddress,
            'company' => [
                'name' => 'บริษัท อินโนบิค นูทริชั่น จำกัด',
                'address' => 'เลขที่ 425/1 อาคาร เอนโก้เทอร์มินอล อาคาร บี ชั้น 7 ถนนกำแพงเพชร 6 แขวงดอนเมือง เขตดอนเมือง กรุงเทพมหานคร 10210',
                'tax_id' => '0123456789012',
                'phone' => '02-111-6289',
                'email' => 'info@innobic.com',
            ],
            'printDate' => now()->format('d/m/Y H:i'),
        ];
    }
    
    /**
     * Generate filename for Delivery Note PDF
     * 
     * @param PurchaseOrder $purchaseOrder
     * @return string
     */
    public function generateFilename(PurchaseOrder $purchaseOrder): string
    {
        $cleanPoNumber = str_replace('/', '-', $purchaseOrder->po_number);
        
        return sprintf(
            'DeliveryNote_%s_%s.pdf',
            $cleanPoNumber,
            now()->format('YmdHis')
        );
    }
}