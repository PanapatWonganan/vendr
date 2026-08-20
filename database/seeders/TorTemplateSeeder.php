<?php

namespace Database\Seeders;

use App\Models\TorTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the TOR clause template library from the customer's 5 Word templates
 * ([INNT] TOR ประเภทซื้อทั่วไปและซื้อ Inventory / จ้างบริการทั่วไป / จ้างบริการ Bidding /
 * จ้างผลิต Inventory / เช่า, received 2026-08).
 *
 * Shared boilerplate is stored once with placeholders resolved at TOR creation:
 *   {{party}}         ผู้ขาย / ผู้รับจ้าง / ผู้ให้เช่า
 *   {{company_full}}  บริษัท อินโนบิก นูทริชั่น จำกัด (ตามบริษัทที่เลือก)
 *   {{company_short}} INNT / INBA / INBL
 *   {{penalty_rate}}  0.2 / 0.1
 *   {{penalty_base}}  มูลค่าสินค้า / มูลค่าค่าจ้าง
 *
 * Idempotent: re-running replaces all templates and their sections.
 */
class TorTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'code' => TorTemplate::TYPE_BUY_GENERAL,
                'name_th' => 'ซื้อทั่วไป',
                'party_term' => 'ผู้ขาย',
                'penalty_rate' => 0.20,
                'penalty_base' => 'มูลค่าสินค้า',
                'sort_order' => 1,
                'sections' => $this->buySections(),
            ],
            [
                'code' => TorTemplate::TYPE_BUY_INVENTORY,
                'name_th' => 'ซื้อ Inventory',
                'party_term' => 'ผู้ขาย',
                'penalty_rate' => 0.20,
                'penalty_base' => 'มูลค่าสินค้า',
                'sort_order' => 2,
                'sections' => $this->buySections(),
            ],
            [
                'code' => TorTemplate::TYPE_SERVICE,
                'name_th' => 'จ้างบริการทั่วไป',
                'party_term' => 'ผู้รับจ้าง',
                'penalty_rate' => 0.10,
                'penalty_base' => 'มูลค่าค่าจ้าง',
                'sort_order' => 3,
                'sections' => $this->serviceSections(),
            ],
            [
                'code' => TorTemplate::TYPE_SERVICE_BIDDING,
                'name_th' => 'จ้างบริการ (Bidding)',
                'party_term' => 'ผู้รับจ้าง',
                'penalty_rate' => 0.10,
                'penalty_base' => 'มูลค่าค่าจ้าง',
                'sort_order' => 4,
                'sections' => $this->serviceSections(),
            ],
            [
                'code' => TorTemplate::TYPE_MANUFACTURE,
                'name_th' => 'จ้างผลิต Inventory',
                'party_term' => 'ผู้รับจ้าง',
                'penalty_rate' => 0.10,
                'penalty_base' => 'มูลค่าค่าจ้าง',
                'sort_order' => 5,
                'sections' => $this->manufactureSections(),
            ],
            [
                'code' => TorTemplate::TYPE_RENT,
                'name_th' => 'เช่า',
                'party_term' => 'ผู้ให้เช่า',
                'penalty_rate' => 0.20,
                'penalty_base' => 'มูลค่าสินค้า',
                'sort_order' => 6,
                'sections' => $this->rentSections(),
            ],
        ];

        DB::transaction(function () use ($templates) {
            foreach ($templates as $data) {
                $sections = $data['sections'];
                unset($data['sections']);
                $data['name_en'] = null;
                $data['company_id'] = null;
                $data['is_active'] = true;

                $template = TorTemplate::updateOrCreate(['code' => $data['code']], $data);
                $template->sections()->delete();

                foreach ($sections as $i => $section) {
                    $template->sections()->create($section + ['sort_order' => ($i + 1) * 10]);
                }
            }
        });

        $this->command?->info('TOR templates seeded: '.count($templates));
    }

    // ------------------------------------------------------------------
    // Per-type section lists (order = เลขข้อในเอกสารจริง)
    // ------------------------------------------------------------------

    /** ซื้อทั่วไป / ซื้อ Inventory: 1-8, รับประกันสินค้า, ค่าปรับ, บอกเลิก, ภาษี, หลักประกัน */
    private function buySections(): array
    {
        return [
            $this->preamble(),
            $this->definitions(),
            $this->qualifications(),
            $this->proposalDocuments(),
            $this->evaluationCriteria(),
            $this->scope('5', 'ผู้ขายจะต้องดำเนินการจัดส่งให้กับ{{company_full}} "{{company_short}}" ได้ตามขอบเขตการดำเนินงานและรายละเอียดที่กำหนดในเอกสารแนบ ซึ่งมีรายละเอียดงานดังนี้', [
                ['no' => '5.1', 'text' => '', 'quantity' => ''],
                ['no' => '5.2', 'text' => '', 'quantity' => ''],
                ['no' => '5.3', 'text' => '', 'quantity' => ''],
            ], true, 'ใส่ชื่อสินค้าตาม Material'),
            $this->timeline('6'),
            $this->payment('7'),
            $this->delivery('8', [
                'รายละเอียดคุณลักษณะเฉพาะของสินค้า (Specification: Spec)',
                'คู่มือการใช้งาน',
            ]),
            $this->warrantyGoods('9'),
            $this->penalty('10'),
            $this->termination('11'),
            $this->taxDuty('12'),
            $this->contractSecurity('13'),
        ];
    }

    /** จ้างบริการทั่วไป / จ้างบริการ Bidding: 1-8, รับประกันสินค้า, ค่าปรับ, รักษาความลับ, บอกเลิก (จบ) */
    private function serviceSections(): array
    {
        return [
            $this->preamble(),
            $this->definitions(),
            $this->qualifications(),
            $this->proposalDocuments(),
            $this->evaluationCriteria(),
            $this->scope('5', 'ผู้รับจ้างจะต้องดำเนินการให้กับ{{company_full}} "{{company_short}}" ได้ตามขอบเขตการดำเนินงานและรายละเอียดที่กำหนดในเอกสารแนบ ซึ่งมีรายละเอียดงานดังนี้', [
                ['no' => '5.1', 'text' => ''],
                ['no' => '5.2', 'text' => ''],
                ['no' => '5.3', 'text' => ''],
            ]),
            $this->timeline('6'),
            $this->payment('7'),
            $this->delivery('8', [
                'เอกสารหรือหลักฐานอื่นใดที่เกี่ยวข้องตามที่ {{company_short}} กำหนด',
            ]),
            $this->warrantyGoods('9'),
            $this->penalty('10'),
            $this->confidentiality('11'),
            $this->termination('12'),
        ];
    }

    /** จ้างผลิต Inventory: เหมือนจ้างบริการ แต่ scope มีโครง 4 หมวด + doc checklist 7 รายการ + Tolerance ±5% */
    private function manufactureSections(): array
    {
        $sections = $this->serviceSections();

        // ข้อ 5: โครงขอบเขตงานผลิต 4 หมวด
        $sections[5] = $this->scope('5', 'ผู้รับจ้างจะต้องดำเนินการผลิตให้กับ{{company_full}} "{{company_short}}" ได้ตามขอบเขตการดำเนินงานและรายละเอียดที่กำหนดในเอกสารแนบ ซึ่งมีรายละเอียดงานดังนี้', [
            ['no' => '5.1', 'text' => 'การจัดหาวัตถุดิบ', 'children' => [
                ['no' => '5.1.1', 'text' => 'ผู้รับจ้างจะต้องจัดหาวัตถุดิบที่มีคุณภาพเป็นไปตามมาตรฐาน และ ISO ที่กำหนด'],
                ['no' => '5.1.2', 'text' => ''],
            ]],
            ['no' => '5.2', 'text' => 'กระบวนการผลิต', 'children' => [
                ['no' => '5.2.1', 'text' => 'ผู้รับจ้างจะต้องดำเนินการผลิตสินค้าให้เป็นไปตามสูตร แบบ หรือตัวอย่างที่ {{company_short}} กำหนด และ ISO ที่กำหนด'],
                ['no' => '5.2.2', 'text' => 'ผู้รับจ้างจะต้องควบคุมคุณภาพในทุกขั้นตอนการผลิต'],
            ]],
            ['no' => '5.3', 'text' => 'การออกแบบและบรรจุภัณฑ์', 'children' => [
                ['no' => '5.3.1', 'text' => 'จัดทำรูปแบบ Artwork ของฉลาก (Label) และบรรจุภัณฑ์ตามแบบที่ {{company_short}} กำหนด และ ISO ที่กำหนด'],
                ['no' => '5.3.2', 'text' => 'บรรจุสินค้าให้เรียบร้อย แข็งแรง และเหมาะสมต่อการขนส่ง'],
            ]],
            ['no' => '5.4', 'text' => 'การควบคุมคุณภาพ', 'children' => [
                ['no' => '5.4.1', 'text' => 'ตรวจสอบคุณภาพสินค้า ก่อนส่งมอบ และจัดทำรายงานผลการตรวจสอบ (ถ้ามี)'],
                ['no' => '5.4.2', 'text' => 'สินค้าที่ส่งมอบจะต้องเป็นไปตามสูตร แบบ หรือตัวอย่างที่ {{company_short}} กำหนด และ ISO ที่กำหนด'],
            ]],
        ]);

        // ข้อ 8: checklist เอกสารส่งมอบ 7 รายการ + tolerance clause
        $sections[8] = $this->delivery('8', [
            'ใบรับรองผลการวิเคราะห์ (Certificate of Analysis: COA)',
            'เอกสาร สบ.5',
            'แบบ Artwork ที่ได้รับอนุมัติ',
            'รายละเอียดคุณลักษณะเฉพาะของสินค้า (Specification: Spec)',
            'รายงานผลการดำเนินงาน (Report)',
            'เอกสารรับรองมาตรฐาน (เช่น GMP, HACCP, Halal ฯลฯ หากเกี่ยวข้อง)',
            'คู่มือการใช้งาน',
        ], 'ปริมาณการส่งมอบสินค้าอาจคลาดเคลื่อน (Tolerance) ได้ไม่เกินร้อยละ 5 (±5%) ของปริมาณที่กำหนดไว้ในสัญญา หรือเป็นไปตามเกณฑ์ที่ {{company_short}} กำหนด ทั้งนี้ ต้องไม่กระทบต่อคุณภาพ ราคาและเงื่อนไขอื่นตามสัญญา หากอัตราการสูญเสียเกินกว่าที่กำหนด ผู้รับจ้างต้องรับผิดชอบจัดหาทดแทนหรือชดใช้ค่าเสียหายส่วนที่เกินดังกล่าว โดยไม่คิดค่าใช้จ่ายเพิ่มเติมแก่ {{company_short}}');

        return $sections;
    }

    /** เช่า: 1-8, ค่าปรับ(9), บอกเลิก(10), รับประกันการเช่า(11), ภาษี(12), หลักประกัน(13) */
    private function rentSections(): array
    {
        return [
            $this->preamble(),
            $this->definitions(),
            $this->qualifications(),
            $this->proposalDocuments(),
            $this->evaluationCriteria(),
            $this->scope('5', 'ผู้ให้เช่าจะต้องดำเนินการส่งมอบให้กับ{{company_full}} "{{company_short}}" ได้ตามขอบเขตการดำเนินงานและรายละเอียดที่กำหนดในเอกสารแนบ ซึ่งมีรายละเอียดงานดังนี้', [
                ['no' => '5.1', 'text' => ''],
                ['no' => '5.2', 'text' => ''],
                ['no' => '5.3', 'text' => ''],
            ]),
            $this->timeline('6'),
            $this->payment('7'),
            $this->delivery('8', []),
            $this->penalty('9'),
            $this->termination('10'),
            $this->warrantyRent('11'),
            $this->taxDuty('12'),
            $this->contractSecurity('13'),
        ];
    }

    // ------------------------------------------------------------------
    // Shared clause builders (ข้อความจริงจากไฟล์ template ของลูกค้า)
    // ------------------------------------------------------------------

    private function preamble(): array
    {
        return [
            'section_key' => 'preamble',
            'display_number' => null,
            'title_th' => 'เงื่อนไขข้อกำหนด{{company_full}} (TOR)',
            'section_type' => 'clause',
            'body_default' => '{{company_full}} ซึ่งต่อไปเรียกว่า "{{company_short}}" มีความประสงค์จะดำเนินการจัดซื้อจัดจ้างตามระเบียบ{{company_full}} ว่าด้วยการจัดหาและจำหน่ายที่มิใช่การจำหน่ายในทางธุรกิจ 2566',
        ];
    }

    private function definitions(): array
    {
        return [
            'section_key' => 'definitions',
            'display_number' => '1',
            'title_th' => 'คำนิยาม',
            'section_type' => 'clause',
            'config' => ['items' => [
                ['no' => '1.1', 'text' => '"{{company_short}}" หมายถึง {{company_full}}'],
                ['no' => '1.2', 'text' => '"ผู้เสนอราคา" หมายถึง บุคคลธรรมดา หรือนิติบุคคลที่ประสงค์จะเสนอราคา หรือมีสิทธิเข้าเสนอราคากับ {{company_short}}'],
                // ต้นฉบับ find-replace พังเป็น "ผู้ให้เช่า / ผู้รับจ้าง / ผู้ให้เช่า" ฯลฯ — ใช้ list เต็มตามไฟล์ซื้อซึ่งถูกต้อง
                ['no' => '1.3', 'text' => '"คู่ค้า" หมายถึง ผู้ขาย / ผู้รับจ้าง / ผู้ให้เช่า'],
            ]],
        ];
    }

    private function qualifications(): array
    {
        return [
            'section_key' => 'bidder_qualifications',
            'display_number' => '2',
            'title_th' => 'คุณสมบัติของผู้เสนอราคา',
            'section_type' => 'clause',
            // ต้นฉบับพิมพ์เลขข้อผิดเป็น 1.1-1.4 — แก้เป็น 2.1-2.4
            'config' => ['items' => [
                ['no' => '2.1', 'text' => 'มีความสามารถตามกฎหมาย'],
                ['no' => '2.2', 'text' => 'ไม่เป็นบุคคลล้มละลาย'],
                ['no' => '2.3', 'text' => 'ไม่อยู่ระหว่างเลิกกิจการ'],
                ['no' => '2.4', 'text' => 'เป็นบุคคลธรรมดาหรือนิติบุคคลผู้มีอาชีพตามลักษณะงานดังกล่าว'],
            ]],
        ];
    }

    private function proposalDocuments(): array
    {
        return [
            'section_key' => 'proposal_documents',
            'display_number' => '3',
            'title_th' => 'หลักฐานการยื่นข้อเสนอ',
            'section_type' => 'clause',
            'body_default' => 'ในกรณีผู้เสนอราคามอบอำนาจให้บุคคลอื่นกระทำการแทนให้แนบหนังสือมอบอำนาจซึ่งติดอากรแสตมป์ตามกฎหมาย โดยมีหลักฐานแสดงตัวตนของผู้มอบอำนาจและผู้รับมอบอำนาจพร้อมรับรองสำเนาถูกต้อง ทั้งนี้ หากผู้รับมอบอำนาจเป็นบุคคลธรรมดาต้องเป็นผู้ที่บรรลุนิติภาวะตามกฎหมายแล้วเท่านั้น',
        ];
    }

    private function evaluationCriteria(): array
    {
        return [
            'section_key' => 'evaluation_criteria',
            'display_number' => '4',
            'title_th' => 'หลักเกณฑ์และสิทธิ์ในการพิจารณา',
            'section_type' => 'clause',
            'body_default' => 'เกณฑ์ราคา / ผลงาน',
        ];
    }

    private function scope(string $number, string $intro, array $items, bool $withQuantity = false, ?string $itemHint = null): array
    {
        $config = [
            'with_quantity' => $withQuantity, // ประเภทซื้อ: แต่ละรายการมีช่อง "จำนวน"
            'items' => $items,
        ];
        if ($itemHint !== null) {
            $config['item_hint'] = $itemHint; // placeholder ในช่องรายการ (comment ลูกค้า)
        }

        return [
            'section_key' => 'scope_of_work',
            'display_number' => $number,
            'title_th' => 'ขอบเขตการดำเนินงาน/รายละเอียดคุณลักษณะเฉพาะ',
            'section_type' => 'scope',
            'body_default' => $intro,
            'config' => $config,
        ];
    }

    private function timeline(string $number): array
    {
        return [
            'section_key' => 'timeline',
            'display_number' => $number,
            'title_th' => 'ระยะเวลาดำเนินการ',
            'section_type' => 'timeline',
            'config' => [
                // เลือกได้ 1 mode เท่านั้น — PDF/Print แสดงเฉพาะ mode ที่เลือก
                'modes' => [
                    ['key' => 'date_range', 'label' => 'ระยะเวลาดำเนินงานเริ่มวันที่ {start_date} ถึงวันที่ {end_date}'],
                    ['key' => 'from_signing', 'label' => 'ระยะเวลาดำเนินงานนับถัดจากวันที่ลงนามสัญญาจนถึงวันที่ {until_date}'],
                    ['key' => 'other', 'label' => 'อื่นๆ : {other_text}'],
                ],
            ],
        ];
    }

    private function payment(string $number): array
    {
        return [
            'section_key' => 'payment',
            'display_number' => $number,
            'title_th' => 'การชำระเงิน',
            'section_type' => 'payment',
            'config' => [
                // เลือกได้ 1 หรือหลาย option, ผลรวม % ทุก option ที่เลือก = 100
                'options' => [
                    [
                        'key' => 'after_signing',
                        'label' => 'ชำระเงินหลังจากลงนามในสัญญา',
                        'has_percent' => true,
                        'body' => '{{company_short}} จะชำระเงินให้แก่{{party}}ภายใน….วัน นับถัดจากวันที่ {{company_short}} ได้รับหลักฐานการขอรับชำระเงิน ถูกต้อง ครบถ้วนตามที่กำหนด ทั้งนี้ หาก{{party}}ยื่นเอกสารไม่ถูกต้อง ไม่ครบถ้วนหรือยื่นล่าช้ากว่ากำหนด ระยะเวลาการชำระเงินดังกล่าวจะขยายออกไปตามจำนวนวันที่ยื่นเอกสารล่าช้าเช่นกัน',
                    ],
                    [
                        'key' => 'after_completion',
                        'label' => 'ชำระเงินหลังส่งมอบงานแล้วเสร็จ',
                        'has_percent' => true,
                        'body' => '{{company_short}} จะชำระเงินให้แก่{{party}}ตามการส่งมอบงานจริง ภายใน 30 วัน นับถัดจากวันที่ {{company_short}} ได้รับหลักฐานการขอรับชำระเงิน พร้อมเอกสารและข้อมูลการส่งมอบงานที่ถูกต้องครบถ้วนตามสัญญาหรือหนังสือข้อตกลงของ {{company_short}} และผ่านการตรวจรับงานจากคณะกรรมการตรวจรับ ทั้งนี้ หาก{{party}}ยื่นเอกสารไม่ถูกต้อง ไม่ครบถ้วนหรือยื่นล่าช้ากว่ากำหนด ระยะเวลาการชำระเงินดังกล่าวจะขยายออกไปตามจำนวนวันที่ยื่นเอกสารล่าช้าเช่นกัน',
                    ],
                    [
                        'key' => 'installments',
                        'label' => 'ชำระเงินหลังส่งมอบงานเป็นงวดงาน',
                        'has_percent' => false, // ใช้ตารางงวดแทน, ระบบ validate ผลรวม = 100%
                        'body' => '{{company_short}} จะจ่ายเงินให้{{party}}ภายใน 30 วัน นับถัดจากวันที่ {{company_short}} ได้รับหลักฐานการขอรับชำระเงิน ข้อมูลการส่งมอบงานที่ถูกต้องครบถ้วนตามสัญญาหรือหนังสือข้อตกลงสัญญาของ {{company_short}} และผ่านการตรวจรับงานจากคณะกรรมการตรวจรับ ทั้งนี้ หาก{{party}}ยื่นเอกสารไม่ถูกต้อง ไม่ครบถ้วนหรือยื่นล่าช้ากว่ากำหนด ระยะเวลาการชำระเงินดังกล่าวจะขยายออกไปตามจำนวนวันที่ยื่นเอกสารล่าช้าเช่นกัน',
                    ],
                ],
                'billing' => [
                    'address' => 'ณ {{company_full}} (สำนักงานใหญ่) เลขที่ 425/1 อาคารเอนโก้ เทอร์มินอล อาคารบี ชั้น 7 ถนนกำแพงเพชร 6 แขวงดอนเมือง เขตดอนเมือง กรุงเทพมหานคร 10210',
                    'contact' => '',
                    'phone' => '',
                ],
            ],
        ];
    }

    private function delivery(string $number, array $documents, ?string $tolerance = null): array
    {
        $config = [
            // 8.1.1 เอกสารประกอบการส่งมอบ — แต่ละรายการอ้างงวดตามข้อ 7.3 ได้
            'documents' => array_map(
                fn ($doc) => ['name' => $doc, 'milestone_ref' => ''],
                $documents
            ),
            // 8.3 คณะกรรมการตรวจรับ (ชื่อ/โทร/อีเมล) — เพิ่มได้ไม่จำกัด
            'committee' => [
                ['name' => '', 'phone' => '', 'email' => ''],
                ['name' => '', 'phone' => '', 'email' => ''],
            ],
        ];
        if ($tolerance !== null) {
            $config['tolerance_clause'] = $tolerance; // จ้างผลิต: ข้อ 8.1.3
        }

        return [
            'section_key' => 'delivery',
            'display_number' => $number,
            'title_th' => 'การส่งมอบงาน',
            'section_type' => 'delivery',
            'body_default' => '{{party}}จะต้องดำเนินการส่งมอบงานให้แล้วเสร็จภายในระยะเวลาที่กำหนดตามข้อ 6. จำนวน….งวด พร้อมเอกสารการส่งมอบงานและเอกสารประกอบการส่งมอบ'
                ."\n\nในการส่งมอบงานแต่ละครั้ง {{party}}ต้องจัดส่งเอกสารประกอบการส่งมอบงานให้ครบถ้วนพร้อมหนังสือส่งมอบงานภายหลังจากดำเนินการแล้วเสร็จหรือหลักฐานอื่นใดที่เกี่ยวข้องตามที่ {{company_short}} กำหนด"
                ."\n\n{{party}}จะต้องส่งมอบงานตามข้อ 5 มาที่{{company_full}} เลขที่ 425/1 อาคารเอนโก้ เทอร์มินอล อาคารบี ชั้น 7 ถนนกำแพงเพชร 6 แขวงดอนเมือง เขตดอนเมือง กรุงเทพมหานคร 10210 หรือสถานที่ที่ {{company_short}} กำหนด ทั้งนี้ หาก{{party}}ส่งมอบเอกสารไม่ครบถ้วน ไม่ถูกต้อง หรือไม่เป็นไปตามข้อกำหนด ให้ถือว่าการส่งมอบงานในงวดนั้นยังไม่สมบูรณ์ และ {{company_short}} มีสิทธิไม่รับมอบงานหรือระงับการชำระเงินในงวดนั้นไว้จนกว่าจะได้รับเอกสารครบถ้วนถูกต้องตามที่กำหนด"
                ."\n\nทั้งนี้ ในการส่งมอบงานแต่ละงวด {{party}}ต้องส่งมอบงานต่อคณะกรรมการตรวจรับซึ่งเป็นผู้บริหารสัญญาตามที่แต่งตั้ง หรือตามที่ {{company_short}} กำหนด และต้องเป็นไปตามขอบเขตงานที่กำหนดเท่านั้น หากส่งมอบงานแก่บุคคลอื่นที่มิใช่ผู้มีอำนาจตรวจรับ จะไม่ถือว่าเป็นการส่งมอบงานโดยชอบตามสัญญา",
            'config' => $config,
        ];
    }

    private function warrantyGoods(string $number): array
    {
        return [
            'section_key' => 'warranty',
            'display_number' => $number,
            'title_th' => 'เงื่อนไขการรับประกันสินค้า',
            'section_type' => 'clause',
            'is_optional' => true,
            'config' => ['items' => [
                ['no' => $number.'.1', 'text' => 'สินค้าที่{{party}}ส่งมอบจะต้องมีคุณภาพไม่ต่ำกว่ารายละเอียดที่กำหนดไว้ในใบเสนอราคา/ใบสั่งซื้อ/สั่งจ้าง/สัญญา/หนังสือข้อตกลง และเอกสารแนบท้ายสัญญาทุกประการ โดยต้องเป็นของแท้ ของใหม่ ไม่เคยผ่านการใช้งานและไม่เป็นสินค้าค้างสต็อกหรือสินค้าที่มีตำหนิ'],
                ['no' => $number.'.2', 'text' => 'ในกรณีที่สินค้าเป็นประเภทที่ต้องมีการตรวจสอบ ทดสอบ หรือทดลองใช้งาน {{party}}ยินยอมรับรองว่าสินค้าดังกล่าวผ่านการตรวจสอบหรือทดสอบแล้ว จะต้องมีคุณภาพและคุณลักษณะไม่ต่ำกว่าที่กำหนดไว้ตามเอกสารที่เกี่ยวข้องทุกประการ'],
                ['no' => $number.'.3', 'text' => '{{company_short}} สงวนสิทธิ์ไม่รับมอบสินค้า หากปรากฏว่าสินค้ามีลักษณะชำรุด บกพร่อง เสียหาย หรือไม่เป็นไปตามรายละเอียดที่กำหนดไว้ในใบสั่งซื้อ/สั่งจ้าง/สัญญา/หนังสือข้อตกลงหรือเอกสารที่เกี่ยวข้อง ทั้งนี้ ไม่ว่าจะตรวจพบในขณะตรวจรับหรือภายหลังการตรวจรับก็ตาม {{party}}ต้องดำเนินการซ่อมแซม แก้ไข หรือเปลี่ยนสินค้าใหม่ให้ถูกต้องครบถ้วนตามระยะเวลาที่ {{company_short}} กำหนด นับตั้งแต่วันที่ได้รับแจ้งจาก {{company_short}} โดยไม่คิดค่าใช้จ่ายใดๆ ทั้งสิ้น'],
                ['no' => $number.'.4', 'text' => 'ในกรณีที่มีการซ่อมแซม แก้ไข หรือเปลี่ยนสินค้าใหม่ตามข้อ '.$number.'.3 ให้ระยะเวลาการรับประกันสำหรับสินค้าดังกล่าวเริ่มนับใหม่ตั้งแต่วันที่ {{company_short}} ได้รับมอบสินค้าที่ได้ซ่อมแซมหรือเปลี่ยนใหม่เรียบร้อยแล้ว'],
            ]],
        ];
    }

    private function warrantyRent(string $number): array
    {
        return [
            'section_key' => 'warranty',
            'display_number' => $number,
            'title_th' => 'เงื่อนไขการรับประกันการเช่า',
            'section_type' => 'clause',
            'is_optional' => true,
            // ต้นฉบับเว้นว่างให้กรอกเองตามงานเช่าแต่ละประเภท
            'config' => ['items' => [
                ['no' => $number.'.1', 'text' => ''],
                ['no' => $number.'.2', 'text' => ''],
                ['no' => $number.'.3', 'text' => ''],
            ]],
        ];
    }

    private function penalty(string $number): array
    {
        return [
            'section_key' => 'penalty',
            'display_number' => $number,
            'title_th' => 'อัตราค่าปรับ',
            'section_type' => 'clause',
            'body_default' => 'หาก{{party}}ส่งมอบงานล่าช้ากว่ากำหนดเวลาที่ตกลงกันไว้ {{party}}จะต้องชำระค่าปรับให้แก่ {{company_short}} เป็นเงินให้ใช้อัตราค่าปรับร้อยละ {{penalty_rate}} ต่อวันของ{{penalty_base}}ที่ยังไม่ได้รับมอบ นับถัดจากวันครบกำหนดส่งมอบสินค้าเป็นต้นไปจนถึงวันที่ {{company_short}} ได้รับมอบงานถูกต้องครบถ้วน'
                ."\n\nหมายเหตุ : การงดค่าปรับ การลดค่าปรับ หรือการขยายระยะเวลาปฏิบัติงานอันมีผลให้งดหรือลดค่าปรับจะกระทำได้เฉพาะกรณีที่มีเหตุอันสมควร มิใช่ความผิดของ{{party}} หรือเป็นเหตุสุดวิสัยที่{{party}}ไม่สามารถควบคุมหรือป้องกันได้เท่านั้น โดยคณะกรรมการตรวจรับหรือผู้ตรวจรับงานต้องพิจารณาข้อเท็จจริงและเสนอความเห็นพร้อมเหตุผลต่อผู้มีอำนาจอนุมัติการจัดหา (ใบสั่งซื้อ/ใบสั่งจ้าง/สัญญา/หนังสือข้อตกลง) เพื่อพิจารณาอนุมัติก่อนดำเนินการทุกครั้ง",
        ];
    }

    private function confidentiality(string $number): array
    {
        return [
            'section_key' => 'confidentiality',
            'display_number' => $number,
            'title_th' => 'การรักษาความลับของข้อมูล',
            'section_type' => 'clause',
            'config' => ['items' => [
                ['no' => $number.'.1', 'text' => '{{party}}ตกลงเก็บรักษาเอกสาร ข้อมูล หรือสารสนเทศใดๆ ที่ได้รับหรือรับทราบจากการปฏิบัติงานตามสัญญานี้ไว้เป็นความลับ และจะไม่เปิดเผยหรือส่งมอบแก่บุคคลภายนอก ทั้งนี้ กรรมสิทธิ์ของข้อมูลทั้งหมดยังคงเป็นของผู้จ้าง เว้นแต่ได้รับความยินยอมเป็นลายลักษณ์อักษรจาก {{company_short}} เท่านั้น'],
                ['no' => $number.'.2', 'text' => '{{party}}จะใช้ข้อมูลดังกล่าวเฉพาะเพื่อการปฏิบัติงานตามสัญญานี้ และจะไม่นำไปใช้เพื่อประโยชน์อื่นโดยมิชอบ'],
                ['no' => $number.'.3', 'text' => '{{party}}ต้องจัดให้มีมาตรการที่เหมาะสมเพื่อป้องกันการสูญหาย การเข้าถึง หรือการเปิดเผยข้อมูลโดยไม่ได้รับอนุญาต'],
                ['no' => $number.'.4', 'text' => 'หาก{{party}}ฝ่าฝืนข้อกำหนดนี้ {{party}}ต้องรับผิดชดใช้ค่าเสียหายที่เกิดขึ้นตามจริง และ {{company_short}} มีสิทธิบอกเลิกสัญญาได้ทันที'],
            ]],
        ];
    }

    private function termination(string $number): array
    {
        return [
            'section_key' => 'termination',
            'display_number' => $number,
            'title_th' => 'การบอกเลิกสัญญาและผลการเลิกสัญญา',
            'section_type' => 'clause',
            'body_default' => '{{company_short}} ขอสงวนสิทธิ์ในการบอกเลิกสัญญา และเรียกร้องค่าเสียหายอันเกิดจากการที่{{party}}ละเลยหรือไม่ปฏิบัติตามเงื่อนไขที่กำหนดไว้ในสัญญา'
                ."\n\nตามวรรคแรกในกรณีที่ {{company_short}} จำเป็นต้องจัดหาบริษัท กลุ่มบุคคล หรือบุคคลอื่น เพื่อดำเนินงานส่วนที่เหลือของงานไม่ว่าทั้งหมดหรือบางส่วน รวมถึงกรณีจัดหาเพื่อทดแทนงานที่ขาดส่ง {{party}}ตกลงยินยอมชดใช้ค่าใช้จ่ายส่วนต่างที่เพิ่มขึ้น ตลอดจนค่าเสียหายอื่นใดที่เกิดขึ้นแก่ {{company_short}}",
        ];
    }

    private function taxDuty(string $number): array
    {
        return [
            'section_key' => 'tax_duty',
            'display_number' => $number,
            'title_th' => 'ค่าภาษี ค่าอากร ค่าธรรมเนียมตามสัญญา',
            'section_type' => 'clause',
            'body_default' => '{{party}}เป็นผู้รับภาระภาษี ค่าอากรแสตมป์ (ถ้ามี) และค่าธรรมเนียมอื่น ๆ เว้นแต่ภาษีมูลค่าเพิ่ม ซึ่ง {{company_short}} เป็นผู้รับภาระ',
        ];
    }

    private function contractSecurity(string $number): array
    {
        return [
            'section_key' => 'contract_security',
            'display_number' => $number,
            'title_th' => 'หลักประกันสัญญา',
            'section_type' => 'clause',
            'body_default' => 'ยกเว้นหลักประกันสัญญา',
        ];
    }
}
