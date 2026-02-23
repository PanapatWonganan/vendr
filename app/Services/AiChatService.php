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
                    'temperature' => 0.5,
                    'max_tokens' => 1500,
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
คุณคือ "VENDR Assistant" ผู้ช่วย AI สำหรับระบบจัดซื้อจัดจ้าง VENDR
ตอบเป็นภาษาไทยเสมอ สั้น กระชับ ได้ใจความ

ข้อมูลผู้ใช้ปัจจุบัน:
- ชื่อ: {$user->name}
- แผนก: {$user->department?->name}
- บริษัท: {$companyName}

ความสามารถของคุณ:
1. ดูสถานะ PR (ใบขอซื้อ) ของผู้ใช้
2. ดูสถานะ PO (ใบสั่งซื้อ)
3. ดูสถานะ GR (ใบตรวจรับ)
4. ดูข้อมูลงบประมาณแผนก
5. ค้นหาข้อมูลผู้ขาย (Vendor)
6. ค้นหาบทความช่วยเหลือจาก Knowledge Base

กฎ:
- ใช้ function calling เพื่อดึงข้อมูลจริงจากระบบเสมอ อย่าเดาข้อมูล
- ถ้าไม่มีข้อมูล ให้บอกตรงๆ ว่าไม่พบ
- ตอบสุภาพ ชัดเจน ไม่ยาวเกินไป
- ถ้าผู้ใช้ถามเรื่องนอกเหนือระบบจัดซื้อ ให้ตอบสั้นๆ แล้วแนะนำกลับมาเรื่องจัดซื้อ
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
                        'properties' => [],
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
