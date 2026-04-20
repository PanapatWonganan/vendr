<?php

namespace App\Services;

use App\Models\TermsOfReference;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use Illuminate\Support\Facades\Log;

class TorPdfService
{
    /**
     * Generate PDF for Terms of Reference
     */
    public function generatePdf(TermsOfReference $tor): string
    {
        try {
            $data = $this->prepareData($tor);

            $config = [
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'tempDir' => storage_path('app/temp'),
                'default_font' => 'freeserif',
                'autoLangToFont' => true,
                'autoScriptToLang' => true,
            ];

            $pdf = PDF::loadView('pdf.tor', $data, [], $config);

            Log::info('TOR PDF generated successfully', [
                'tor_number' => $tor->tor_number,
            ]);

            return $pdf->output();

        } catch (\Exception $e) {
            Log::error('Failed to generate TOR PDF', [
                'tor_number' => $tor->tor_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate filename for TOR PDF
     */
    public function generateFilename(TermsOfReference $tor): string
    {
        $cleanNumber = str_replace(['/', '\\'], '-', $tor->tor_number);
        return sprintf('TOR_%s_%s.pdf', $cleanNumber, now()->format('YmdHis'));
    }

    /**
     * Prepare data for PDF view
     */
    private function prepareData(TermsOfReference $tor): array
    {
        $tor->load([
            'company',
            'department',
            'creator',
            'approver',
            'procurementCommittee',
            'inspectionCommittee',
            'items',
        ]);

        $torTypeLabels = TermsOfReference::getTorTypeOptions();
        $workTypeLabels = TermsOfReference::getWorkTypeOptions();
        $methodLabels = TermsOfReference::getProcurementMethodOptions();
        $statusLabels = TermsOfReference::getStatusOptions();
        $priorityLabels = TermsOfReference::getPriorityOptions();

        $companyData = [
            'name' => $tor->company->description ?? $tor->company->name ?? 'บริษัท อินโนบิค จำกัด',
            'address' => $tor->company->address ?? '',
            'tax_id' => $tor->company->tax_id ?? '',
            'phone' => $tor->company->phone ?? '',
        ];

        return [
            'tor' => $tor,
            'company' => $companyData,
            'torTypeLabel' => $torTypeLabels[$tor->tor_type] ?? $tor->tor_type,
            'workTypeLabel' => $workTypeLabels[$tor->work_type] ?? $tor->work_type ?? '-',
            'procurementMethodLabel' => $methodLabels[$tor->procurement_method] ?? $tor->procurement_method ?? '-',
            'statusLabel' => $statusLabels[$tor->status] ?? $tor->status,
            'priorityLabel' => $priorityLabels[$tor->priority] ?? $tor->priority,
            'printDate' => now()->format('d/m/Y H:i'),
        ];
    }
}
