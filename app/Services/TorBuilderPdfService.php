<?php

namespace App\Services;

use App\Models\TermsOfReference;
use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;

/**
 * PDF export for the TOR document builder (document_sections JSON).
 *
 * Mirrors the customer's paper form: the full header block (logo, company
 * names, TOR box, ชื่อเรื่อง/ผู้รับผิดชอบ/หน่วยงาน) repeats on EVERY page via
 * SetHTMLHeader, and the footer carries หน้า X/Y + form code — same behaviour
 * as the Word template's header1.xml/footer1.xml.
 */
class TorBuilderPdfService
{
    /** Thai → English company display names (same map as the HTML preview). */
    private const COMPANY_EN = [
        'บริษัท อินโนบิก นูทริชั่น จำกัด' => 'Innobic Nutrition Company Limited',
        'บริษัท อินโนบิก (เอเซีย) จำกัด' => 'Innobic (Asia) Company Limited',
        'บริษัท อินโนบิก แอลแอล จำกัด' => 'Innobic LL Company Limited',
    ];

    public function __construct(private TorDocumentService $documents) {}

    public function generate(TermsOfReference $tor): string
    {
        try {
            $sections = $this->documents->visibleSections($tor->document_sections ?? []);

            $preamble = collect($sections)->firstWhere('key', 'preamble');
            $companyTh = trim(str_replace(['เงื่อนไขข้อกำหนด', '(TOR)'], '', $preamble['title'] ?? ''))
                ?: 'บริษัท อินโนบิก นูทริชั่น จำกัด';
            $companyEn = self::COMPANY_EN[$companyTh] ?? 'Innobic Company Limited';
            $responsible = $preamble['data']['responsible_name'] ?? null;

            $tempDir = storage_path('app/temp');
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0775, true);
            }

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'tempDir' => $tempDir,
                'default_font' => 'freeserif',
                'default_font_size' => 14,
                'autoLangToFont' => true,
                'autoScriptToLang' => true,
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 48,
                'margin_bottom' => 18,
                'margin_header' => 8,
                'margin_footer' => 8,
                // header สูงไม่เท่ากันตามความยาวชื่อเรื่อง — ให้ mPDF ขยาย margin อัตโนมัติ
                'setAutoTopMargin' => 'stretch',
                'setAutoBottomMargin' => 'stretch',
            ]);

            $headerData = [
                'tor' => $tor,
                'companyTh' => $companyTh,
                'companyEn' => $companyEn,
                'responsible' => $responsible,
            ];

            $mpdf->SetHTMLHeader(view('pdfs.tor-document-header', $headerData)->render());
            $mpdf->SetHTMLFooter(view('pdfs.tor-document-footer')->render());
            $mpdf->WriteHTML(view('pdfs.tor-document', [
                'tor' => $tor,
                'sections' => $sections,
                'companyTh' => $companyTh,
            ])->render());

            return $mpdf->Output('', 'S');
        } catch (\Throwable $e) {
            Log::error('Failed to generate TOR builder PDF', [
                'tor_number' => $tor->tor_number,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function filename(TermsOfReference $tor): string
    {
        $cleanNumber = str_replace(['/', '\\'], '-', $tor->tor_number);

        return "TOR_{$cleanNumber}.pdf";
    }
}
