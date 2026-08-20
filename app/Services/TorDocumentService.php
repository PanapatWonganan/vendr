<?php

namespace App\Services;

use App\Models\Company;
use App\Models\TermsOfReference;
use App\Models\TorTemplate;

/**
 * Logic for the TOR document builder (document_sections JSON).
 *
 * - buildForCompany(): resolve a template into a fresh document snapshot
 * - validate(): business rules (payment percentages must total 100%)
 * - visibleSections(): drop hidden sections and renumber for rendering
 */
class TorDocumentService
{
    /** Company short codes used in clause text (คำนำหน้าในเอกสาร). */
    private const COMPANY_SHORT = [
        'Innobic Asia' => 'INBA',
        'Innobic Nutrition' => 'INNT',
        'Innobic LL' => 'INBL',
    ];

    /** Thai legal names per short code (ใช้แทน {{company_full}} ในเนื้อเอกสาร). */
    private const COMPANY_FULL_TH = [
        'INBA' => 'บริษัท อินโนบิก (เอเซีย) จำกัด',
        'INNT' => 'บริษัท อินโนบิก นูทริชั่น จำกัด',
        'INBL' => 'บริษัท อินโนบิก แอลแอล จำกัด',
    ];

    public function buildForCompany(TorTemplate $template, ?Company $company): array
    {
        $short = null;
        $companyName = $company?->name ?? '';
        foreach (self::COMPANY_SHORT as $needle => $code) {
            if ($companyName !== '' && str_contains($companyName, $needle)) {
                $short = $code;
                break;
            }
        }

        $short = $short ?? 'INNT';

        return $template->buildDocumentSections([
            'company_full' => self::COMPANY_FULL_TH[$short] ?? ($company?->display_name ?? 'บริษัท อินโนบิก นูทริชั่น จำกัด'),
            'company_short' => $short,
        ]);
    }

    /**
     * Validate a document snapshot. Returns list of error messages (empty = valid).
     */
    public function validate(array $sections): array
    {
        $errors = [];

        foreach ($sections as $section) {
            if (($section['hidden'] ?? false) === true) {
                continue;
            }
            if (($section['type'] ?? '') === 'payment') {
                $errors = array_merge($errors, $this->validatePayment($section['data'] ?? []));
            }
            if (($section['type'] ?? '') === 'timeline') {
                $mode = $section['data']['mode'] ?? null;
                if (! in_array($mode, ['date_range', 'from_signing', 'other'], true)) {
                    $errors[] = 'ข้อระยะเวลาดำเนินการ: ต้องเลือกรูปแบบระยะเวลา 1 รูปแบบ';
                }
            }
        }

        return $errors;
    }

    /**
     * Payment rules: at least one option enabled, and the total of enabled
     * percentages (including installment rows) must equal 100.
     */
    private function validatePayment(array $data): array
    {
        $options = $data['options'] ?? [];
        $enabled = array_filter($options, fn ($o) => ($o['enabled'] ?? false) === true);

        if (empty($enabled)) {
            return ['ข้อการชำระเงิน: ต้องเลือกรูปแบบการชำระเงินอย่างน้อย 1 รูปแบบ'];
        }

        $total = $this->paymentTotal($data);
        if (abs($total - 100.0) > 0.01) {
            return ["ข้อการชำระเงิน: เปอร์เซ็นต์รวมทุกงวดต้องเท่ากับ 100% (ปัจจุบัน {$total}%)"];
        }

        return [];
    }

    public function paymentTotal(array $data): float
    {
        $total = 0.0;
        foreach ($data['options'] ?? [] as $option) {
            if (($option['enabled'] ?? false) !== true) {
                continue;
            }
            if (isset($option['rows']) && is_array($option['rows'])) {
                foreach ($option['rows'] as $row) {
                    $total += (float) ($row['percent'] ?? 0);
                }
            } else {
                $total += (float) ($option['percent'] ?? 0);
            }
        }

        return round($total, 2);
    }

    /**
     * Sections for rendering: hidden ones removed, numbered sections renumbered
     * sequentially (1..n) so removing e.g. ข้อ 11 shifts ข้อ 12→11.
     */
    public function visibleSections(array $sections): array
    {
        $visible = array_values(array_filter(
            $sections,
            fn ($s) => ($s['hidden'] ?? false) !== true
        ));

        $number = 0;
        foreach ($visible as &$section) {
            if (($section['number'] ?? null) === null || $section['number'] === '') {
                continue; // preamble — ไม่มีเลขข้อ
            }
            $number++;
            $section['render_number'] = (string) $number;
        }

        return $visible;
    }

    /**
     * Flatten the scope section into plain text for the legacy
     * terms_of_references.scope_of_work column (NOT NULL, used by list/search).
     */
    public function flattenScope(array $sections): string
    {
        foreach ($sections as $section) {
            if (($section['key'] ?? '') !== 'scope_of_work') {
                continue;
            }
            $lines = array_filter([trim((string) ($section['body'] ?? ''))]);
            foreach ($section['data']['items'] ?? [] as $item) {
                if (trim((string) ($item['text'] ?? '')) !== '') {
                    $lines[] = trim(($item['no'] ?? '').' '.$item['text']);
                }
                foreach ($item['children'] ?? [] as $child) {
                    if (trim((string) ($child['text'] ?? '')) !== '') {
                        $lines[] = trim(($child['no'] ?? '').' '.$child['text']);
                    }
                }
            }

            return implode("\n", $lines) ?: '-';
        }

        return '-';
    }

    /**
     * Snapshot an existing TOR's document for the "คัดลอก TOR เก่า" feature.
     */
    public function copyFrom(TermsOfReference $source): ?array
    {
        return $source->document_sections;
    }
}
