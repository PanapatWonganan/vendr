<?php

namespace App\Filament\Pages;

use App\Models\Vendor;
use App\Models\VendorAssessment;
use App\Services\VendorRiskAssessmentService;
use App\Services\DbdApiService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Log;

class VendorRiskAssessment extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Vendor Risk Assessment';
    protected static ?string $navigationGroup = 'Reports & Analytics';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Vendor Risk Assessment (AI)';
    protected static ?string $slug = 'vendor-risk-assessment';
    protected static string $view = 'filament.pages.vendor-risk-assessment';

    public ?int $selectedVendorId = null;
    public ?string $manualTaxId = null;
    public ?VendorAssessment $currentAssessment = null;
    public array $assessmentHistory = [];
    public bool $isAssessing = false;
    public string $searchMode = 'vendor'; // 'vendor' or 'taxid'

    public function mount(): void
    {
        $this->loadAssessmentHistory();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('ตรวจสอบ Vendor')
                ->description('เลือก Vendor จากระบบ หรือกรอกเลขทะเบียนนิติบุคคลเพื่อตรวจสอบ')
                ->schema([
                    Select::make('searchMode')
                        ->label('วิธีค้นหา')
                        ->options([
                            'vendor' => 'เลือกจาก Vendor ในระบบ',
                            'taxid' => 'กรอกเลขทะเบียนนิติบุคคล (13 หลัก)',
                        ])
                        ->default('vendor')
                        ->live()
                        ->afterStateUpdated(fn () => $this->resetSearch()),

                    Select::make('selectedVendorId')
                        ->label('เลือก Vendor')
                        ->placeholder('พิมพ์ค้นหาชื่อหรือ Tax ID...')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search): array {
                            $companyId = session('company_id');
                            return Vendor::where('company_id', $companyId)
                                ->where(function ($q) use ($search) {
                                    $q->where('company_name', 'like', "%{$search}%")
                                      ->orWhere('tax_id', 'like', "%{$search}%");
                                })
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn (Vendor $v) => [
                                    $v->id => "{$v->company_name} ({$v->tax_id})",
                                ])
                                ->toArray();
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            $vendor = Vendor::find($value);
                            return $vendor ? "{$vendor->company_name} ({$vendor->tax_id})" : null;
                        })
                        ->visible(fn ($get) => ($get('searchMode') ?? 'vendor') === 'vendor')
                        ->live(),

                    TextInput::make('manualTaxId')
                        ->label('เลขทะเบียนนิติบุคคล')
                        ->placeholder('เช่น 0105500002383')
                        ->maxLength(13)
                        ->minLength(13)
                        ->visible(fn ($get) => ($get('searchMode') ?? 'vendor') === 'taxid')
                        ->live(),
                ])
                ->columns(1),
        ]);
    }

    public function resetSearch(): void
    {
        $this->selectedVendorId = null;
        $this->manualTaxId = null;
        $this->currentAssessment = null;
    }

    /**
     * Run the assessment
     */
    public function runAssessment(): void
    {
        $this->isAssessing = true;

        try {
            $service = app(VendorRiskAssessmentService::class);

            if ($this->searchMode === 'vendor' && $this->selectedVendorId) {
                $vendor = Vendor::findOrFail($this->selectedVendorId);
                $this->currentAssessment = $service->assess($vendor);

            } elseif ($this->searchMode === 'taxid' && $this->manualTaxId) {
                // Find vendor by tax ID, or create assessment without vendor
                $dbdService = app(DbdApiService::class);

                if (!$dbdService->isValidTaxId($this->manualTaxId)) {
                    Notification::make()
                        ->title('เลขทะเบียนนิติบุคคลไม่ถูกต้อง')
                        ->body('กรุณากรอกเลข 13 หลัก')
                        ->danger()
                        ->send();
                    $this->isAssessing = false;
                    return;
                }

                $companyId = session('company_id');
                $vendor = Vendor::where('tax_id', $this->manualTaxId)
                    ->where('company_id', $companyId)
                    ->first();

                if ($vendor) {
                    $this->currentAssessment = $service->assess($vendor);
                } else {
                    // Create a temporary assessment for an external tax ID
                    $this->currentAssessment = $this->assessExternalTaxId($this->manualTaxId);
                }
            } else {
                Notification::make()
                    ->title('กรุณาเลือก Vendor หรือกรอกเลขทะเบียนนิติบุคคล')
                    ->warning()
                    ->send();
                $this->isAssessing = false;
                return;
            }

            if ($this->currentAssessment && $this->currentAssessment->isCompleted()) {
                Notification::make()
                    ->title('ประเมินเสร็จสมบูรณ์')
                    ->body("ระดับความเสี่ยง: {$this->currentAssessment->risk_level_label}")
                    ->color($this->currentAssessment->risk_level_color)
                    ->success()
                    ->send();
            } elseif ($this->currentAssessment && $this->currentAssessment->isFailed()) {
                Notification::make()
                    ->title('การประเมินล้มเหลว')
                    ->body($this->currentAssessment->error_message ?? 'เกิดข้อผิดพลาด')
                    ->danger()
                    ->send();
            }

            $this->loadAssessmentHistory();

        } catch (\Exception $e) {
            Log::error("Vendor risk assessment page error: {$e->getMessage()}");
            Notification::make()
                ->title('เกิดข้อผิดพลาด')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->isAssessing = false;
    }

    /**
     * Assess an external tax ID not in our vendor list
     */
    protected function assessExternalTaxId(string $taxId): VendorAssessment
    {
        $dbdService = app(DbdApiService::class);
        $dbdData = $dbdService->getJuristicPerson($taxId);

        $companyId = session('company_id');

        $assessment = VendorAssessment::create([
            'vendor_id' => null,
            'company_id' => $companyId,
            'assessed_by' => auth()->id(),
            'tax_id' => $taxId,
            'assessment_status' => VendorAssessment::STATUS_PENDING,
        ]);

        if ($dbdData) {
            $assessment->update([
                'dbd_status' => $dbdData['status'],
                'dbd_entity_type' => $dbdData['entity_type'],
                'dbd_registered_capital' => $dbdData['registered_capital'],
                'dbd_name_th' => $dbdData['name_th'],
                'dbd_name_en' => $dbdData['name_en'],
                'dbd_address' => $dbdData['address'],
                'dbd_registration_date' => $dbdData['registration_date'],
                'dbd_objectives' => $dbdData['objectives'],
                'dbd_raw_data' => $dbdData['raw_data'],
                'dbd_fetched_at' => now(),
                'assessment_status' => VendorAssessment::STATUS_DBD_FETCHED,
            ]);
        } else {
            $assessment->update([
                'dbd_fetched_at' => now(),
                'assessment_status' => VendorAssessment::STATUS_DBD_FETCHED,
            ]);
        }

        // Run AI analysis on the external tax ID
        $this->runExternalAiAnalysis($assessment);

        return $assessment->fresh();
    }

    /**
     * Run AI analysis for external tax ID assessment
     */
    protected function runExternalAiAnalysis(VendorAssessment $assessment): void
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.model', 'gpt-4o-mini');

        // Build prompt from DBD data
        $prompt = $this->buildExternalPrompt($assessment);

        if ($apiKey) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(60)
                    ->withToken($apiKey)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $model,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $this->getAiSystemPrompt(),
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt,
                            ],
                        ],
                        'temperature' => 0.3,
                        'response_format' => ['type' => 'json_object'],
                    ]);

                if ($response->successful()) {
                    $result = $response->json();
                    $content = $result['choices'][0]['message']['content'] ?? '';
                    $parsed = json_decode($content, true);

                    if ($parsed) {
                        $assessment->update([
                            'risk_score' => $parsed['risk_score'] ?? 50,
                            'risk_level' => $parsed['risk_level'] ?? 'medium',
                            'ai_summary' => $parsed['summary'] ?? null,
                            'ai_risk_factors' => $parsed['risk_factors'] ?? [],
                            'ai_strengths' => $parsed['strengths'] ?? [],
                            'ai_recommendations' => $parsed['recommendations'] ?? [],
                            'dimension_scores' => $parsed['dimension_scores'] ?? null,
                            'ai_raw_response' => $content,
                            'ai_model_used' => $model,
                            'ai_analyzed_at' => now(),
                            'overall_risk_score' => $parsed['risk_score'] ?? 50,
                            'overall_risk_level' => $parsed['risk_level'] ?? 'medium',
                            'assessment_status' => VendorAssessment::STATUS_COMPLETED,
                        ]);
                        return;
                    }
                }

                Log::warning("OpenAI API error for external assessment", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } catch (\Exception $e) {
                Log::error("OpenAI API exception: {$e->getMessage()}");
            }
        }

        // Fallback: rule-based
        $this->runExternalRuleBasedAnalysis($assessment);
    }

    /**
     * Build AI prompt for external tax ID
     */
    protected function buildExternalPrompt(VendorAssessment $assessment): string
    {
        $parts = [];
        $parts[] = "## ข้อมูลบริษัทที่ต้องการประเมินความเสี่ยง";
        $parts[] = "- เลขทะเบียนนิติบุคคล: {$assessment->tax_id}";

        if ($assessment->hasDbdData() && $assessment->dbd_status) {
            $parts[] = "";
            $parts[] = "## ข้อมูลจากกรมพัฒนาธุรกิจการค้า (DBD)";
            $parts[] = "- สถานะนิติบุคคล: " . ($assessment->dbd_status ?? 'ไม่ทราบ');
            $parts[] = "- ประเภทนิติบุคคล: " . ($assessment->dbd_entity_type ?? 'ไม่ทราบ');
            $parts[] = "- ชื่อไทย: " . ($assessment->dbd_name_th ?? '-');
            $parts[] = "- ชื่ออังกฤษ: " . ($assessment->dbd_name_en ?? '-');
            $parts[] = "- ทุนจดทะเบียน: " . ($assessment->dbd_registered_capital ? number_format($assessment->dbd_registered_capital, 2) . " บาท" : 'ไม่ทราบ');
            $parts[] = "- วันจดทะเบียน: " . ($assessment->dbd_registration_date ? $assessment->dbd_registration_date->format('d/m/Y') . " (อายุ {$assessment->dbd_company_age})" : 'ไม่ทราบ');
            $parts[] = "- ที่อยู่: " . ($assessment->dbd_address ?? '-');

            if ($assessment->dbd_objectives) {
                $objTexts = collect($assessment->dbd_objectives)->pluck('text_th')->filter()->toArray();
                if (!empty($objTexts)) {
                    $parts[] = "- วัตถุประสงค์: " . implode(', ', $objTexts);
                }
            }
        } else {
            $parts[] = "";
            $parts[] = "## ข้อมูลจาก DBD: ไม่สามารถดึงข้อมูลได้ (พิจารณาเป็นปัจจัยเสี่ยงเพิ่มเติม)";
        }

        $parts[] = "";
        $parts[] = "## หมายเหตุ";
        $parts[] = "- บริษัทนี้ยังไม่ได้อยู่ในระบบ Vendor ขององค์กร (เป็นการตรวจสอบภายนอก)";
        $parts[] = "- ไม่มีข้อมูลประวัติการสั่งซื้อหรือการประเมินภายใน";

        return implode("\n", $parts);
    }

    /**
     * AI System prompt for vendor risk analysis
     */
    protected function getAiSystemPrompt(): string
    {
        return <<<'PROMPT'
คุณเป็น AI ผู้เชี่ยวชาญด้านการประเมินความเสี่ยงของ Vendor (Vendor Risk Assessment) สำหรับระบบจัดซื้อจัดจ้างในประเทศไทย

หน้าที่ของคุณคือวิเคราะห์ข้อมูลที่ได้รับและให้คะแนนความเสี่ยงพร้อมคำอธิบาย

กรุณาตอบเป็น JSON ตามรูปแบบนี้เท่านั้น:
{
    "risk_score": <int 0-100, 0=ปลอดภัยที่สุด, 100=เสี่ยงที่สุด>,
    "risk_level": "<low|medium|high|critical>",
    "summary": "<สรุปการประเมินภาพรวมเป็นภาษาไทย 2-3 ประโยค>",
    "risk_factors": ["<ปัจจัยเสี่ยงข้อ 1>", "<ปัจจัยเสี่ยงข้อ 2>", ...],
    "strengths": ["<จุดแข็งข้อ 1>", "<จุดแข็งข้อ 2>", ...],
    "recommendations": ["<คำแนะนำข้อ 1>", "<คำแนะนำข้อ 2>", ...],
    "dimension_scores": {
        "legal_status": <int 0-100, คะแนนด้านสถานะนิติบุคคล>,
        "company_age": <int 0-100, คะแนนด้านอายุบริษัท>,
        "capital": <int 0-100, คะแนนด้านทุนจดทะเบียน>,
        "objective_match": <int 0-100, คะแนนด้านความสอดคล้องของวัตถุประสงค์กิจการ>,
        "internal_performance": <int 0-100, คะแนนด้านผลการประเมินภายใน (50 ถ้าไม่มีข้อมูล)>,
        "po_history": <int 0-100, คะแนนด้านประวัติการสั่งซื้อ (30 ถ้าไม่มีข้อมูล)>
    }
}

dimension_scores คือคะแนนรายด้าน 6 มิติ (0=แย่ที่สุด, 100=ดีที่สุด) สำหรับแสดง Radar Chart:
- legal_status: สถานะนิติบุคคล (ดำเนินกิจการ=สูง, เลิก/ล้มละลาย=ต่ำ)
- company_age: อายุบริษัท (นานขึ้น=สูงขึ้น)
- capital: ทุนจดทะเบียน (สูงขึ้น=สูงขึ้น)
- objective_match: ความสอดคล้องของวัตถุประสงค์กิจการ
- internal_performance: ผลการประเมินภายใน (ถ้าเป็นบริษัทภายนอกให้ 50)
- po_history: ประวัติการสั่งซื้อ (ถ้าเป็นบริษัทภายนอกให้ 30)

เกณฑ์การประเมิน:
- สถานะนิติบุคคลจาก DBD (ดำเนินกิจการ/เลิก/ล้มละลาย)
- อายุบริษัท (จดทะเบียนนานแค่ไหน)
- ทุนจดทะเบียน
- ประเภทกิจการ/วัตถุประสงค์
- ถ้าไม่มีข้อมูลจาก DBD ถือเป็นปัจจัยเสี่ยงเพิ่มเติม

risk_level:
- low (0-25): ความเสี่ยงต่ำ เหมาะสมในการทำธุรกรรม
- medium (26-50): ความเสี่ยงปานกลาง ควรติดตาม
- high (51-75): ความเสี่ยงสูง ควรระมัดระวังเป็นพิเศษ
- critical (76-100): ความเสี่ยงวิกฤต ไม่แนะนำให้ทำธุรกรรม
PROMPT;
    }

    /**
     * Fallback rule-based analysis for external tax ID
     */
    protected function runExternalRuleBasedAnalysis(VendorAssessment $assessment): void
    {
        $riskScore = 50;
        $riskFactors = [];
        $strengths = [];

        if ($assessment->dbd_status) {
            $status = mb_strtolower($assessment->dbd_status);
            if (str_contains($status, 'เลิก') || str_contains($status, 'ล้มละลาย')) {
                $riskFactors[] = 'นิติบุคคลถูกเลิกกิจการหรือล้มละลาย';
                $riskScore += 40;
            } elseif (str_contains($status, 'ดำเนินกิจการ')) {
                $strengths[] = 'นิติบุคคลยังดำเนินกิจการอยู่ตามปกติ';
                $riskScore -= 10;
            }
        } else {
            $riskFactors[] = 'ไม่สามารถตรวจสอบสถานะนิติบุคคลได้';
            $riskScore += 15;
        }

        if ($assessment->dbd_registered_capital) {
            if ($assessment->dbd_registered_capital >= 10000000) {
                $strengths[] = 'ทุนจดทะเบียนสูง (' . number_format($assessment->dbd_registered_capital, 0) . ' บาท)';
                $riskScore -= 10;
            } elseif ($assessment->dbd_registered_capital < 1000000) {
                $riskFactors[] = 'ทุนจดทะเบียนค่อนข้างต่ำ (' . number_format($assessment->dbd_registered_capital, 0) . ' บาท)';
                $riskScore += 10;
            }
        }

        if ($assessment->dbd_registration_date) {
            $years = $assessment->dbd_registration_date->diffInYears(now());
            if ($years >= 10) {
                $strengths[] = "บริษัทจดทะเบียนมานาน {$years} ปี";
                $riskScore -= 10;
            } elseif ($years < 2) {
                $riskFactors[] = "บริษัทจดทะเบียนเพียง {$years} ปี ยังเป็นบริษัทใหม่";
                $riskScore += 10;
            }
        }

        $riskScore = max(0, min(100, $riskScore));
        $riskLevel = match(true) {
            $riskScore <= 25 => 'low',
            $riskScore <= 50 => 'medium',
            $riskScore <= 75 => 'high',
            default => 'critical',
        };

        // Build dimension scores for external assessment
        $dimensionScores = $this->buildExternalDimensionScores($assessment);

        $assessment->update([
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'ai_summary' => "ตรวจสอบ " . ($assessment->dbd_name_th ?? $assessment->tax_id) . ": ระดับความเสี่ยง" . match($riskLevel) { 'low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'high' => 'สูง', 'critical' => 'วิกฤต' },
            'ai_risk_factors' => $riskFactors,
            'ai_strengths' => $strengths,
            'ai_recommendations' => !empty($riskFactors)
                ? ['ควรตรวจสอบรายละเอียดเพิ่มเติมก่อนทำธุรกรรม', 'ขอเอกสารหนังสือรับรองบริษัทจาก Vendor โดยตรง']
                : ['ไม่พบข้อกังวลเบื้องต้น สามารถดำเนินการได้ตามปกติ'],
            'dimension_scores' => $dimensionScores,
            'ai_model_used' => 'rule-based',
            'ai_analyzed_at' => now(),
            'overall_risk_score' => $riskScore,
            'overall_risk_level' => $riskLevel,
            'assessment_status' => VendorAssessment::STATUS_COMPLETED,
        ]);
    }

    /**
     * Build dimension scores for external (no-vendor) rule-based assessment
     */
    protected function buildExternalDimensionScores(VendorAssessment $assessment): array
    {
        $legalStatus = 50;
        if ($assessment->dbd_status) {
            $status = mb_strtolower($assessment->dbd_status);
            if (str_contains($status, 'ดำเนินกิจการ')) {
                $legalStatus = 90;
            } elseif (str_contains($status, 'เลิก') || str_contains($status, 'ล้มละลาย')) {
                $legalStatus = 10;
            }
        }

        $companyAge = 50;
        if ($assessment->dbd_registration_date) {
            $years = $assessment->dbd_registration_date->diffInYears(now());
            $companyAge = min(100, max(10, $years * 8 + 20));
        }

        $capital = 50;
        if ($assessment->dbd_registered_capital) {
            if ($assessment->dbd_registered_capital >= 50000000) $capital = 95;
            elseif ($assessment->dbd_registered_capital >= 10000000) $capital = 80;
            elseif ($assessment->dbd_registered_capital >= 5000000) $capital = 70;
            elseif ($assessment->dbd_registered_capital >= 1000000) $capital = 55;
            else $capital = 30;
        }

        $objectiveMatch = 50;
        if ($assessment->dbd_objectives && !empty($assessment->dbd_objectives)) {
            $objectiveMatch = 65;
        }

        return [
            'legal_status' => $legalStatus,
            'company_age' => $companyAge,
            'capital' => $capital,
            'objective_match' => $objectiveMatch,
            'internal_performance' => 50, // No internal data for external
            'po_history' => 30, // No PO history for external
        ];
    }

    /**
     * View a specific assessment from history
     */
    public function viewAssessment(int $assessmentId): void
    {
        $this->currentAssessment = VendorAssessment::find($assessmentId);
    }

    /**
     * Load assessment history
     */
    protected function loadAssessmentHistory(): void
    {
        $companyId = session('company_id');
        $this->assessmentHistory = VendorAssessment::where('company_id', $companyId)
            ->with(['vendor', 'assessedBy'])
            ->latestFirst()
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
}
