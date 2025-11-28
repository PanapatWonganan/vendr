<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use Illuminate\Support\Facades\Log;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class PurchaseOrderPdfService
{
    /**
     * Generate PDF for Purchase Order
     * 
     * @param PurchaseOrder $purchaseOrder
     * @return string PDF content as binary string
     */
    public function generatePdf(PurchaseOrder $purchaseOrder): string
    {
        try {
            // Determine which template to use based on work_type
            $template = $this->selectTemplate($purchaseOrder);
            
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
            
            $pdf = PDF::loadView($template, $data, [], $config);
            
            Log::info('PDF generated successfully', [
                'po_number' => $purchaseOrder->po_number,
                'template' => $template,
                'work_type' => $purchaseOrder->work_type,
            ]);
            
            // Return PDF as binary string
            return $pdf->output();
            
        } catch (\Exception $e) {
            Log::error('Failed to generate PDF', [
                'po_number' => $purchaseOrder->po_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Select appropriate template based on work_type
     * 
     * @param PurchaseOrder $purchaseOrder
     * @return string
     */
    private function selectTemplate(PurchaseOrder $purchaseOrder): string
    {
        // Check work_type to determine template
        switch ($purchaseOrder->work_type) {
            case 'hire':
                // จ้าง template - ใช้ SOW format (PCM-002)
                return 'pdf.purchase-orders.hire-sow';
            case 'rent':
                // เช่า template - ใช้ rent format (PCMN-002-FO)
                return 'pdf.purchase-orders.rent';
            case 'buy':
            default:
                // ซื้อ template - ใช้ purchase format (PCMN-002-FO)
                return 'pdf.purchase-orders.purchase';
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
            'company',
        ]);
        
        // Get work type label and document code
        $workTypeLabel = '';
        $documentCode = '';
        $participantLabel = ''; // ผู้ขาย/ผู้ให้เช่า/ผู้รับจ้าง
        
        switch ($purchaseOrder->work_type) {
            case 'hire':
                $workTypeLabel = 'จ้าง';
                $documentCode = 'PCM-002';
                $participantLabel = 'ผู้รับจ้าง/ผู้ให้บริการ';
                break;
            case 'rent':
                $workTypeLabel = 'เช่า';
                $documentCode = 'PCMN-002-FO';
                $participantLabel = 'ผู้ให้เช่า';
                break;
            case 'buy':
            default:
                $workTypeLabel = 'ซื้อ';
                $documentCode = 'PCMN-002-FO';
                $participantLabel = 'ผู้ขาย';
                break;
        }
        
        // Get procurement method label
        $procurementMethodLabels = [
            'agreement_price' => 'ตกลงราคา',
            'invitation_bid' => 'ประมูลโดยการประกาศเชิญ',
            'open_bid' => 'ประมูลโดยการประกาศเชิญชวนทั่วไป',
            'special_1' => 'พิเศษ ข้อ 1',
            'special_2' => 'พิเศษ ข้อ 2',
            'selection' => 'คัดเลือก',
        ];
        $procurementMethodLabel = $procurementMethodLabels[$purchaseOrder->procurement_method] ?? $purchaseOrder->procurement_method;
        
        // Get penalty rate based on work_type
        $penaltyRate = match ($purchaseOrder->work_type) {
            'hire' => '0.1',
            'buy', 'rent' => '0.2',
            default => '0.2'
        };
        
        // Get company data from relationship
        $companyData = [
            'name' => $purchaseOrder->company->description ?? $purchaseOrder->company->name ?? 'บริษัท อินโนบิค จำกัด',
            'address' => $purchaseOrder->company->address ?? 'เลขที่ 425/1 อาคาร เอนโก้เทอร์มินอล อาคาร บี ชั้น 7 ถนนกำแพงเพชร 6 แขวงดอนเมือง เขตดอนเมือง กรุงเทพมหานคร 10210',
            'tax_id' => $purchaseOrder->company->tax_id ?? '0105563067701',
            'phone' => $purchaseOrder->company->phone ?? '02-111-6289',
            'email' => $purchaseOrder->company->email ?? 'info@innobic.com',
        ];

        return [
            'purchaseOrder' => $purchaseOrder,
            'workTypeLabel' => $workTypeLabel,
            'documentCode' => $documentCode,
            'participantLabel' => $participantLabel,
            'penaltyRate' => $penaltyRate,
            'procurementMethodLabel' => $procurementMethodLabel,
            'company' => $companyData,
            'printDate' => now()->format('d/m/Y H:i'),
            'approvalDate' => $purchaseOrder->approved_at ? $purchaseOrder->approved_at->format('d/m/Y') : now()->format('d/m/Y'),
        ];
    }
    
    /**
     * Generate filename for PDF
     * 
     * @param PurchaseOrder $purchaseOrder
     * @return string
     */
    public function generateFilename(PurchaseOrder $purchaseOrder): string
    {
        $type = match ($purchaseOrder->work_type) {
            'hire' => 'HIRE',
            'rent' => 'RENT',
            'buy' => 'PURCHASE',
            default => 'PURCHASE'
        };
        
        $cleanPoNumber = str_replace('/', '-', $purchaseOrder->po_number);
        
        return sprintf(
            'SOW_%s_%s_%s.pdf',
            $type,
            $cleanPoNumber,
            now()->format('YmdHis')
        );
    }
}