<?php

namespace App\Services;

use App\Models\TermsOfReference;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TorAiService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', '');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
    }

    /**
     * Generate a TOR draft based on basic info
     */
    public function generateDraft(array $basicInfo): array
    {
        if (!$this->apiKey) {
            Log::warning('OpenAI API key not configured, using template fallback for TOR draft');
            return $this->fallbackDraft($basicInfo);
        }

        $torType = $basicInfo['tor_type'] ?? 'goods';
        $prompt = $this->buildDraftPrompt($basicInfo);

        try {
            $response = Http::timeout(90)
                ->withToken($this->apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $this->getDraftSystemPrompt()],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.4,
                    'max_tokens' => 4000,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $content = $result['choices'][0]['message']['content'] ?? '';
                $parsed = json_decode($content, true);

                if ($parsed) {
                    Log::info('TOR AI draft generated successfully', [
                        'tor_type' => $torType,
                        'title' => $basicInfo['title'] ?? '',
                    ]);
                    return $parsed;
                }
            }

            Log::warning('OpenAI API returned unexpected response for TOR draft');
            return $this->fallbackDraft($basicInfo);

        } catch (\Exception $e) {
            Log::error('TOR AI draft generation failed: ' . $e->getMessage());
            return $this->fallbackDraft($basicInfo);
        }
    }

    /**
     * Review a TOR for compliance and quality
     */
    public function reviewTor(TermsOfReference $tor): array
    {
        if (!$this->apiKey) {
            Log::warning('OpenAI API key not configured, using rule-based review');
            return $this->fallbackReview($tor);
        }

        $prompt = $this->buildReviewPrompt($tor);

        try {
            $response = Http::timeout(60)
                ->withToken($this->apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $this->getReviewSystemPrompt()],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.3,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $content = $result['choices'][0]['message']['content'] ?? '';
                $parsed = json_decode($content, true);

                if ($parsed) {
                    Log::info('TOR AI review completed', [
                        'tor_number' => $tor->tor_number,
                        'score' => $parsed['score'] ?? null,
                    ]);
                    return $parsed;
                }
            }

            return $this->fallbackReview($tor);

        } catch (\Exception $e) {
            Log::error('TOR AI review failed: ' . $e->getMessage());
            return $this->fallbackReview($tor);
        }
    }

    /**
     * Improve a specific field content
     */
    public function improveField(string $field, string $content, array $context): string
    {
        if (!$this->apiKey || empty($content)) {
            return $content;
        }

        $fieldLabels = [
            'background' => 'ความเป็นมา/หลักการและเหตุผล',
            'objectives' => 'วัตถุประสงค์',
            'scope_of_work' => 'ขอบเขตของงาน',
            'deliverables' => 'ผลงานที่ต้องส่งมอบ',
            'specifications' => 'ข้อกำหนดทางเทคนิค',
            'qualification_requirements' => 'คุณสมบัติผู้เสนอราคา',
            'payment_terms' => 'เงื่อนไขการจ่ายเงิน',
            'warranty_requirements' => 'การรับประกัน',
            'penalty_clause' => 'เงื่อนไขเบี้ยปรับ',
        ];

        $fieldLabel = $fieldLabels[$field] ?? $field;

        try {
            $response = Http::timeout(60)
                ->withToken($this->apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $this->getImproveSystemPrompt()],
                        ['role' => 'user', 'content' => "ช่วยปรับปรุงเนื้อหาส่วน \"{$fieldLabel}\" ของ TOR:\n\nประเภท TOR: " . ($context['tor_type'] ?? 'goods') . "\nชื่อ: " . ($context['title'] ?? '') . "\nงบประมาณ: " . ($context['budget_estimate'] ?? 'ไม่ระบุ') . "\n\nเนื้อหาปัจจุบัน:\n{$content}\n\nกรุณาปรับปรุงให้ดีขึ้นตามหลัก SMART และกฎหมายจัดซื้อจัดจ้าง"],
                    ],
                    'temperature' => 0.3,
                ]);

            if ($response->successful()) {
                $result = $response->json();
                return $result['choices'][0]['message']['content'] ?? $content;
            }

            return $content;

        } catch (\Exception $e) {
            Log::error('TOR AI improve failed: ' . $e->getMessage());
            return $content;
        }
    }

    // ─── System Prompts ──────────────────────────────────────────

    protected function getDraftSystemPrompt(): string
    {
        return <<<'PROMPT'
คุณเป็นผู้เชี่ยวชาญด้านการเขียน TOR (Terms of Reference / ขอบเขตของงาน) สำหรับการจัดซื้อจัดจ้างภาคเอกชนในประเทศไทย โดยอ้างอิงแนวปฏิบัติจากกฎหมายจัดซื้อจัดจ้าง

## กฎหมายและมาตรฐานอ้างอิง
- พ.ร.บ. การจัดซื้อจัดจ้างและการบริหารพัสดุภาครัฐ พ.ศ. 2560 (ใช้เป็นแนวปฏิบัติ)
- ระเบียบกระทรวงการคลังว่าด้วยการจัดซื้อจัดจ้าง ข้อ 21
- หลัก SMART (Specific, Measurable, Achievable, Relevant, Time-bound)
- มาตรฐาน มอก. / ISO / IEC ตามความเหมาะสม

## กฎที่ต้องปฏิบัติอย่างเคร่งครัด
- ห้ามล็อคสเปค (ห้ามระบุยี่ห้อเฉพาะเจาะจง ใช้ "หรือเทียบเท่า" เสมอ)
- ต้องอ้างอิงมาตรฐาน มอก. / ISO / มาตรฐานสากลแทนการระบุยี่ห้อ
- ต้องเปิดกว้างให้แข่งขันอย่างเป็นธรรม
- เนื้อหาทั้งหมดต้องเป็นภาษาไทย

## ข้อกำหนดคุณภาพเนื้อหา — สำคัญมาก
ทุกฟิลด์ต้องเขียนอย่างละเอียด เจาะลึก เฉพาะเจาะจงกับโครงการ ห้ามเขียนกว้างๆ คลุมเครือ:

### background (ความเป็นมา) — ต้องมีอย่างน้อย 3 ย่อหน้า:
- ย่อหน้า 1: บริบทองค์กร ปัญหาหรือความต้องการที่เกิดขึ้น
- ย่อหน้า 2: ผลกระทบหากไม่ดำเนินการ ความจำเป็นเร่งด่วน
- ย่อหน้า 3: แนวทางแก้ไขที่เสนอ เหตุผลเลือกวิธีนี้

### objectives (วัตถุประสงค์) — ต้องเป็น SMART:
- ระบุเป็นข้อๆ อย่างน้อย 3-5 ข้อ
- แต่ละข้อต้องวัดผลได้ (มีตัวเลข เปอร์เซ็นต์ หรือเกณฑ์ชัดเจน)
- ห้ามเขียนกว้างเช่น "เพื่อให้ดีขึ้น" ต้องระบุว่าดีขึ้นอย่างไร วัดอย่างไร

### scope_of_work (ขอบเขตของงาน) — ต้องละเอียดมาก:
- แบ่งเป็นหัวข้อย่อยพร้อมรายละเอียดแต่ละข้อ
- ระบุขั้นตอนการดำเนินงานเป็นลำดับ
- ระบุสิ่งที่รวมอยู่ในขอบเขต และสิ่งที่ไม่รวม
- ระบุสถานที่ดำเนินงาน ระยะเวลาแต่ละขั้นตอน

### deliverables (ผลงานที่ต้องส่งมอบ) — ต้องชัดเจน:
- ระบุรายการส่งมอบแต่ละรายการพร้อมรายละเอียด
- ระบุรูปแบบการส่งมอบ (กี่ชุด กี่สำเนา รูปแบบไฟล์)
- ระบุเงื่อนไขการตรวจรับแต่ละรายการ
- ระบุกำหนดส่งมอบแต่ละงวด

### specifications (ข้อกำหนดทางเทคนิค) — สำคัญมาก:
- ระบุข้อกำหนดเป็นข้อๆ อย่างละเอียด
- อ้างอิงมาตรฐาน มอก./ISO/IEC ที่เกี่ยวข้อง
- ระบุเป็นช่วง (range) หรือค่าขั้นต่ำ ห้ามระบุค่าเดียวตายตัว
- แยกข้อกำหนดบังคับ (mandatory) กับข้อกำหนดที่ต้องการ (desirable)

### qualification_requirements (คุณสมบัติผู้เสนอราคา) — ตาม ม.64:
- เป็นนิติบุคคลจดทะเบียนในประเทศไทย
- ไม่เป็นผู้ถูกระบุในบัญชีรายชื่อผู้ทิ้งงาน
- ระบุทุนจดทะเบียนขั้นต่ำตามวงเงินงบประมาณ
- ระบุผลงานย้อนหลังที่ต้องการ (จำนวนปี มูลค่าขั้นต่ำ)
- ระบุใบอนุญาต/ใบรับรองที่จำเป็นตามประเภทงาน

### evaluation_criteria — ต้องรวม 100%:
- ระบุอย่างน้อย 3-5 เกณฑ์ พร้อมคำอธิบายวิธีให้คะแนน
- น้ำหนักต้องรวมเป็น 100% พอดี
- เกณฑ์ต้องสอดคล้องกับประเภทงาน

### payment_terms (เงื่อนไขการจ่ายเงิน):
- ระบุจำนวนงวด เงื่อนไขแต่ละงวด เปอร์เซ็นต์
- ระบุระยะเวลาการชำระเงินหลังตรวจรับ
- ระบุเอกสารที่ต้องใช้ในการวางบิล

### warranty_requirements (การรับประกัน):
- ระบุระยะเวลารับประกันที่เหมาะสมกับประเภทงาน
- ระบุขอบเขตการรับประกัน สิ่งที่ครอบคลุมและไม่ครอบคลุม
- ระบุ response time ในการแก้ไขปัญหา
- ระบุเงื่อนไขการต่อสัญญารับประกัน (ถ้ามี)

### penalty_clause (เงื่อนไขเบี้ยปรับ):
- อัตราปรับรายวันเป็นร้อยละ สอดคล้องกับวงเงิน
- เพดานค่าปรับสูงสุด
- เงื่อนไขยกเว้นค่าปรับ (เหตุสุดวิสัย)

## รูปแบบ JSON ที่ต้องตอบ:
{
    "background": "ความเป็นมา (อย่างน้อย 3 ย่อหน้า)",
    "objectives": "วัตถุประสงค์ (อย่างน้อย 3-5 ข้อ แบบ SMART)",
    "scope_of_work": "ขอบเขตของงาน (แบ่งขั้นตอนชัดเจน)",
    "deliverables": "ผลงานที่ต้องส่งมอบ (ระบุรายการ เงื่อนไข กำหนดส่ง)",
    "specifications": "ข้อกำหนดทางเทคนิค (อ้างอิงมาตรฐาน มีรายละเอียดเฉพาะ)",
    "qualification_requirements": "คุณสมบัติผู้เสนอราคา (ตาม ม.64)",
    "evaluation_criteria": [{"name":"ชื่อเกณฑ์","weight":40,"description":"วิธีให้คะแนนละเอียด"}],
    "payment_terms": "เงื่อนไขการจ่ายเงิน (จำนวนงวด เปอร์เซ็นต์ เงื่อนไข)",
    "warranty_requirements": "การรับประกัน (ระยะเวลา ขอบเขต response time)",
    "penalty_clause": "เงื่อนไขเบี้ยปรับ (อัตรา เพดาน ข้อยกเว้น)",
    "suggested_duration_days": 90,
    "suggested_items": [{"description":"รายละเอียดชัดเจน","quantity":1,"unit":"หน่วยนับ"}]
}
PROMPT;
    }

    protected function getReviewSystemPrompt(): string
    {
        return <<<'PROMPT'
คุณเป็นผู้ตรวจสอบ TOR (Terms of Reference) ตามระเบียบการจัดซื้อจัดจ้างของประเทศไทย

ตรวจสอบ:
1. ล็อคสเปค: ระบุยี่ห้อ/คุณลักษณะเจาะจงเกินไปหรือไม่
2. หลัก SMART: วัดผลได้? มีกรอบเวลา? เป็นไปได้จริง?
3. องค์ประกอบครบตามระเบียบหรือไม่
4. ความสอดคล้องของงบประมาณกับขอบเขตงาน
5. คุณสมบัติผู้เสนอราคาตาม ม.64

ตอบเป็น JSON:
{
    "score": 85,
    "grade": "B",
    "issues": [
        {"field": "specifications", "severity": "warning", "message": "ปัญหาที่พบ", "suggestion": "คำแนะนำ"}
    ],
    "passed": [
        {"field": "scope_of_work", "message": "ขอบเขตงานชัดเจน ครบถ้วน"}
    ],
    "summary": "สรุปผลการตรวจสอบ 2-3 ประโยค"
}

severity: "error" (ต้องแก้ไข), "warning" (ควรแก้ไข), "info" (ข้อสังเกต)
grade: A (90-100), B (75-89), C (60-74), D (40-59), F (<40)
PROMPT;
    }

    protected function getImproveSystemPrompt(): string
    {
        return <<<'PROMPT'
คุณเป็นผู้เชี่ยวชาญด้านการเขียน TOR ตามกฎหมายจัดซื้อจัดจ้างไทย
ปรับปรุงเนื้อหาที่ได้รับให้:
- เป็นไปตามหลัก SMART
- ไม่ล็อคสเปค
- ใช้ภาษาที่ชัดเจน กระชับ
- อ้างอิงมาตรฐาน มอก./สากล
- ครอบคลุมประเด็นสำคัญ

ตอบเป็นเนื้อหาที่ปรับปรุงแล้วเท่านั้น (ไม่ต้องอธิบายว่าแก้ไขอะไร)
ตอบเป็นภาษาไทย
PROMPT;
    }

    // ─── Prompt Builders ─────────────────────────────────────────

    protected function buildDraftPrompt(array $basicInfo): string
    {
        $torTypeLabels = TermsOfReference::getTorTypeOptions();
        $workTypeLabels = TermsOfReference::getWorkTypeOptions();
        $methodLabels = TermsOfReference::getProcurementMethodOptions();

        $title = $basicInfo['title'] ?? 'ไม่ระบุ';
        $torType = $basicInfo['tor_type'] ?? 'goods';
        $torTypeLabel = $torTypeLabels[$torType] ?? $torType;
        $workType = $workTypeLabels[$basicInfo['work_type'] ?? ''] ?? $basicInfo['work_type'] ?? 'ซื้อ';
        $method = $methodLabels[$basicInfo['procurement_method'] ?? ''] ?? $basicInfo['procurement_method'] ?? 'ตกลงราคา';
        $budget = $basicInfo['budget_estimate'] ?? 0;
        $department = $basicInfo['department'] ?? 'ไม่ระบุ';
        $category = $basicInfo['category'] ?? 'ไม่ระบุ';

        // Determine budget tier for context
        $budgetTier = match (true) {
            $budget <= 100000 => 'วงเงินน้อย (ไม่เกิน 100,000 บาท) — TOR ควรกระชับแต่ครบถ้วน',
            $budget <= 500000 => 'วงเงินปานกลาง (100,001-500,000 บาท) — TOR ต้องละเอียดพอสมควร',
            $budget <= 2000000 => 'วงเงินสูง (500,001-2,000,000 บาท) — TOR ต้องละเอียดมาก มีข้อกำหนดเข้มงวด',
            default => 'วงเงินสูงมาก (เกิน 2,000,000 บาท) — TOR ต้องละเอียดที่สุด ครบทุกประเด็น มีเกณฑ์ประเมินรัดกุม',
        };

        // Type-specific guidance
        $typeGuidance = match ($torType) {
            'goods' => "เน้นข้อกำหนดทางเทคนิคของพัสดุ สเปค มาตรฐาน มอก./ISO การรับประกัน ระยะเวลาจัดส่ง การติดตั้ง (ถ้ามี) อะไหล่และบริการหลังการขาย",
            'services' => "เน้น SLA (Service Level Agreement) KPI ที่วัดผลได้ คุณสมบัติทีมงาน/บุคลากร แผนการดำเนินงาน รายงานความคืบหน้า การถ่ายทอดความรู้",
            'construction' => "เน้นแบบรูปรายการ BOQ มาตรฐานงานก่อสร้าง ความปลอดภัย พ.ร.บ.ควบคุมอาคาร ใบอนุญาตผู้รับเหมา ประกันภัยงานก่อสร้าง As-Built Drawing",
            'consulting' => "เน้นคุณสมบัติที่ปรึกษา ประสบการณ์ทีมงาน Methodology แผนการศึกษา/วิจัย การนำเสนอผลงาน การถ่ายทอดความรู้ ลิขสิทธิ์ผลงาน",
            default => "เขียนให้ครบถ้วนตามประเภทงาน",
        };

        $parts = [];
        $parts[] = "## คำสั่ง";
        $parts[] = "กรุณาเขียน TOR ที่ละเอียด เจาะจง ใช้งานได้จริง สำหรับโครงการต่อไปนี้:";
        $parts[] = "";
        $parts[] = "## ข้อมูลโครงการ";
        $parts[] = "- **ชื่อโครงการ:** {$title}";
        $parts[] = "- **ประเภท TOR:** {$torTypeLabel}";
        $parts[] = "- **ประเภทงาน:** {$workType}";
        $parts[] = "- **วิธีจัดซื้อจัดจ้าง:** {$method}";
        $parts[] = "- **งบประมาณโดยประมาณ:** " . number_format((float) $budget, 2) . " บาท";
        $parts[] = "- **ระดับวงเงิน:** {$budgetTier}";
        $parts[] = "- **แผนกที่ต้องการ:** {$department}";
        $parts[] = "- **หมวดหมู่:** {$category}";
        $parts[] = "";
        $parts[] = "## แนวทางการเขียนเฉพาะประเภทนี้";
        $parts[] = $typeGuidance;
        $parts[] = "";
        $parts[] = "## ข้อกำหนดเพิ่มเติม";
        $parts[] = "- เขียนเนื้อหาให้เจาะจงกับ \"{$title}\" ห้ามเขียนแบบกว้างๆ ทั่วไป";
        $parts[] = "- ทุกฟิลด์ต้องมีเนื้อหาที่นำไปใช้งานได้จริง ไม่ใช่แค่ placeholder";
        $parts[] = "- ข้อกำหนดทางเทคนิคต้องสอดคล้องกับชื่อโครงการและงบประมาณ";
        $parts[] = "- แบ่งรายการ suggested_items ให้เหมาะสมกับขอบเขตงาน อย่างน้อย 2-5 รายการ";
        $parts[] = "- evaluation_criteria ต้องรวมน้ำหนักเป็น 100% พอดี";

        return implode("\n", $parts);
    }

    protected function buildReviewPrompt(TermsOfReference $tor): string
    {
        $parts = [];
        $parts[] = "## TOR ที่ต้องตรวจสอบ";
        $parts[] = "- เลข TOR: {$tor->tor_number}";
        $parts[] = "- ชื่อ: {$tor->title}";
        $parts[] = "- ประเภท: " . ($tor->tor_type_label);
        $parts[] = "- วิธีจัดซื้อ: {$tor->procurement_method}";
        $parts[] = "- งบประมาณ: " . number_format($tor->budget_estimate ?? 0, 2) . " {$tor->currency}";
        $parts[] = "";
        $parts[] = "### ความเป็นมา";
        $parts[] = strip_tags($tor->background ?? 'ไม่ระบุ');
        $parts[] = "";
        $parts[] = "### วัตถุประสงค์";
        $parts[] = strip_tags($tor->objectives ?? 'ไม่ระบุ');
        $parts[] = "";
        $parts[] = "### ขอบเขตงาน";
        $parts[] = strip_tags($tor->scope_of_work ?? 'ไม่ระบุ');
        $parts[] = "";
        $parts[] = "### ผลงานที่ส่งมอบ";
        $parts[] = strip_tags($tor->deliverables ?? 'ไม่ระบุ');
        $parts[] = "";
        $parts[] = "### ข้อกำหนดทางเทคนิค";
        $parts[] = strip_tags($tor->specifications ?? 'ไม่ระบุ');
        $parts[] = "";
        $parts[] = "### คุณสมบัติผู้เสนอราคา";
        $parts[] = strip_tags($tor->qualification_requirements ?? 'ไม่ระบุ');
        $parts[] = "";
        $parts[] = "### เกณฑ์ประเมิน";
        if ($tor->evaluation_criteria) {
            foreach ($tor->evaluation_criteria as $criteria) {
                $parts[] = "- " . ($criteria['name'] ?? '') . " (" . ($criteria['weight'] ?? 0) . "%)";
            }
        } else {
            $parts[] = "ไม่ระบุ";
        }
        $parts[] = "";
        $parts[] = "### รายการ";
        foreach ($tor->items as $item) {
            $parts[] = "- {$item->description}: {$item->quantity} {$item->unit_of_measure}";
        }

        return implode("\n", $parts);
    }

    // ─── Fallback Methods ────────────────────────────────────────

    protected function fallbackDraft(array $basicInfo): array
    {
        $title = $basicInfo['title'] ?? 'โครงการจัดซื้อจัดจ้าง';
        $torType = $basicInfo['tor_type'] ?? 'goods';
        $budget = $basicInfo['budget_estimate'] ?? 0;

        $templates = [
            'goods' => [
                'background' => "ด้วยหน่วยงานมีความจำเป็นต้องจัดซื้อ{$title} เพื่อใช้ในการดำเนินงานตามภารกิจของหน่วยงาน จึงจำเป็นต้องจัดทำขอบเขตของงาน (Terms of Reference) เพื่อกำหนดรายละเอียดและคุณลักษณะเฉพาะของพัสดุที่ต้องการ",
                'objectives' => "1. เพื่อจัดหาพัสดุที่มีคุณภาพตรงตามความต้องการใช้งาน\n2. เพื่อให้การดำเนินงานเป็นไปอย่างมีประสิทธิภาพ\n3. เพื่อให้เป็นไปตามระเบียบการจัดซื้อจัดจ้าง",
                'scope_of_work' => "จัดซื้อ{$title} ตามรายละเอียดคุณลักษณะเฉพาะที่กำหนด โดยผู้ขายต้องจัดส่งพัสดุที่มีคุณภาพตรงตามข้อกำหนด พร้อมติดตั้ง (ถ้ามี) และทดสอบให้ใช้งานได้ปกติ",
                'deliverables' => "1. พัสดุตามรายการที่กำหนด พร้อมคู่มือการใช้งาน\n2. ใบรับประกันสินค้า\n3. เอกสารการฝึกอบรมการใช้งาน (ถ้ามี)",
                'warranty_requirements' => "ผู้ขายต้องรับประกันสินค้าไม่น้อยกว่า 1 ปี นับจากวันที่ตรวจรับ หากพบว่าสินค้าชำรุดบกพร่องจากการใช้งานปกติ ผู้ขายต้องซ่อมแซมหรือเปลี่ยนใหม่โดยไม่คิดค่าใช้จ่าย",
            ],
            'services' => [
                'background' => "ด้วยหน่วยงานมีความจำเป็นต้องจ้าง{$title} เพื่อสนับสนุนการดำเนินงานตามภารกิจ เนื่องจากต้องอาศัยผู้เชี่ยวชาญเฉพาะทาง จึงจำเป็นต้องจัดทำขอบเขตของงาน (Terms of Reference)",
                'objectives' => "1. เพื่อให้ได้บริการที่มีคุณภาพตามมาตรฐาน\n2. เพื่อให้การดำเนินงานเป็นไปตามกรอบเวลาที่กำหนด\n3. เพื่อให้การบริหารโครงการเป็นไปอย่างมีประสิทธิภาพ",
                'scope_of_work' => "จ้าง{$title} ตามรายละเอียดที่กำหนด โดยผู้รับจ้างต้องดำเนินการให้แล้วเสร็จตามระยะเวลาและมาตรฐานที่กำหนด",
                'deliverables' => "1. รายงานผลการดำเนินงาน\n2. เอกสารส่งมอบงานตามข้อกำหนด\n3. รายงานสรุปผลเมื่อเสร็จสิ้นโครงการ",
                'warranty_requirements' => "ผู้รับจ้างต้องรับประกันผลงานไม่น้อยกว่า 1 ปี นับจากวันตรวจรับ",
            ],
            'construction' => [
                'background' => "ด้วยหน่วยงานมีความจำเป็นต้องดำเนินโครงการ{$title} เพื่อปรับปรุง/ก่อสร้างให้เหมาะสมกับการใช้งาน จึงจำเป็นต้องจัดทำขอบเขตของงาน (Terms of Reference)",
                'objectives' => "1. เพื่อก่อสร้าง/ปรับปรุงให้ได้ตามแบบรูปรายการที่กำหนด\n2. เพื่อให้มีมาตรฐานความปลอดภัยตามกฎหมาย\n3. เพื่อให้แล้วเสร็จภายในระยะเวลาที่กำหนด",
                'scope_of_work' => "ดำเนินการก่อสร้าง/ปรับปรุง{$title} ตามแบบรูปรายการ ปริมาณงาน และข้อกำหนดที่กำหนด",
                'deliverables' => "1. งานก่อสร้างที่แล้วเสร็จตามแบบรูปรายการ\n2. แบบ As-Built\n3. คู่มือการใช้งาน/บำรุงรักษา\n4. เอกสารรับประกันผลงาน",
                'warranty_requirements' => "ผู้รับจ้างต้องรับประกันผลงานไม่น้อยกว่า 2 ปี นับจากวันตรวจรับ",
            ],
            'consulting' => [
                'background' => "ด้วยหน่วยงานมีความจำเป็นต้องจ้างที่ปรึกษาเพื่อ{$title} เนื่องจากต้องอาศัยผู้เชี่ยวชาญเฉพาะด้าน จึงจำเป็นต้องจัดทำขอบเขตของงาน (Terms of Reference)",
                'objectives' => "1. เพื่อให้ได้คำปรึกษาและข้อเสนอแนะที่มีคุณภาพ\n2. เพื่อพัฒนาแนวทางการดำเนินงานที่เหมาะสม\n3. เพื่อถ่ายทอดความรู้แก่บุคลากร",
                'scope_of_work' => "จ้างที่ปรึกษาเพื่อ{$title} ตามรายละเอียดที่กำหนด",
                'deliverables' => "1. รายงานผลการศึกษา/วิเคราะห์\n2. ข้อเสนอแนะและแนวทางปฏิบัติ\n3. รายงานสรุปผล\n4. การจัดอบรม/ถ่ายทอดความรู้",
                'warranty_requirements' => "ที่ปรึกษาต้องให้การสนับสนุนหลังส่งมอบงานอย่างน้อย 3 เดือน",
            ],
        ];

        $template = $templates[$torType] ?? $templates['goods'];

        return [
            'background' => $template['background'],
            'objectives' => $template['objectives'],
            'scope_of_work' => $template['scope_of_work'],
            'deliverables' => $template['deliverables'],
            'specifications' => "คุณลักษณะเฉพาะต้องเป็นไปตามมาตรฐาน มอก. หรือมาตรฐานสากลที่เทียบเท่า",
            'qualification_requirements' => "1. เป็นนิติบุคคลที่จดทะเบียนในประเทศไทย\n2. ไม่เป็นผู้ที่ถูกระบุชื่อไว้ในบัญชีรายชื่อผู้ทิ้งงาน\n3. มีผลงานในลักษณะงานเดียวกัน\n4. มีฐานะการเงินมั่นคง",
            'evaluation_criteria' => [
                ['name' => 'ราคา', 'weight' => 40, 'description' => 'เสนอราคาต่ำสุดและเหมาะสม'],
                ['name' => 'คุณภาพ', 'weight' => 30, 'description' => 'คุณภาพตรงตามข้อกำหนด'],
                ['name' => 'ประสบการณ์', 'weight' => 20, 'description' => 'ประสบการณ์ในงานลักษณะเดียวกัน'],
                ['name' => 'ระยะเวลา', 'weight' => 10, 'description' => 'สามารถส่งมอบได้ตามกำหนด'],
            ],
            'payment_terms' => "ชำระเงินภายใน 30 วัน หลังตรวจรับเรียบร้อยแล้ว โดยผู้ขาย/ผู้รับจ้างต้องวางบิลพร้อมเอกสารครบถ้วน",
            'warranty_requirements' => $template['warranty_requirements'],
            'penalty_clause' => "กรณีส่งมอบล่าช้า ปรับเป็นรายวัน ในอัตราร้อยละ 0.1-0.2 ของราคาที่ยังไม่ได้รับมอบ ต่อวัน แต่ไม่เกินร้อยละ 10 ของวงเงินตามสัญญา",
            'suggested_duration_days' => 90,
            'suggested_items' => [
                ['description' => $title, 'quantity' => 1, 'unit' => 'งาน'],
            ],
        ];
    }

    protected function fallbackReview(TermsOfReference $tor): array
    {
        $issues = [];
        $passed = [];
        $score = 100;

        // Check required fields
        if (empty($tor->background)) {
            $issues[] = ['field' => 'background', 'severity' => 'warning', 'message' => 'ไม่ได้ระบุความเป็นมา/หลักการและเหตุผล', 'suggestion' => 'ควรระบุเหตุผลความจำเป็นในการจัดซื้อจัดจ้าง'];
            $score -= 5;
        } else {
            $passed[] = ['field' => 'background', 'message' => 'มีการระบุความเป็นมาแล้ว'];
        }

        if (empty($tor->objectives)) {
            $issues[] = ['field' => 'objectives', 'severity' => 'warning', 'message' => 'ไม่ได้ระบุวัตถุประสงค์', 'suggestion' => 'ควรระบุวัตถุประสงค์ให้ชัดเจน'];
            $score -= 5;
        } else {
            $passed[] = ['field' => 'objectives', 'message' => 'มีการระบุวัตถุประสงค์แล้ว'];
        }

        if (empty($tor->scope_of_work)) {
            $issues[] = ['field' => 'scope_of_work', 'severity' => 'error', 'message' => 'ไม่ได้ระบุขอบเขตงาน', 'suggestion' => 'ต้องระบุขอบเขตงานให้ชัดเจน'];
            $score -= 20;
        } else {
            $passed[] = ['field' => 'scope_of_work', 'message' => 'มีการระบุขอบเขตงานแล้ว'];
        }

        if (empty($tor->specifications)) {
            $issues[] = ['field' => 'specifications', 'severity' => 'warning', 'message' => 'ไม่ได้ระบุข้อกำหนดทางเทคนิค', 'suggestion' => 'ควรระบุข้อกำหนดทางเทคนิคเพื่อความชัดเจน'];
            $score -= 5;
        }

        if (!$tor->budget_estimate || $tor->budget_estimate <= 0) {
            $issues[] = ['field' => 'budget_estimate', 'severity' => 'error', 'message' => 'ไม่ได้ระบุงบประมาณ', 'suggestion' => 'ต้องระบุวงเงินงบประมาณ'];
            $score -= 10;
        } else {
            $passed[] = ['field' => 'budget_estimate', 'message' => 'มีการระบุงบประมาณแล้ว: ' . number_format($tor->budget_estimate, 2) . ' ' . $tor->currency];
        }

        if (empty($tor->evaluation_criteria) || count($tor->evaluation_criteria) < 1) {
            $issues[] = ['field' => 'evaluation_criteria', 'severity' => 'warning', 'message' => 'ไม่ได้ระบุเกณฑ์ประเมิน', 'suggestion' => 'ควรระบุเกณฑ์ประเมินอย่างน้อย 1 เกณฑ์'];
            $score -= 5;
        } else {
            $totalWeight = collect($tor->evaluation_criteria)->sum('weight');
            if ($totalWeight != 100) {
                $issues[] = ['field' => 'evaluation_criteria', 'severity' => 'warning', 'message' => "น้ำหนักเกณฑ์รวม {$totalWeight}% (ควรเป็น 100%)", 'suggestion' => 'ปรับน้ำหนักเกณฑ์ให้รวมเป็น 100%'];
                $score -= 5;
            } else {
                $passed[] = ['field' => 'evaluation_criteria', 'message' => 'เกณฑ์ประเมินครบถ้วน น้ำหนักรวม 100%'];
            }
        }

        if ($tor->items()->count() < 1) {
            $issues[] = ['field' => 'items', 'severity' => 'warning', 'message' => 'ไม่ได้ระบุรายการ', 'suggestion' => 'ควรระบุรายการอย่างน้อย 1 รายการ'];
            $score -= 5;
        } else {
            $passed[] = ['field' => 'items', 'message' => 'มีรายการ ' . $tor->items()->count() . ' รายการ'];
        }

        if (empty($tor->qualification_requirements)) {
            $issues[] = ['field' => 'qualification_requirements', 'severity' => 'warning', 'message' => 'ไม่ได้ระบุคุณสมบัติผู้เสนอราคา', 'suggestion' => 'ควรระบุคุณสมบัติตาม ม.64 พ.ร.บ.จัดซื้อจัดจ้าง'];
            $score -= 5;
        }

        $score = max(0, min(100, $score));
        $grade = match (true) {
            $score >= 90 => 'A',
            $score >= 75 => 'B',
            $score >= 60 => 'C',
            $score >= 40 => 'D',
            default => 'F',
        };

        $issueCount = count($issues);
        $summary = "[Rule-Based] TOR นี้ได้คะแนน {$score}/100 (เกรด {$grade}) ";
        if ($issueCount > 0) {
            $errorCount = collect($issues)->where('severity', 'error')->count();
            $warningCount = collect($issues)->where('severity', 'warning')->count();
            $summary .= "พบปัญหา {$issueCount} จุด (ต้องแก้ไข {$errorCount}, ควรแก้ไข {$warningCount})";
        } else {
            $summary .= "TOR มีองค์ประกอบครบถ้วน";
        }

        return [
            'score' => $score,
            'grade' => $grade,
            'issues' => $issues,
            'passed' => $passed,
            'summary' => $summary,
        ];
    }
}
