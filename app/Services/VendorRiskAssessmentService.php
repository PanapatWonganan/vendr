<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\VendorAssessment;
use App\Models\VendorScore;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class VendorRiskAssessmentService
{
    protected DbdApiService $dbdApi;

    public function __construct(DbdApiService $dbdApi)
    {
        $this->dbdApi = $dbdApi;
    }

    /**
     * Run a full assessment for a vendor
     */
    public function assess(Vendor $vendor, ?int $companyId = null): VendorAssessment
    {
        $companyId = $companyId ?? session('company_id') ?? $vendor->company_id;

        // Create assessment record
        $assessment = VendorAssessment::create([
            'vendor_id' => $vendor->id,
            'company_id' => $companyId,
            'assessed_by' => Auth::id(),
            'tax_id' => $vendor->tax_id,
            'assessment_status' => VendorAssessment::STATUS_PENDING,
        ]);

        try {
            // Step 1: Fetch DBD data
            $this->fetchDbdData($assessment);

            // Step 2: Run AI analysis
            $this->runAiAnalysis($assessment, $vendor);

            // Step 3: Calculate overall risk
            $this->calculateOverallRisk($assessment);

            $assessment->update([
                'assessment_status' => VendorAssessment::STATUS_COMPLETED,
            ]);

        } catch (\Exception $e) {
            Log::error("Vendor assessment failed", [
                'vendor_id' => $vendor->id,
                'error' => $e->getMessage(),
            ]);

            $assessment->update([
                'assessment_status' => VendorAssessment::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
        }

        return $assessment->fresh();
    }

    /**
     * Step 1: Fetch data from DBD API
     */
    protected function fetchDbdData(VendorAssessment $assessment): void
    {
        $dbdData = $this->dbdApi->getJuristicPerson($assessment->tax_id);

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
            // DBD fetch failed, but we can still continue with AI analysis using internal data
            Log::warning("DBD data not available for tax_id: {$assessment->tax_id}");
            $assessment->update([
                'dbd_fetched_at' => now(),
                'assessment_status' => VendorAssessment::STATUS_DBD_FETCHED,
            ]);
        }
    }

    /**
     * Step 2: Run AI analysis using OpenAI
     */
    protected function runAiAnalysis(VendorAssessment $assessment, Vendor $vendor): void
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.model', 'gpt-4o-mini');

        if (!$apiKey) {
            Log::warning("OpenAI API key not configured, skipping AI analysis");
            $this->runRuleBasedAnalysis($assessment, $vendor);
            return;
        }

        $prompt = $this->buildAnalysisPrompt($assessment, $vendor);

        try {
            $response = Http::timeout(60)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->getSystemPrompt(),
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
                        'risk_score' => $parsed['risk_score'] ?? null,
                        'risk_level' => $parsed['risk_level'] ?? null,
                        'ai_summary' => $parsed['summary'] ?? null,
                        'ai_risk_factors' => $parsed['risk_factors'] ?? [],
                        'ai_strengths' => $parsed['strengths'] ?? [],
                        'ai_recommendations' => $parsed['recommendations'] ?? [],
                        'dimension_scores' => $parsed['dimension_scores'] ?? null,
                        'ai_raw_response' => $content,
                        'ai_model_used' => $model,
                        'ai_analyzed_at' => now(),
                        'assessment_status' => VendorAssessment::STATUS_ANALYZED,
                    ]);

                    return;
                }
            }

            Log::warning("OpenAI API returned unexpected response", [
                'status' => $response->status(),
            ]);

            // Fallback to rule-based analysis
            $this->runRuleBasedAnalysis($assessment, $vendor);

        } catch (\Exception $e) {
            Log::error("OpenAI API error: {$e->getMessage()}");
            $this->runRuleBasedAnalysis($assessment, $vendor);
        }
    }

    /**
     * Fallback: Rule-based analysis when AI is unavailable
     */
    protected function runRuleBasedAnalysis(VendorAssessment $assessment, Vendor $vendor): void
    {
        $riskFactors = [];
        $strengths = [];
        $riskScore = 50; // Start neutral

        // Check DBD status
        if ($assessment->dbd_status) {
            $status = mb_strtolower($assessment->dbd_status);
            if (str_contains($status, 'เลิก') || str_contains($status, 'ล้มละลาย')) {
                $riskFactors[] = 'นิติบุคคลถูกเลิกกิจการหรือล้มละลาย';
                $riskScore += 40;
            } elseif (str_contains($status, 'ร้าง') || str_contains($status, 'ถูกขีดชื่อ')) {
                $riskFactors[] = 'นิติบุคคลมีสถานะไม่ปกติ';
                $riskScore += 25;
            } elseif (str_contains($status, 'ดำเนินกิจการ')) {
                $strengths[] = 'นิติบุคคลยังดำเนินกิจการอยู่ตามปกติ';
                $riskScore -= 10;
            }
        } else {
            $riskFactors[] = 'ไม่สามารถตรวจสอบสถานะนิติบุคคลได้จาก DBD';
            $riskScore += 15;
        }

        // Check company age
        if ($assessment->dbd_registration_date) {
            $years = $assessment->dbd_registration_date->diffInYears(now());
            if ($years >= 10) {
                $strengths[] = "บริษัทจดทะเบียนมานาน {$years} ปี มีความมั่นคง";
                $riskScore -= 10;
            } elseif ($years >= 5) {
                $strengths[] = "บริษัทดำเนินกิจการมา {$years} ปี";
                $riskScore -= 5;
            } elseif ($years < 2) {
                $riskFactors[] = "บริษัทจดทะเบียนเพียง {$years} ปี ยังเป็นบริษัทใหม่";
                $riskScore += 10;
            }
        }

        // Check registered capital
        if ($assessment->dbd_registered_capital) {
            if ($assessment->dbd_registered_capital >= 10000000) {
                $strengths[] = 'ทุนจดทะเบียนสูง (' . number_format($assessment->dbd_registered_capital, 0) . ' บาท)';
                $riskScore -= 10;
            } elseif ($assessment->dbd_registered_capital < 1000000) {
                $riskFactors[] = 'ทุนจดทะเบียนค่อนข้างต่ำ (' . number_format($assessment->dbd_registered_capital, 0) . ' บาท)';
                $riskScore += 10;
            }
        }

        // Check internal vendor performance
        $companyId = $assessment->company_id;
        $latestScore = VendorScore::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->whereNull('quarter')
            ->whereNull('month')
            ->latest('year')
            ->first();

        if ($latestScore) {
            $grade = $latestScore->weighted_grade ?? $latestScore->grade;
            if (in_array($grade, ['A', 'B'])) {
                $strengths[] = "ผลการประเมินภายในอยู่ในเกรด {$grade} (คะแนน {$latestScore->formatted_score})";
                $riskScore -= 15;
            } elseif (in_array($grade, ['C', 'D'])) {
                $riskFactors[] = "ผลการประเมินภายในอยู่ในเกรด {$grade} ต้องปรับปรุง";
                $riskScore += 15;
            }
        }

        // Check PO history
        $poCount = PurchaseOrder::where('vendor_id', $vendor->id)->count();
        if ($poCount >= 10) {
            $strengths[] = "มีประวัติการสั่งซื้อ {$poCount} ครั้ง แสดงถึงความน่าเชื่อถือ";
            $riskScore -= 5;
        } elseif ($poCount === 0) {
            $riskFactors[] = 'ยังไม่มีประวัติการสั่งซื้อในระบบ';
            $riskScore += 5;
        }

        // Clamp risk score
        $riskScore = max(0, min(100, $riskScore));
        $riskLevel = $this->scoreToRiskLevel($riskScore);

        $summary = $this->buildRuleBasedSummary($vendor, $riskScore, $riskLevel, $riskFactors, $strengths);

        // Build dimension scores for radar chart
        $dimensionScores = $this->buildRuleBasedDimensionScores($assessment, $vendor);

        $assessment->update([
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'ai_summary' => $summary,
            'ai_risk_factors' => $riskFactors,
            'ai_strengths' => $strengths,
            'ai_recommendations' => $this->generateRecommendations($riskFactors),
            'dimension_scores' => $dimensionScores,
            'ai_model_used' => 'rule-based',
            'ai_analyzed_at' => now(),
            'assessment_status' => VendorAssessment::STATUS_ANALYZED,
        ]);
    }

    /**
     * Build dimension scores for rule-based analysis
     */
    protected function buildRuleBasedDimensionScores(VendorAssessment $assessment, Vendor $vendor): array
    {
        // Legal status
        $legalStatus = 50;
        if ($assessment->dbd_status) {
            $status = mb_strtolower($assessment->dbd_status);
            if (str_contains($status, 'ดำเนินกิจการ')) {
                $legalStatus = 90;
            } elseif (str_contains($status, 'เลิก') || str_contains($status, 'ล้มละลาย')) {
                $legalStatus = 10;
            } elseif (str_contains($status, 'ร้าง') || str_contains($status, 'ถูกขีดชื่อ')) {
                $legalStatus = 30;
            }
        }

        // Company age
        $companyAge = 50;
        if ($assessment->dbd_registration_date) {
            $years = $assessment->dbd_registration_date->diffInYears(now());
            $companyAge = min(100, max(10, $years * 8 + 20));
        }

        // Capital
        $capital = 50;
        if ($assessment->dbd_registered_capital) {
            if ($assessment->dbd_registered_capital >= 50000000) {
                $capital = 95;
            } elseif ($assessment->dbd_registered_capital >= 10000000) {
                $capital = 80;
            } elseif ($assessment->dbd_registered_capital >= 5000000) {
                $capital = 70;
            } elseif ($assessment->dbd_registered_capital >= 1000000) {
                $capital = 55;
            } else {
                $capital = 30;
            }
        }

        // Objective match
        $objectiveMatch = 50;
        if ($assessment->dbd_objectives && !empty($assessment->dbd_objectives)) {
            $objectiveMatch = 65;
        }

        // Internal performance
        $internalPerf = 50;
        $companyId = $assessment->company_id;
        $latestScore = VendorScore::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->whereNull('quarter')
            ->whereNull('month')
            ->latest('year')
            ->first();

        if ($latestScore) {
            $grade = $latestScore->weighted_grade ?? $latestScore->grade;
            $internalPerf = match($grade) {
                'A' => 90,
                'B' => 75,
                'C' => 45,
                'D' => 25,
                default => 50,
            };
        }

        // PO history
        $poHistory = 50;
        $poCount = PurchaseOrder::where('vendor_id', $vendor->id)->count();
        if ($poCount >= 20) {
            $poHistory = 90;
        } elseif ($poCount >= 10) {
            $poHistory = 75;
        } elseif ($poCount >= 5) {
            $poHistory = 60;
        } elseif ($poCount >= 1) {
            $poHistory = 45;
        } else {
            $poHistory = 25;
        }

        return [
            'legal_status' => $legalStatus,
            'company_age' => $companyAge,
            'capital' => $capital,
            'objective_match' => $objectiveMatch,
            'internal_performance' => $internalPerf,
            'po_history' => $poHistory,
        ];
    }

    /**
     * Step 3: Calculate overall risk from all signals
     */
    protected function calculateOverallRisk(VendorAssessment $assessment): void
    {
        $riskScore = $assessment->risk_score ?? 50;
        $riskLevel = $this->scoreToRiskLevel($riskScore);

        $assessment->update([
            'overall_risk_score' => $riskScore,
            'overall_risk_level' => $riskLevel,
        ]);
    }

    /**
     * Convert numeric risk score to risk level
     */
    protected function scoreToRiskLevel(int $score): string
    {
        if ($score <= 25) return VendorAssessment::RISK_LOW;
        if ($score <= 50) return VendorAssessment::RISK_MEDIUM;
        if ($score <= 75) return VendorAssessment::RISK_HIGH;
        return VendorAssessment::RISK_CRITICAL;
    }

    /**
     * Get the system prompt for AI analysis
     */
    protected function getSystemPrompt(): string
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
        "internal_performance": <int 0-100, คะแนนด้านผลการประเมินภายใน>,
        "po_history": <int 0-100, คะแนนด้านประวัติการสั่งซื้อ>
    }
}

dimension_scores คือคะแนนรายด้าน 6 มิติ (0=แย่ที่สุด, 100=ดีที่สุด) สำหรับแสดง Radar Chart:
- legal_status: สถานะนิติบุคคล (ดำเนินกิจการ=สูง, เลิก/ล้มละลาย=ต่ำ)
- company_age: อายุบริษัท (นานขึ้น=สูงขึ้น)
- capital: ทุนจดทะเบียน (สูงขึ้น=สูงขึ้น)
- objective_match: ความสอดคล้องของวัตถุประสงค์กิจการกับงานจัดซื้อ
- internal_performance: ผลการประเมินภายใน (เกรด A=สูง, D=ต่ำ, ไม่มีข้อมูล=50)
- po_history: ประวัติการสั่งซื้อ (มาก+สม่ำเสมอ=สูง, ไม่เคย=ต่ำ)

เกณฑ์การประเมิน:
- สถานะนิติบุคคลจาก DBD (ดำเนินกิจการ/เลิก/ล้มละลาย)
- อายุบริษัท (จดทะเบียนนานแค่ไหน)
- ทุนจดทะเบียน (สัดส่วนกับมูลค่าการสั่งซื้อ)
- ผลการประเมินภายใน (เกรด A-D)
- ประวัติการสั่งซื้อ
- ประเภทกิจการตรงกับงานที่จัดซื้อหรือไม่

risk_level:
- low (0-25): ความเสี่ยงต่ำ เหมาะสมในการทำธุรกรรม
- medium (26-50): ความเสี่ยงปานกลาง ควรติดตาม
- high (51-75): ความเสี่ยงสูง ควรระมัดระวังเป็นพิเศษ
- critical (76-100): ความเสี่ยงวิกฤต ไม่แนะนำให้ทำธุรกรรม
PROMPT;
    }

    /**
     * Build the analysis prompt with all available data
     */
    protected function buildAnalysisPrompt(VendorAssessment $assessment, Vendor $vendor): string
    {
        $parts = [];
        $parts[] = "## ข้อมูล Vendor ที่ต้องการประเมิน";
        $parts[] = "- ชื่อบริษัท: {$vendor->company_name}";
        $parts[] = "- เลขทะเบียนนิติบุคคล/Tax ID: {$vendor->tax_id}";
        $parts[] = "- ประเภทงาน: " . ($vendor->work_category ?? 'ไม่ระบุ');
        $parts[] = "- ประสบการณ์: " . ($vendor->experience ?? 'ไม่ระบุ');
        $parts[] = "- สถานะในระบบ: {$vendor->status_label}";

        // DBD Data
        if ($assessment->hasDbdData()) {
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
                $parts[] = "- วัตถุประสงค์: " . implode(', ', array_slice($assessment->dbd_objectives, 0, 5));
            }
        } else {
            $parts[] = "";
            $parts[] = "## ข้อมูลจาก DBD";
            $parts[] = "ไม่สามารถดึงข้อมูลจาก DBD ได้ (กรุณาพิจารณาเป็นปัจจัยเสี่ยงเพิ่มเติม)";
        }

        // Internal evaluation data
        $companyId = $assessment->company_id;
        $latestScore = VendorScore::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->whereNull('quarter')
            ->whereNull('month')
            ->latest('year')
            ->first();

        $parts[] = "";
        $parts[] = "## ข้อมูลการประเมินภายในองค์กร";

        if ($latestScore) {
            $parts[] = "- เกรดปัจจุบัน: " . ($latestScore->weighted_grade ?? $latestScore->grade ?? 'N/A');
            $parts[] = "- คะแนนเฉลี่ย: " . ($latestScore->formatted_score ?? 'N/A');
            $parts[] = "- จำนวนการประเมิน: {$latestScore->evaluation_count} ครั้ง";
            $parts[] = "- แนวโน้ม: " . match($latestScore->trend) {
                'up' => 'ดีขึ้น',
                'down' => 'แย่ลง',
                'stable' => 'คงที่',
                default => 'ไม่ระบุ',
            };

            if ($latestScore->category_scores) {
                $parts[] = "- คะแนนตามหมวด:";
                foreach ($latestScore->category_scores as $cat => $score) {
                    $parts[] = "  - {$cat}: {$score}/4.00";
                }
            }
        } else {
            $parts[] = "- ยังไม่มีการประเมินในระบบ";
        }

        // PO history
        $poStats = PurchaseOrder::where('vendor_id', $vendor->id)
            ->selectRaw('COUNT(*) as total_orders, SUM(total_amount) as total_value, AVG(total_amount) as avg_value')
            ->first();

        $parts[] = "";
        $parts[] = "## ข้อมูลการสั่งซื้อ";
        $parts[] = "- จำนวนใบสั่งซื้อทั้งหมด: " . ($poStats->total_orders ?? 0) . " ครั้ง";
        $parts[] = "- มูลค่ารวม: " . number_format($poStats->total_value ?? 0, 2) . " บาท";
        $parts[] = "- มูลค่าเฉลี่ยต่อครั้ง: " . number_format($poStats->avg_value ?? 0, 2) . " บาท";

        return implode("\n", $parts);
    }

    /**
     * Build summary for rule-based analysis
     */
    protected function buildRuleBasedSummary(Vendor $vendor, int $score, string $level, array $risks, array $strengths): string
    {
        $levelTh = match ($level) {
            'low' => 'ต่ำ',
            'medium' => 'ปานกลาง',
            'high' => 'สูง',
            'critical' => 'วิกฤต',
        };

        $summary = "การประเมินความเสี่ยงของ {$vendor->company_name}: ระดับความเสี่ยง{$levelTh} (คะแนน {$score}/100) ";

        if (!empty($strengths)) {
            $summary .= "จุดแข็ง: " . $strengths[0] . " ";
        }

        if (!empty($risks)) {
            $summary .= "ข้อควรระวัง: " . $risks[0];
        }

        return $summary;
    }

    /**
     * Generate recommendations based on risk factors
     */
    protected function generateRecommendations(array $riskFactors): array
    {
        $recommendations = [];

        foreach ($riskFactors as $factor) {
            if (str_contains($factor, 'เลิก') || str_contains($factor, 'ล้มละลาย')) {
                $recommendations[] = 'ไม่ควรทำธุรกรรมกับบริษัทที่เลิกกิจการหรือล้มละลาย';
            }
            if (str_contains($factor, 'บริษัทใหม่')) {
                $recommendations[] = 'ควรขอหลักประกันหรือเอกสารอ้างอิงเพิ่มเติมจาก Vendor ที่เพิ่งจดทะเบียน';
            }
            if (str_contains($factor, 'ทุนจดทะเบียนค่อนข้างต่ำ')) {
                $recommendations[] = 'ควรพิจารณาให้ Vendor วางหลักประกันผลงาน';
            }
            if (str_contains($factor, 'ไม่สามารถตรวจสอบ')) {
                $recommendations[] = 'ควรตรวจสอบเลขทะเบียนนิติบุคคลให้ถูกต้อง หรือขอเอกสารหนังสือรับรองบริษัท';
            }
            if (str_contains($factor, 'ต้องปรับปรุง')) {
                $recommendations[] = 'ควรกำหนดเงื่อนไขพิเศษในสัญญา และติดตามผลงานอย่างใกล้ชิด';
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = 'ไม่มีข้อกังวลเป็นพิเศษ สามารถดำเนินการจัดซื้อได้ตามปกติ';
        }

        return array_unique($recommendations);
    }

    /**
     * Get the latest assessment for a vendor
     */
    public function getLatestAssessment(int $vendorId, ?int $companyId = null): ?VendorAssessment
    {
        $companyId = $companyId ?? session('company_id');

        return VendorAssessment::where('vendor_id', $vendorId)
            ->where('company_id', $companyId)
            ->completed()
            ->latestFirst()
            ->first();
    }

    /**
     * Check if a vendor needs reassessment (older than 30 days)
     */
    public function needsReassessment(int $vendorId, int $dayThreshold = 30): bool
    {
        $latest = VendorAssessment::where('vendor_id', $vendorId)
            ->completed()
            ->latestFirst()
            ->first();

        if (!$latest) return true;

        return $latest->created_at->diffInDays(now()) >= $dayThreshold;
    }
}
