<?php

namespace Tests\Feature;

use App\Services\SlaService;
use App\Services\TorAiService;
use Tests\TestCase;

class TorFeatureTest extends TestCase
{
    // ─── SLA Service Tests ───────────────────────────────────────

    public function test_sla_service_calculate_working_days(): void
    {
        $service = new SlaService();

        // Monday to Friday = 5 working days
        $start = \Carbon\Carbon::parse('2026-03-02'); // Monday
        $end = \Carbon\Carbon::parse('2026-03-06'); // Friday
        $days = $service->calculateWorkingDays($start, $end);
        $this->assertEquals(5, $days);
    }

    public function test_sla_service_excludes_weekends(): void
    {
        $service = new SlaService();

        // Monday to next Monday = 6 working days (excludes Sat & Sun)
        $start = \Carbon\Carbon::parse('2026-03-02'); // Monday
        $end = \Carbon\Carbon::parse('2026-03-09'); // Next Monday
        $days = $service->calculateWorkingDays($start, $end);
        $this->assertEquals(6, $days);
    }

    public function test_sla_service_calculates_grades(): void
    {
        $service = new SlaService();
        $this->assertEquals('S', $service->calculateGrade(50));
        $this->assertEquals('S', $service->calculateGrade(30));
        $this->assertEquals('A', $service->calculateGrade(51));
        $this->assertEquals('A', $service->calculateGrade(70));
        $this->assertEquals('B', $service->calculateGrade(71));
        $this->assertEquals('B', $service->calculateGrade(90));
        $this->assertEquals('C', $service->calculateGrade(91));
        $this->assertEquals('C', $service->calculateGrade(100));
        $this->assertEquals('D', $service->calculateGrade(101));
        $this->assertEquals('D', $service->calculateGrade(120));
        $this->assertEquals('F', $service->calculateGrade(121));
        $this->assertEquals('F', $service->calculateGrade(200));
    }

    public function test_sla_standard_days(): void
    {
        $service = new SlaService();
        $this->assertEquals(9, $service->getSlaStandardDays('agreement_price'));
        $this->assertEquals(25, $service->getSlaStandardDays('invitation_bid'));
        $this->assertEquals(34, $service->getSlaStandardDays('open_bid'));
        $this->assertEquals(9, $service->getSlaStandardDays(null));
        $this->assertEquals(9, $service->getSlaStandardDays('unknown_method'));
    }

    // ─── AI Service Tests ────────────────────────────────────────

    public function test_ai_fallback_draft_returns_expected_fields(): void
    {
        $service = app(TorAiService::class);
        $result = $service->generateDraft([
            'title' => 'จัดซื้อคอมพิวเตอร์',
            'tor_type' => 'goods',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('background', $result);
        $this->assertArrayHasKey('objectives', $result);
        $this->assertArrayHasKey('scope_of_work', $result);
        $this->assertArrayHasKey('deliverables', $result);
        $this->assertArrayHasKey('specifications', $result);
        $this->assertArrayHasKey('qualification_requirements', $result);
        $this->assertArrayHasKey('evaluation_criteria', $result);
        $this->assertArrayHasKey('payment_terms', $result);
        $this->assertArrayHasKey('warranty_requirements', $result);
        $this->assertArrayHasKey('penalty_clause', $result);
    }

    public function test_ai_fallback_draft_for_each_type(): void
    {
        $service = app(TorAiService::class);

        $types = ['goods', 'services', 'construction', 'consulting'];
        foreach ($types as $type) {
            $result = $service->generateDraft([
                'title' => "ทดสอบ {$type}",
                'tor_type' => $type,
            ]);

            $this->assertIsArray($result, "Failed for type: {$type}");
            $this->assertNotEmpty($result['background'], "Empty background for type: {$type}");
            $this->assertNotEmpty($result['scope_of_work'], "Empty scope_of_work for type: {$type}");
        }
    }

    public function test_ai_fallback_draft_evaluation_criteria_sums_to_100(): void
    {
        $service = app(TorAiService::class);
        $result = $service->generateDraft([
            'title' => 'test',
            'tor_type' => 'goods',
        ]);

        $this->assertIsArray($result['evaluation_criteria']);
        $this->assertNotEmpty($result['evaluation_criteria']);

        $totalWeight = array_sum(array_column($result['evaluation_criteria'], 'weight'));
        $this->assertEquals(100, $totalWeight);
    }

    public function test_ai_improve_field_returns_original_when_empty(): void
    {
        $service = app(TorAiService::class);
        $result = $service->improveField('background', '', []);
        $this->assertEquals('', $result);
    }
}
