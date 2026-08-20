<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\TermsOfReference;
use App\Models\TorTemplate;
use App\Models\User;
use App\Services\TorDocumentService;
use Database\Seeders\TorTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TorDocumentBuilderTest extends TestCase
{
    use RefreshDatabase;

    private TorDocumentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TorTemplateSeeder::class);
        $this->service = new TorDocumentService;
    }

    // ─── Template library ────────────────────────────────────────

    public function test_seeder_creates_all_six_templates(): void
    {
        $this->assertSame(6, TorTemplate::count());
        $this->assertSame(
            ['buy_general', 'buy_inventory', 'service', 'service_bidding', 'manufacture', 'rent'],
            TorTemplate::orderBy('sort_order')->pluck('code')->all()
        );
    }

    public function test_section_sets_match_the_paper_templates(): void
    {
        $keys = fn (string $code) => TorTemplate::where('code', $code)->first()
            ->sections->pluck('section_key')->all();

        // ซื้อ: มีภาษี+หลักประกัน ไม่มีรักษาความลับ
        $this->assertContains('tax_duty', $keys('buy_general'));
        $this->assertContains('contract_security', $keys('buy_general'));
        $this->assertNotContains('confidentiality', $keys('buy_general'));

        // จ้างบริการ/จ้างผลิต: มีรักษาความลับ ไม่มีภาษี/หลักประกัน
        foreach (['service', 'service_bidding', 'manufacture'] as $code) {
            $this->assertContains('confidentiality', $keys($code), $code);
            $this->assertNotContains('tax_duty', $keys($code), $code);
            $this->assertNotContains('contract_security', $keys($code), $code);
        }

        // ซื้อ: ข้อ 5 มีช่องจำนวน + hint "ใส่ชื่อสินค้าตาม Material" (comment ลูกค้า)
        $buyScope = TorTemplate::where('code', 'buy_general')->first()
            ->sections->firstWhere('section_key', 'scope_of_work');
        $this->assertTrue($buyScope->config['with_quantity']);
        $this->assertSame('ใส่ชื่อสินค้าตาม Material', $buyScope->config['item_hint']);

        // เช่า: ค่าปรับอยู่ข้อ 9, รับประกันการเช่าอยู่ข้อ 11
        $rent = TorTemplate::where('code', 'rent')->first()->sections;
        $this->assertSame('9', $rent->firstWhere('section_key', 'penalty')->display_number);
        $this->assertSame('11', $rent->firstWhere('section_key', 'warranty')->display_number);
        $this->assertSame('เงื่อนไขการรับประกันการเช่า', $rent->firstWhere('section_key', 'warranty')->title_th);
    }

    public function test_manufacture_has_rich_scope_and_document_checklist(): void
    {
        $mfg = TorTemplate::where('code', 'manufacture')->first();

        $scope = $mfg->sections->firstWhere('section_key', 'scope_of_work');
        $this->assertCount(4, $scope->config['items']); // จัดหาวัตถุดิบ/ผลิต/บรรจุภัณฑ์/ควบคุมคุณภาพ

        $delivery = $mfg->sections->firstWhere('section_key', 'delivery');
        $this->assertCount(7, $delivery->config['documents']); // COA, สบ.5, Artwork, Spec, Report, มาตรฐาน, คู่มือ
        $this->assertArrayHasKey('tolerance_clause', $delivery->config);
    }

    // ─── Placeholder resolution ──────────────────────────────────

    public function test_build_document_sections_resolves_placeholders(): void
    {
        $service = TorTemplate::where('code', 'service')->with('sections')->first();
        $doc = $service->buildDocumentSections([
            'company_full' => 'บริษัท อินโนบิก นูทริชั่น จำกัด',
            'company_short' => 'INNT',
        ]);

        $json = json_encode($doc, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('{{', $json, 'ต้องไม่มี placeholder ค้าง');
        $this->assertStringContainsString('ผู้รับจ้าง', $json);
        $this->assertStringContainsString('INNT', $json);

        $penalty = collect($doc)->firstWhere('key', 'penalty');
        $this->assertStringContainsString('ร้อยละ 0.1 ต่อวันของมูลค่าค่าจ้าง', $penalty['body']);

        $buy = TorTemplate::where('code', 'buy_general')->with('sections')->first()
            ->buildDocumentSections(['company_short' => 'INNT']);
        $buyPenalty = collect($buy)->firstWhere('key', 'penalty');
        $this->assertStringContainsString('ร้อยละ 0.2 ต่อวันของมูลค่าสินค้า', $buyPenalty['body']);
    }

    // ─── Payment validation (รวม 100%) ───────────────────────────

    private function paymentSection(array $options): array
    {
        return ['key' => 'payment', 'number' => '7', 'type' => 'payment', 'hidden' => false,
            'data' => ['options' => $options]];
    }

    public function test_payment_must_have_at_least_one_option(): void
    {
        $errors = $this->service->validate([$this->paymentSection([
            ['key' => 'after_signing', 'enabled' => false, 'percent' => null],
        ])]);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('อย่างน้อย 1 รูปแบบ', $errors[0]);
    }

    public function test_payment_percentages_must_total_100(): void
    {
        $bad = $this->service->validate([$this->paymentSection([
            ['key' => 'after_signing', 'enabled' => true, 'percent' => 30],
            ['key' => 'installments', 'enabled' => true, 'rows' => [
                ['no' => 1, 'percent' => 40],
                ['no' => 2, 'percent' => 20],
            ]],
        ])]);
        $this->assertNotEmpty($bad);
        $this->assertStringContainsString('90', $bad[0]);

        $good = $this->service->validate([$this->paymentSection([
            ['key' => 'after_signing', 'enabled' => true, 'percent' => 25],
            ['key' => 'installments', 'enabled' => true, 'rows' => [
                ['no' => 1, 'percent' => 50],
                ['no' => 2, 'percent' => 25],
            ]],
        ])]);
        $this->assertSame([], $good);
    }

    public function test_hidden_payment_section_is_not_validated(): void
    {
        $section = $this->paymentSection([]);
        $section['hidden'] = true;
        $this->assertSame([], $this->service->validate([$section]));
    }

    public function test_timeline_requires_a_mode(): void
    {
        $errors = $this->service->validate([
            ['key' => 'timeline', 'number' => '6', 'type' => 'timeline', 'hidden' => false, 'data' => []],
        ]);
        $this->assertNotEmpty($errors);
    }

    // ─── Renumbering when sections are hidden ────────────────────

    public function test_visible_sections_renumber_after_hiding(): void
    {
        $sections = [
            ['key' => 'preamble', 'number' => null, 'type' => 'clause', 'hidden' => false],
            ['key' => 'definitions', 'number' => '1', 'type' => 'clause', 'hidden' => false],
            ['key' => 'warranty', 'number' => '2', 'type' => 'clause', 'hidden' => true],
            ['key' => 'penalty', 'number' => '3', 'type' => 'clause', 'hidden' => false],
        ];

        $visible = $this->service->visibleSections($sections);

        $this->assertCount(3, $visible); // warranty หายไป
        $this->assertSame('1', collect($visible)->firstWhere('key', 'definitions')['render_number']);
        $this->assertSame('2', collect($visible)->firstWhere('key', 'penalty')['render_number']); // 3 → 2
        $this->assertArrayNotHasKey('render_number', $visible[0]); // preamble ไม่มีเลข
    }

    // ─── Preview route (E2E) ─────────────────────────────────────

    private function makeSessionAndTor(): array
    {
        $company = Company::create([
            'name' => 'Innobic Nutrition Company Limited',
            'display_name' => 'Innobic Nutrition',
            'code' => 'INNT',
            'database_connection' => 'sqlite',
            'is_active' => true,
        ]);
        $department = Department::create(['name' => 'จัดซื้อ', 'code' => 'PROC']);
        $user = User::factory()->create();

        $template = TorTemplate::where('code', 'service')->with('sections')->first();
        $sections = $this->service->buildForCompany($template, $company);

        // เลือก timeline + payment ให้เอกสาร valid
        foreach ($sections as &$s) {
            if ($s['type'] === 'timeline') {
                $s['data']['mode'] = 'from_signing';
                $s['data']['until_date'] = '2026-12-31';
            }
            if ($s['type'] === 'payment') {
                $s['data']['options'][0]['enabled'] = true;
                $s['data']['options'][0]['percent'] = 100;
            }
        }

        $tor = TermsOfReference::create([
            'tor_number' => 'TOR-TEST-0001',
            'company_id' => $company->id,
            'department_id' => $department->id,
            'title' => 'จ้างทดสอบระบบ Builder',
            'tor_type' => 'services',
            'work_type' => 'hire',
            'status' => TermsOfReference::STATUS_DRAFT,
            'currency' => 'THB',
            'procurement_type' => 'service',
            'party_term' => $template->party_term,
            'tor_template_id' => $template->id,
            'document_sections' => $sections,
            'scope_of_work' => $this->service->flattenScope($sections),
            'created_by' => $user->id,
        ]);

        $session = [
            'company_id' => $company->id,
            'company_connection' => 'sqlite',
        ];

        return [$user, $session, $tor];
    }

    public function test_preview_route_renders_document(): void
    {
        [$user, $session, $tor] = $this->makeSessionAndTor();

        $response = $this->actingAs($user)->withSession($session)
            ->get(route('tor-builder.preview', $tor));

        $response->assertOk();
        $response->assertSee('จ้างทดสอบระบบ Builder');
        $response->assertSee('ข้อกำหนด (Terms of Reference: TOR)');
        $response->assertSee('ชำระเงินหลังจากลงนามในสัญญา');           // option ที่เลือก
        $response->assertDontSee('ชำระเงินหลังส่งมอบงานเป็นงวดงาน');    // option ที่ไม่ได้เลือก
        $response->assertSee('นับถัดจากวันที่ลงนามสัญญา');              // timeline mode ที่เลือก
        $response->assertSee('การรักษาความลับของข้อมูล');               // section เฉพาะประเภทจ้าง
    }

    // ─── Builder page (Livewire) ─────────────────────────────────

    public function test_builder_page_loads_template_and_saves_tor(): void
    {
        [$user, $session] = $this->makeSessionAndTor();

        $this->actingAs($user);
        session($session);

        $component = \Livewire\Livewire::test(\App\Filament\Pages\TorBuilder::class)
            ->set('data.title', 'จ้างบริการทดสอบผ่าน Builder')
            ->set('data.department_id', Department::first()->id)
            ->set('data.budget_estimate', 50000)
            ->set('data.currency', 'THB')
            ->set('data.procurement_type', 'service')
            ->call('loadTemplate')
            ->assertCount('sections', 13); // service มี 13 sections

        // เติมข้อมูลให้ valid: timeline mode + payment 100%
        $sections = $component->get('sections');
        foreach ($sections as $i => $s) {
            if ($s['type'] === 'timeline') {
                $component->set("sections.{$i}.data.mode", 'other')
                    ->set("sections.{$i}.data.other_text", 'ภายใน 90 วัน');
            }
            if ($s['type'] === 'payment') {
                $component->set("sections.{$i}.data.options.1.enabled", true)
                    ->set("sections.{$i}.data.options.1.percent", 100);
            }
        }

        $component->call('save');

        $tor = TermsOfReference::where('title', 'จ้างบริการทดสอบผ่าน Builder')->first();
        $this->assertNotNull($tor);
        $this->assertSame('service', $tor->procurement_type);
        $this->assertSame('ผู้รับจ้าง', $tor->party_term);
        $this->assertNotEmpty($tor->document_sections);
        $this->assertNotSame('-', $tor->scope_of_work);
        $this->assertSame(TermsOfReference::STATUS_DRAFT, $tor->status);
    }

    public function test_builder_save_rejects_invalid_payment_total(): void
    {
        [$user, $session] = $this->makeSessionAndTor();

        $this->actingAs($user);
        session($session);

        $component = \Livewire\Livewire::test(\App\Filament\Pages\TorBuilder::class)
            ->set('data.title', 'เอกสารไม่ครบเปอร์เซ็นต์')
            ->set('data.department_id', Department::first()->id)
            ->set('data.budget_estimate', 50000)
            ->set('data.currency', 'THB')
            ->set('data.procurement_type', 'buy_general')
            ->call('loadTemplate');

        $sections = $component->get('sections');
        foreach ($sections as $i => $s) {
            if ($s['type'] === 'timeline') {
                $component->set("sections.{$i}.data.mode", 'other')
                    ->set("sections.{$i}.data.other_text", 'x');
            }
            if ($s['type'] === 'payment') {
                $component->set("sections.{$i}.data.options.0.enabled", true)
                    ->set("sections.{$i}.data.options.0.percent", 60); // ไม่ถึง 100
            }
        }

        $component->call('save');

        $this->assertNull(TermsOfReference::where('title', 'เอกสารไม่ครบเปอร์เซ็นต์')->first());
    }

    public function test_builder_copies_existing_tor(): void
    {
        [$user, $session, $source] = $this->makeSessionAndTor();

        $this->actingAs($user);
        session($session);

        $component = \Livewire\Livewire::test(\App\Filament\Pages\TorBuilder::class)
            ->set('data.copy_from', $source->id)
            ->call('loadTemplate');

        $this->assertSame(
            collect($source->document_sections)->pluck('key')->all(),
            collect($component->get('sections'))->pluck('key')->all()
        );
    }

    // ─── TOR → PR conversion ─────────────────────────────────────

    public function test_convert_to_pr_data_uses_document_sections(): void
    {
        [, , $tor] = $this->makeSessionAndTor();

        $tor->update(['budget_estimate' => 36000, 'start_date' => '2026-09-01']);
        $tor = $tor->fresh();

        $prData = $tor->convertToPrData();
        $this->assertSame($tor->id, $prData['tor_id']);
        $this->assertEquals(36000, (float) $prData['procurement_budget']);
        $this->assertNotSame('', $prData['description']); // scope flatten
        $this->assertStringContainsString('ชำระเงินหลังจากลงนามในสัญญา 100%', $prData['payment_schedule']);

        // ไม่มี TorItems → ได้รายการเหมา 1 บรรทัดจากงบ
        $items = $tor->convertItemsToPrItems();
        $this->assertCount(1, $items);
        $this->assertEquals(36000, (float) $items[0]['estimated_amount']);
    }

    public function test_builder_submit_moves_tor_into_workflow(): void
    {
        [$user, $session, $tor] = $this->makeSessionAndTor();
        \Illuminate\Support\Facades\Event::fake([\App\Events\TorSubmitted::class]);

        $this->actingAs($user);
        session($session);

        \Livewire\Livewire::withQueryParams(['tor' => $tor->id])
            ->test(\App\Filament\Pages\TorBuilder::class)
            ->call('submitTor')
            ->assertSet('torStatus', 'submitted');

        $this->assertSame('submitted', $tor->fresh()->status);
        \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\TorSubmitted::class);
    }

    public function test_pdf_route_renders_document_with_repeating_header(): void
    {
        [$user, $session, $tor] = $this->makeSessionAndTor();

        $response = $this->actingAs($user)->withSession($session)
            ->get(route('tor-builder.pdf', $tor));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertGreaterThan(50_000, strlen($response->getContent())); // มีโลโก้+ฟอนต์ฝัง
    }

    public function test_pdf_route_blocks_other_company(): void
    {
        [$user, $session, $tor] = $this->makeSessionAndTor();

        $other = Company::create([
            'name' => 'Other Co 2', 'display_name' => 'Other2', 'code' => 'OT2',
            'database_connection' => 'sqlite', 'is_active' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['company_id' => $other->id, 'company_connection' => 'sqlite'])
            ->get(route('tor-builder.pdf', $tor))
            ->assertForbidden();
    }

    public function test_preview_route_blocks_other_company(): void
    {
        [$user, $session, $tor] = $this->makeSessionAndTor();

        $other = Company::create([
            'name' => 'Other Co', 'display_name' => 'Other', 'code' => 'OTH',
            'database_connection' => 'sqlite', 'is_active' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['company_id' => $other->id, 'company_connection' => 'sqlite'])
            ->get(route('tor-builder.preview', $tor))
            ->assertForbidden();
    }
}
