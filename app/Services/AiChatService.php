<?php

namespace App\Services;

use App\Models\PurchaseRequisition;
use App\Models\PurchaseOrder;
use App\Models\GoodsReceipt;
use App\Models\Vendor;
use App\Models\Department;
use App\Models\KnowledgeArticle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiChatService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', '');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
    }

    public function chat(string $userMessage, array $conversationHistory = []): array
    {
        $companyId = session('company_id');
        $user = Auth::user();

        if (!$this->apiKey) {
            return [
                'content' => 'ระบบ AI Chat ยังไม่ได้ตั้งค่า กรุณาติดต่อผู้ดูแลระบบ',
                'metadata' => ['error' => 'missing_api_key'],
            ];
        }

        $messages = $this->buildMessages($userMessage, $conversationHistory);
        $tools = $this->getToolDefinitions();

        try {
            $response = Http::timeout(60)
                ->withToken($this->apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'tools' => $tools,
                    'tool_choice' => 'auto',
                    'temperature' => 0.3,
                    'max_tokens' => 1000,
                ]);

            if (!$response->successful()) {
                Log::error('AiChatService API error', ['status' => $response->status(), 'body' => $response->body()]);
                return [
                    'content' => 'ขออภัย ระบบ AI ไม่สามารถตอบได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง',
                    'metadata' => ['error' => 'api_error', 'status' => $response->status()],
                ];
            }

            $result = $response->json();
            $choice = $result['choices'][0]['message'] ?? null;

            if (!$choice) {
                return ['content' => 'ไม่สามารถประมวลผลได้ กรุณาลองใหม่', 'metadata' => []];
            }

            // Handle function calling (tool_calls)
            if (isset($choice['tool_calls'])) {
                return $this->handleToolCalls($choice, $messages, $companyId, $user);
            }

            return [
                'content' => $choice['content'] ?? 'ไม่สามารถตอบได้',
                'metadata' => [
                    'tokens' => $result['usage'] ?? [],
                    'model' => $result['model'] ?? $this->model,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('AiChatService exception', ['message' => $e->getMessage()]);
            return [
                'content' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                'metadata' => ['error' => 'exception'],
            ];
        }
    }

    private function handleToolCalls(array $choice, array $messages, ?int $companyId, $user): array
    {
        $messages[] = $choice;

        foreach ($choice['tool_calls'] as $toolCall) {
            $functionName = $toolCall['function']['name'];
            $arguments = json_decode($toolCall['function']['arguments'], true) ?? [];

            $functionResult = $this->executeFunction($functionName, $arguments, $companyId, $user);

            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => $toolCall['id'],
                'content' => json_encode($functionResult, JSON_UNESCAPED_UNICODE),
            ];
        }

        // Second call to get final response with function results
        $response = Http::timeout(60)
            ->withToken($this->apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.5,
                'max_tokens' => 1500,
            ]);

        if ($response->successful()) {
            $result = $response->json();
            return [
                'content' => $result['choices'][0]['message']['content'] ?? 'ไม่สามารถตอบได้',
                'metadata' => [
                    'tokens' => $result['usage'] ?? [],
                    'model' => $result['model'] ?? $this->model,
                    'functions_called' => array_map(fn($tc) => $tc['function']['name'], $choice['tool_calls']),
                ],
            ];
        }

        return ['content' => 'เกิดข้อผิดพลาดในการประมวลผลข้อมูล', 'metadata' => ['error' => 'tool_call_failed']];
    }

    private function executeFunction(string $name, array $args, ?int $companyId, $user): array
    {
        return match ($name) {
            'get_my_pr_status' => $this->getMyPrStatus($args, $companyId, $user),
            'get_my_po_status' => $this->getMyPoStatus($args, $companyId, $user),
            'get_gr_status' => $this->getGrStatus($args, $companyId),
            'get_budget_summary' => $this->getBudgetSummary($companyId, $user),
            'get_vendor_info' => $this->getVendorInfo($args, $companyId),
            'search_knowledge_base' => $this->searchKnowledgeBase($args),
            default => ['error' => 'Unknown function: ' . $name],
        };
    }

    // ===== Function Implementations =====

    private function getMyPrStatus(array $args, ?int $companyId, $user): array
    {
        $query = PurchaseRequisition::where('company_id', $companyId);

        if (!empty($args['pr_number'])) {
            $query->where('pr_number', 'like', '%' . $args['pr_number'] . '%');
        } else {
            $query->where('requester_id', $user->id);
        }

        $prs = $query->latest()->limit(5)->get(['id', 'pr_number', 'title', 'status', 'total_amount', 'created_at']);

        if ($prs->isEmpty()) {
            return ['message' => 'ไม่พบ PR ที่ค้นหา'];
        }

        return [
            'count' => $prs->count(),
            'purchase_requisitions' => $prs->map(fn($pr) => [
                'pr_number' => $pr->pr_number,
                'title' => $pr->title,
                'status' => $pr->status,
                'total_amount' => number_format($pr->total_amount, 2),
                'created_at' => $pr->created_at->format('d/m/Y'),
            ])->toArray(),
        ];
    }

    private function getMyPoStatus(array $args, ?int $companyId, $user): array
    {
        $query = PurchaseOrder::where('company_id', $companyId)->with('vendor');

        if (!empty($args['po_number'])) {
            $query->where('po_number', 'like', '%' . $args['po_number'] . '%');
        }

        $pos = $query->latest()->limit(5)->get(['id', 'po_number', 'po_title', 'vendor_id', 'status', 'total_amount', 'expected_delivery_date', 'created_at']);

        if ($pos->isEmpty()) {
            return ['message' => 'ไม่พบ PO ที่ค้นหา'];
        }

        return [
            'count' => $pos->count(),
            'purchase_orders' => $pos->map(fn($po) => [
                'po_number' => $po->po_number,
                'title' => $po->po_title,
                'vendor' => optional($po->vendor)->company_name,
                'status' => $po->status,
                'total_amount' => number_format($po->total_amount, 2),
                'expected_delivery' => $po->expected_delivery_date?->format('d/m/Y'),
                'created_at' => $po->created_at->format('d/m/Y'),
            ])->toArray(),
        ];
    }

    private function getGrStatus(array $args, ?int $companyId): array
    {
        $query = GoodsReceipt::where('company_id', $companyId)->with(['vendor', 'purchaseOrder']);

        if (!empty($args['gr_number'])) {
            $query->where('gr_number', 'like', '%' . $args['gr_number'] . '%');
        }

        $grs = $query->latest()->limit(5)->get(['id', 'gr_number', 'vendor_id', 'purchase_order_id', 'status', 'inspection_status', 'receipt_date']);

        if ($grs->isEmpty()) {
            return ['message' => 'ไม่พบ GR ที่ค้นหา'];
        }

        return [
            'count' => $grs->count(),
            'goods_receipts' => $grs->map(fn($gr) => [
                'gr_number' => $gr->gr_number,
                'vendor' => optional($gr->vendor)->company_name,
                'po_number' => optional($gr->purchaseOrder)->po_number,
                'status' => $gr->status,
                'inspection_status' => $gr->inspection_status,
                'receipt_date' => $gr->receipt_date?->format('d/m/Y'),
            ])->toArray(),
        ];
    }

    private function getBudgetSummary(?int $companyId, $user): array
    {
        $department = $user->department;

        if (!$department) {
            return ['message' => 'ไม่พบข้อมูลแผนก'];
        }

        $monthlyBudget = $department->monthly_budget ?? 0;

        $spentThisMonth = PurchaseRequisition::where('company_id', $companyId)
            ->where('department_id', $department->id)
            ->whereIn('status', ['approved', 'pending_approval'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');

        $pendingCount = PurchaseRequisition::where('company_id', $companyId)
            ->where('department_id', $department->id)
            ->where('status', 'pending_approval')
            ->count();

        return [
            'department' => $department->name,
            'monthly_budget' => number_format($monthlyBudget, 2),
            'spent_this_month' => number_format($spentThisMonth, 2),
            'remaining' => number_format($monthlyBudget - $spentThisMonth, 2),
            'usage_percent' => $monthlyBudget > 0 ? round(($spentThisMonth / $monthlyBudget) * 100, 1) : 0,
            'pending_pr_count' => $pendingCount,
        ];
    }

    private function getVendorInfo(array $args, ?int $companyId): array
    {
        $query = Vendor::where('company_id', $companyId);

        if (!empty($args['vendor_name'])) {
            $query->where('company_name', 'like', '%' . $args['vendor_name'] . '%');
        }

        $vendors = $query->limit(3)->get(['id', 'company_name', 'tax_id', 'contact_name', 'contact_phone', 'contact_email', 'status']);

        if ($vendors->isEmpty()) {
            return ['message' => 'ไม่พบข้อมูลผู้ขายที่ค้นหา'];
        }

        return [
            'count' => $vendors->count(),
            'vendors' => $vendors->map(fn($v) => [
                'name' => $v->company_name,
                'tax_id' => $v->tax_id,
                'contact' => $v->contact_name,
                'phone' => $v->contact_phone,
                'email' => $v->contact_email,
                'status' => $v->status,
            ])->toArray(),
        ];
    }

    private function searchKnowledgeBase(array $args): array
    {
        $keyword = $args['keyword'] ?? '';

        $articles = KnowledgeArticle::where('is_published', true)
            ->where(function ($q) use ($keyword) {
                $q->where('title', 'like', '%' . $keyword . '%')
                  ->orWhere('content', 'like', '%' . $keyword . '%');
            })
            ->limit(3)
            ->get(['id', 'title', 'content', 'category']);

        if ($articles->isEmpty()) {
            return ['message' => 'ไม่พบบทความที่เกี่ยวข้อง'];
        }

        return [
            'count' => $articles->count(),
            'articles' => $articles->map(fn($a) => [
                'title' => $a->title,
                'content' => mb_substr(strip_tags($a->content), 0, 500),
                'category' => $a->category,
            ])->toArray(),
        ];
    }

    // ===== Prompt & Tool Definitions =====

    private function buildMessages(string $userMessage, array $conversationHistory): array
    {
        $messages = [
            ['role' => 'system', 'content' => $this->getSystemPrompt()],
        ];

        // Add last 10 messages for context
        foreach (array_slice($conversationHistory, -10) as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return $messages;
    }

    private function getSystemPrompt(): string
    {
        $user = Auth::user();
        $companyName = session('company_name', 'บริษัท');

        return <<<PROMPT
คุณคือ "VENDR Assistant" ผู้ช่วย AI ประจำระบบจัดซื้อจัดจ้าง VENDR
ตอบเป็นภาษาไทยเสมอ สั้น กระชับ ได้ใจความ ใช้ภาษาสุภาพ

ข้อมูลผู้ใช้ปัจจุบัน:
- ชื่อ: {$user->name}
- แผนก: {$user->department?->name}
- บริษัท: {$companyName}

═══════════════════════════════════════
คู่มือระบบ VENDR (ใช้ตอบคำถามผู้ใช้)
═══════════════════════════════════════

【1. ภาพรวมระบบ】
VENDR คือระบบจัดซื้อจัดจ้างออนไลน์ รองรับหลายบริษัท (Multi-Company)
เมนูหลัก: Procurement Management, Milestone Management, Contract Management, Master Data, Reports & Analytics, System Management, User Management

【2. ขั้นตอนจัดซื้อ (Procurement Flow)】
PR (ใบขอซื้อ) → Value Analysis (วิเคราะห์ราคา) → PO (ใบสั่งซื้อ) → GR/MR (ตรวจรับ) → Payment (จ่ายเงิน)

【3. ใบขอซื้อ (Purchase Requisition - PR)】
วิธีสร้าง PR:
1. ไปที่เมนู Procurement Management > Purchase Requisitions > กดปุ่ม "สร้างใหม่"
2. กรอกข้อมูล: ชื่อเรื่อง, หมวดหมู่, ประเภทงาน (ซื้อ/จ้าง/เช่า), วิธีจัดซื้อ, วัตถุประสงค์
3. เลือกแผนก, ผู้ขอ, ลำดับความสำคัญ, วันที่ต้องการ
4. กรอกข้อมูลงบประมาณ: รหัสงบ, รหัสโครงการ, สกุลเงิน
5. เพิ่มรายการสินค้า/บริการ: รายละเอียด, จำนวน, หน่วย, ราคาต่อหน่วย
6. กำหนดคณะกรรมการ: จัดซื้อ, ตรวจรับ, ผู้อนุมัติ PR
7. กด "สร้าง" → ระบบสร้างเลข PR อัตโนมัติ (PR{ปี}{เดือน}{ลำดับ})

สถานะ PR: Draft → Submitted → Pending Approval → Approved / Rejected / Cancelled

จัดซื้อด่วน (Direct Purchase):
- ไม่เกิน 10,000 บาท: เมนู "จัดหาฯ ไม่เกิน 1 หมื่นบาท" — ขั้นตอนอนุมัติน้อยกว่า
- ไม่เกิน 100,000 บาท: เมนู "จัดหาฯ ไม่เกิน 1 แสนบาท" — ขั้นตอนมาตรฐาน
- ระบบตรวจสอบวงเงินอัตโนมัติ ห้ามเกินที่กำหนด

หมวดหมู่สินค้า: สินค้าพรีเมี่ยม, บริการโฆษณา, เช่าพื้นที่, บริการทั่วไป, งานก่อสร้าง, ทรัพย์สิน, เทคโนโลยี/ซอฟต์แวร์, ยา, เครื่องมือแพทย์, อาหารทางการแพทย์, อาหารเสริม

ประเภทงาน: ซื้อ, จ้าง, เช่า

วิธีจัดซื้อ: ตกลงราคา, ประมูลเชิญ, ประมูลเชิญชวนทั่วไป, พิเศษ ข้อ 1, พิเศษ ข้อ 2, คัดเลือก

【4. คำขอของฉัน & รออนุมัติ】
- "คำขอของฉัน": ดู PR ทั้งหมดที่ตัวเองสร้างหรือเป็นผู้ขอ
- "รออนุมัติ": ดู PR ที่รอการอนุมัติจากตัวเอง สามารถกด อนุมัติ/ปฏิเสธ ได้เลย
- มี badge แสดงจำนวน PR ที่รออนุมัติ

【5. Value Analysis (VA) - วิเคราะห์ราคา/เลือกผู้ขาย】
วิธีสร้าง VA:
1. ไปที่ Vendor Approve > กด "สร้างใหม่"
2. เลือก PR ที่สถานะ Approved
3. กรอกข้อมูล: วิธีจัดซื้อ, แหล่งจัดซื้อ, ราคาที่ตกลง (Agreed Amount)
4. ระบบคำนวณส่วนต่าง (Savings) อัตโนมัติจากงบ PR vs ราคาตกลง
5. ใส่ข้อเสนอแนะ, สรุปผล
6. VA ต้องอนุมัติก่อนถึงจะสร้าง PO ได้
สถานะ VA: Draft → In Progress → Completed → Approved / Rejected
เลข VA: VA-{วันที่}-{ลำดับ}

【6. ใบสั่งซื้อ (Purchase Order - PO)】
เงื่อนไข: สร้าง PO ได้เฉพาะ PR ที่ Approved + VA ที่ Approved แล้วเท่านั้น
วิธีสร้าง PO:
1. ไปที่ Purchase Orders > กด "สร้างใหม่"
2. เลือก PR (ระบบกรองเฉพาะที่ VA อนุมัติแล้ว)
3. ระบบดึงข้อมูลอัตโนมัติ: รายการสินค้า, ราคาจาก VA, ผู้ขาย
4. กรอกข้อมูลเพิ่ม: วันสั่งซื้อ, วันส่งมอบ, ที่อยู่จัดส่ง
5. ระบบคำนวณ: Subtotal + VAT 7% = Total อัตโนมัติ
6. กำหนด Payment Milestones (งวดการจ่ายเงิน): เช่น มัดจำ 30%, งวดกลาง 40%, งวดสุดท้าย 30%
7. แนบเอกสาร (PDF, รูปภาพ)
สถานะ PO: Draft → Pending Approval → Approved → Sent to Supplier → Acknowledged → Partially Received → Fully Received → Closed / Rejected / Cancelled
เลข PO: PO-{วันที่}-{ลำดับ}

【7. งวดการจ่ายเงิน (Payment Milestones)】
- แต่ละ PO มีได้หลายงวด (เช่น มัดจำ/งวดกลาง/งวดสุดท้าย)
- แต่ละงวด: เปอร์เซ็นต์, จำนวนเงิน (คำนวณอัตโนมัติ), วันครบกำหนด, เงื่อนไข
- สถานะ: Pending → Due → Paid / Overdue / Cancelled
- ระบบแจ้งเตือนอัตโนมัติเมื่อเหลือ ≤15 วันก่อนครบกำหนด
- บันทึกข้อมูลจ่าย: วันที่จ่าย, จำนวน, เลขอ้างอิง, หมายเหตุ

【8. ตรวจรับสินค้า (Goods Receipt - GR/MR)】
วิธีสร้าง GR:
1. ไปที่ Goods Receipts > กด "สร้างใหม่"
2. เลือก PO ที่สถานะ Approved
3. ระบบดึงข้อมูลผู้ขายจาก PO อัตโนมัติ
4. กำหนดคณะกรรมการตรวจรับ, วันที่รับ, เลขงวดส่งมอบ
5. คณะกรรมการตรวจสอบคุณภาพ
6. แนบเอกสาร (PDF, รูปภาพ, Word, Excel)
สถานะ GR: Draft → Completed / Returned / Partially Returned / Cancelled
สถานะตรวจสอบ: Pending → Passed / Failed / Partial
เลข GR: GR{ปี}{เดือน}{ลำดับ}

【9. สัญญา (Contract Management)】
- บันทึกข้อมูลสัญญา: เลขสัญญา, ชื่อ, ผู้ขาย, มูลค่า, วันเริ่ม-สิ้นสุด
- ประเภทสัญญา, ลำดับความสำคัญ, แผนก, รหัสงบ/โครงการ
- ระบบตรวจสอบและอนุมัติสัญญา

【10. ผู้ขาย (Vendor Management)】
- ลงทะเบียนผู้ขาย: ชื่อบริษัท, เลขภาษี, ที่อยู่, หมวดงาน, ประสบการณ์
- สถานะผู้ขาย: Pending → Approved / Rejected / Suspended
- ประเมินผู้ขาย (Vendor Evaluation): ให้คะแนนตามเกณฑ์ จัดเกรด A/B/C/D
- ประเมินความเสี่ยง (Risk Assessment): AI วิเคราะห์ความเสี่ยงผู้ขาย → Low/Medium/High/Critical
- คะแนนรวม (Vendor Score): คำนวณจากผลประเมินทั้งหมด แสดงเกรดปัจจุบัน

【11. รายงานและวิเคราะห์ (Reports & Analytics)】
- Dashboard: สรุปตัวเลขจัดซื้อ, SLA Performance, Vendor Grade, Savings
- SLA Report: ติดตาม Service Level Agreement
- Vendor Performance Report: รายงานผลงานผู้ขาย
- Procurement Reports: รายงานจัดซื้อเชิงลึก
- Anomaly Detection: ตรวจจับธุรกรรมที่ผิดปกติ

【12. ข้อมูลหลัก (Master Data)】
- แผนก (Department): ชื่อ, รหัส, ผู้จัดการ, แผนกแม่ (รองรับลำดับชั้น)
- บทความ Knowledge Base: คู่มือใช้งาน, FAQ
- ไฟล์แนบ (Procurement Attachments): จัดการไฟล์กลาง

【13. จัดการระบบ】
- บริษัท (Company): รองรับหลายบริษัท แต่ละบริษัทมี database แยก, โลโก้, การตั้งค่าเฉพาะ
- ผู้ใช้ (User): ชื่อ, อีเมล, แผนก, บทบาท, ตั้งค่าการแจ้งเตือนอีเมล

【14. บทบาทผู้ใช้ (User Roles)】
- admin: ผู้ดูแลระบบ (เข้าถึงทุกอย่าง)
- procurement_manager: ผู้จัดการจัดซื้อ
- approver: ผู้อนุมัติ
- department_head: หัวหน้าแผนก
- procurement_committee: คณะกรรมการจัดซื้อ
- inspection_committee: คณะกรรมการตรวจรับ
- other_stakeholder: ผู้มีส่วนได้ส่วนเสียอื่นๆ

【15. สกุลเงิน】
รองรับ 40+ สกุลเงิน: THB (บาท), USD, EUR, GBP, JPY, CNY, SGD และอื่นๆ

【16. ฟีเจอร์เด่น】
- เลขเอกสารอัตโนมัติ (PR/PO/VA/GR)
- คำนวณยอดเงิน/ภาษี/Savings อัตโนมัติ
- แจ้งเตือนอีเมลตามการตั้งค่า
- ปฏิทินส่งมอบ (Calendar)
- Badge แสดงจำนวนงานค้าง
- อัปโหลดหลายไฟล์ (PDF, รูปภาพ, Word, Excel)
- ค้นหาและกรองข้อมูลขั้นสูง
- Export ข้อมูล

═══════════════════════════════════════
กฎสำคัญ
═══════════════════════════════════════
1. ตอบเฉพาะเรื่องที่เกี่ยวกับระบบ VENDR และงานจัดซื้อจัดจ้างเท่านั้น
2. ถ้าผู้ใช้ถามเรื่องอื่นที่ไม่เกี่ยวกับระบบ ให้ปฏิเสธสุภาพ เช่น "ขออภัยครับ ผมตอบได้เฉพาะเรื่องเกี่ยวกับระบบจัดซื้อ VENDR ครับ"
3. ใช้ function calling ดึงข้อมูลจริงเมื่อผู้ใช้ถามเกี่ยวกับข้อมูลเฉพาะ (PR/PO/GR/งบ/ผู้ขาย)
4. ถ้าเป็นคำถามทั่วไปเกี่ยวกับวิธีใช้งาน ให้ตอบจากความรู้ข้างต้นได้เลย ไม่ต้องเรียก function
5. อย่าเดาข้อมูลตัวเลข ถ้าไม่มีให้บอกตรงๆ
6. ตอบสั้นกระชับ ไม่เกิน 3-4 ประโยค ยกเว้นอธิบายขั้นตอนให้เป็นข้อๆ
PROMPT;
    }

    private function getToolDefinitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_pr_status',
                    'description' => 'ดูสถานะใบขอซื้อ (Purchase Requisition) ของผู้ใช้ หรือค้นหาด้วยเลข PR',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'pr_number' => [
                                'type' => 'string',
                                'description' => 'เลข PR ที่ต้องการค้นหา (ถ้ามี) เช่น PR-2024-0001',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_my_po_status',
                    'description' => 'ดูสถานะใบสั่งซื้อ (Purchase Order) ค้นหาด้วยเลข PO ได้',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'po_number' => [
                                'type' => 'string',
                                'description' => 'เลข PO ที่ต้องการค้นหา (ถ้ามี)',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_gr_status',
                    'description' => 'ดูสถานะใบตรวจรับสินค้า (Goods Receipt) ค้นหาด้วยเลข GR ได้',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'gr_number' => [
                                'type' => 'string',
                                'description' => 'เลข GR ที่ต้องการค้นหา (ถ้ามี)',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_budget_summary',
                    'description' => 'ดูสรุปงบประมาณของแผนกผู้ใช้ในเดือนนี้ รวมถึงยอดที่ใช้ไปและยอดคงเหลือ',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object) [],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_vendor_info',
                    'description' => 'ค้นหาข้อมูลผู้ขาย (Vendor) ด้วยชื่อบริษัท',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'vendor_name' => [
                                'type' => 'string',
                                'description' => 'ชื่อบริษัทผู้ขายที่ต้องการค้นหา',
                            ],
                        ],
                        'required' => ['vendor_name'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_knowledge_base',
                    'description' => 'ค้นหาบทความช่วยเหลือจาก Knowledge Base เกี่ยวกับวิธีใช้ระบบ',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'keyword' => [
                                'type' => 'string',
                                'description' => 'คำค้นหา เช่น "สร้าง PR", "อนุมัติ", "ตรวจรับ"',
                            ],
                        ],
                        'required' => ['keyword'],
                    ],
                ],
            ],
        ];
    }
}
