<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\Department;
use App\Models\TermsOfReference;
use App\Models\TorTemplate;
use App\Services\TorDocumentService;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * TOR Document Builder (Beta) — สร้าง/แก้ไขเอกสาร TOR ตามแบบฟอร์มกระดาษของลูกค้า
 * (Workflow.docx 2026-08). เอกสารถูกเก็บเป็น JSON ใน terms_of_references.document_sections
 *
 * Header/setup ใช้ Filament form; ตัวเอกสารเป็น custom Blade + wire:model บน $sections
 * เพื่อรองรับ "เพิ่มหัวข้อใหม่" แบบ nested ทุกจุดตาม spec
 */
class TorBuilder extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationGroup = 'Procurement Management';

    protected static ?string $navigationLabel = 'สร้าง TOR';

    protected static ?string $title = 'เอกสาร TOR (Document Builder)';

    protected static ?string $slug = 'tor-builder';

    protected static string $view = 'filament.pages.tor-builder';

    public ?array $data = [];

    /** Document snapshot (document_sections JSON) — แก้ไขสดผ่าน wire:model */
    public array $sections = [];

    public ?int $torId = null;

    public ?string $torStatus = null;

    public function mount(): void
    {
        $torId = (int) request()->query('tor', 0);

        if ($torId > 0) {
            $tor = TermsOfReference::where('company_id', session('company_id'))->findOrFail($torId);
            $this->torId = $tor->id;
            $this->torStatus = $tor->status;
            $this->sections = $tor->document_sections ?? [];
            $preamble = collect($tor->document_sections ?? [])->firstWhere('key', 'preamble');
            $this->form->fill([
                'title' => $tor->title,
                'department_id' => $tor->department_id,
                'procurement_type' => $tor->procurement_type,
                'form_category' => $tor->form_category,
                'procurement_method' => $tor->procurement_method,
                'responsible_name' => $preamble['data']['responsible_name'] ?? null,
                'budget_estimate' => $tor->budget_estimate,
                'currency' => $tor->currency ?? 'THB',
            ]);

            return;
        }

        $this->form->fill(['form_category' => 'act_based', 'currency' => 'THB']);
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Section::make('ตั้งค่าเอกสาร')
                ->description('เลือกประเภทแล้วกด "สร้างเอกสารจาก Template" หรือเลือกคัดลอกจาก TOR เดิม')
                ->collapsible()
                ->collapsed(fn () => ! empty($this->sections))
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('title')
                            ->label('ชื่อเรื่อง')
                            ->required()
                            ->columnSpan(3),
                        Select::make('department_id')
                            ->label('หน่วยงาน')
                            ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('responsible_name')
                            ->label('ผู้รับผิดชอบกำหนดร่างขอบเขตงาน')
                            ->placeholder('ชื่อ-นามสกุล'),
                        TextInput::make('budget_estimate')
                            ->label('วงเงินงบประมาณ (ก่อน VAT)')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        Select::make('currency')
                            ->label('สกุลเงิน')
                            ->options(['THB' => 'THB', 'USD' => 'USD', 'EUR' => 'EUR', 'JPY' => 'JPY', 'CNY' => 'CNY'])
                            ->default('THB')
                            ->required(),
                        Select::make('procurement_type')
                            ->label('ประเภทการจัดซื้อจัดจ้าง')
                            ->options(TorTemplate::TYPES)
                            ->required(),
                        Select::make('form_category')
                            ->label('แบบฟอร์ม')
                            ->options([
                                'act_based' => 'แบบฟอร์มเชิงพาณิชย์',
                                'law_based' => 'พ.ร.บ.จัดซื้อจัดจ้างภาครัฐฯ',
                            ]),
                        Select::make('procurement_method')
                            ->label('วิธีจัดซื้อจัดจ้าง')
                            ->options([
                                'agreement_price' => 'วิธีตกลงราคา',
                                'special_1' => 'วิธีพิเศษ',
                                'open_bid' => 'วิธีประมูล',
                                'selection' => 'วิธีคัดเลือก',
                                'invitation_bid' => 'วิธีประมูลโดยเชิญ',
                            ]),
                        Select::make('copy_from')
                            ->label('คัดลอกจาก TOR เดิม (ถ้ามี)')
                            ->options(fn () => TermsOfReference::query()
                                ->where('company_id', session('company_id'))
                                ->whereNotNull('document_sections')
                                ->orderByDesc('id')
                                ->limit(100)
                                ->pluck('title', 'id'))
                            ->searchable()
                            ->placeholder('— สร้างใหม่จาก template —'),
                    ]),
                ]),
        ]);
    }

    /** สร้าง document snapshot จาก template (หรือ copy จาก TOR เดิม) */
    public function loadTemplate(): void
    {
        // อ่านจาก raw state โดยตรง — เลี่ยงการ build form ก่อน mutate (schema cache)
        $type = $this->data['procurement_type'] ?? null;
        $copyFrom = $this->data['copy_from'] ?? null;

        if ($copyFrom) {
            $source = TermsOfReference::where('company_id', session('company_id'))->find($copyFrom);
            if ($source && ! empty($source->document_sections)) {
                $this->sections = $source->document_sections;
                Notification::make()->title('คัดลอกเอกสารจาก TOR เดิมแล้ว')->success()->send();

                return;
            }
            Notification::make()->title('TOR ที่เลือกไม่มีข้อมูลเอกสาร')->warning()->send();

            return;
        }

        if (! $type) {
            Notification::make()->title('กรุณาเลือกประเภทการจัดซื้อจัดจ้างก่อน')->warning()->send();

            return;
        }

        $template = TorTemplate::where('code', $type)->where('is_active', true)->with('sections')->first();
        if (! $template) {
            Notification::make()->title("ไม่พบ template สำหรับประเภท {$type}")->danger()->send();

            return;
        }

        $company = Company::find(session('company_id'));
        $this->sections = app(TorDocumentService::class)->buildForCompany($template, $company);

        Notification::make()->title('สร้างเอกสารจาก template แล้ว — แก้ไขเนื้อหาได้ทุกจุด')->success()->send();
    }

    public function save(): void
    {
        $state = $this->form->getState();

        if (empty($this->sections)) {
            Notification::make()->title('ยังไม่ได้สร้างเอกสาร — กด "สร้างเอกสารจาก Template" ก่อน')->warning()->send();

            return;
        }

        $errors = app(TorDocumentService::class)->validate($this->sections);
        if (! empty($errors)) {
            Notification::make()
                ->title('เอกสารยังไม่ผ่านการตรวจสอบ')
                ->body(implode("\n", $errors))
                ->danger()
                ->send();

            return;
        }

        $template = TorTemplate::where('code', $state['procurement_type'] ?? '')->first();

        // เก็บผู้รับผิดชอบร่างขอบเขตงานไว้กับ preamble (แสดงในหัวเอกสาร)
        foreach ($this->sections as $i => $section) {
            if (($section['key'] ?? '') === 'preamble') {
                $this->sections[$i]['data']['responsible_name'] = $state['responsible_name'] ?? null;
            }
        }

        // ดึงช่วงวันที่จาก section ระยะเวลา ลงคอลัมน์ start/end เดิม (SLA/รายงานใช้ต่อได้)
        $timeline = collect($this->sections)->firstWhere('type', 'timeline')['data'] ?? [];
        $startDate = ($timeline['mode'] ?? null) === 'date_range' ? ($timeline['start_date'] ?? null) : null;
        $endDate = match ($timeline['mode'] ?? null) {
            'date_range' => $timeline['end_date'] ?? null,
            'from_signing' => $timeline['until_date'] ?? null,
            default => null,
        };

        $payload = [
            'title' => $state['title'],
            'department_id' => $state['department_id'],
            'budget_estimate' => $state['budget_estimate'] ?? null,
            'currency' => $state['currency'] ?? 'THB',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'procurement_type' => $state['procurement_type'] ?? null,
            'form_category' => $state['form_category'] ?? null,
            'procurement_method' => $state['procurement_method'] ?? null,
            'tor_template_id' => $template?->id,
            'party_term' => $template?->party_term,
            'document_sections' => $this->sections,
            'scope_of_work' => app(TorDocumentService::class)->flattenScope($this->sections),
        ];

        if ($this->torId) {
            $tor = TermsOfReference::where('company_id', session('company_id'))->findOrFail($this->torId);
            $tor->update($payload + ['updated_by' => auth()->id()]);
        } else {
            $tor = TermsOfReference::create($payload + [
                'tor_number' => TermsOfReference::generateTorNumber(),
                'status' => TermsOfReference::STATUS_DRAFT,
                'tor_type' => 'services',
                'work_type' => $template?->party_term === 'ผู้ขาย' ? 'buy' : ($template?->party_term === 'ผู้ให้เช่า' ? 'rent' : 'hire'),
                'created_by' => auth()->id(),
            ]);
            $this->torId = $tor->id;
        }
        $this->torStatus = $tor->status;

        Notification::make()->title("บันทึกแล้ว ({$tor->tor_number})")->success()->send();
    }

    /** ส่ง TOR เข้าสู่ workflow พิจารณา (draft → submitted + notification เดิม) */
    public function submitTor(): void
    {
        if (! $this->torId) {
            return;
        }
        $tor = TermsOfReference::where('company_id', session('company_id'))->findOrFail($this->torId);
        if (! $tor->submit(auth()->user())) {
            Notification::make()->title('ส่งพิจารณาได้เฉพาะ TOR สถานะ draft')->warning()->send();

            return;
        }
        \App\Events\TorSubmitted::dispatch($tor, auth()->user());
        $this->torStatus = $tor->status;
        Notification::make()->title("ส่ง TOR เข้าพิจารณาแล้ว ({$tor->tor_number})")->success()->send();
    }

    public function getPreviewUrlProperty(): ?string
    {
        return $this->torId ? route('tor-builder.preview', $this->torId) : null;
    }

    public function getPdfUrlProperty(): ?string
    {
        return $this->torId ? route('tor-builder.pdf', $this->torId) : null;
    }

    // ------------------------------------------------------------------
    // "เพิ่มหัวข้อใหม่" / ซ่อนหัวข้อ — ทำงานบน $sections โดยตรง
    // ------------------------------------------------------------------

    public function toggleSection(int $si): void
    {
        $this->sections[$si]['hidden'] = ! ($this->sections[$si]['hidden'] ?? false);
    }

    public function addItem(int $si, ?int $parentIdx = null): void
    {
        $section = &$this->sections[$si];
        $base = $section['number'] ?? '';

        if ($parentIdx === null) {
            $items = $section['data']['items'] ?? [];
            $items[] = ['no' => $base.'.'.(count($items) + 1), 'text' => ''];
            $section['data']['items'] = $items;

            return;
        }

        $parent = &$section['data']['items'][$parentIdx];
        $children = $parent['children'] ?? [];
        $children[] = ['no' => ($parent['no'] ?? '').'.'.(count($children) + 1), 'text' => ''];
        $parent['children'] = $children;
    }

    public function removeItem(int $si, int $ii, ?int $ci = null): void
    {
        if ($ci === null) {
            unset($this->sections[$si]['data']['items'][$ii]);
            $this->sections[$si]['data']['items'] = array_values($this->sections[$si]['data']['items']);

            return;
        }
        unset($this->sections[$si]['data']['items'][$ii]['children'][$ci]);
        $this->sections[$si]['data']['items'][$ii]['children'] = array_values($this->sections[$si]['data']['items'][$ii]['children']);
    }

    public function addRow(int $si, string $listKey): void
    {
        $rows = $this->sections[$si]['data'][$listKey] ?? [];
        $rows[] = match ($listKey) {
            'documents' => ['name' => '', 'milestone_ref' => ''],
            'committee' => ['name' => '', 'phone' => '', 'email' => ''],
            default => [],
        };
        $this->sections[$si]['data'][$listKey] = $rows;
    }

    public function removeRow(int $si, string $listKey, int $ri): void
    {
        unset($this->sections[$si]['data'][$listKey][$ri]);
        $this->sections[$si]['data'][$listKey] = array_values($this->sections[$si]['data'][$listKey]);
    }

    public function addInstallment(int $si): void
    {
        foreach ($this->sections[$si]['data']['options'] ?? [] as $oi => $option) {
            if (($option['key'] ?? '') === 'installments') {
                $rows = $option['rows'] ?? [];
                $rows[] = ['no' => count($rows) + 1, 'percent' => null];
                $this->sections[$si]['data']['options'][$oi]['rows'] = $rows;
                $this->sections[$si]['data']['options'][$oi]['total'] = count($rows);
            }
        }
    }

    public function removeInstallment(int $si, int $ri): void
    {
        foreach ($this->sections[$si]['data']['options'] ?? [] as $oi => $option) {
            if (($option['key'] ?? '') === 'installments') {
                $rows = $option['rows'] ?? [];
                unset($rows[$ri]);
                $rows = array_values($rows);
                foreach ($rows as $i => &$row) {
                    $row['no'] = $i + 1;
                }
                $this->sections[$si]['data']['options'][$oi]['rows'] = $rows;
                $this->sections[$si]['data']['options'][$oi]['total'] = count($rows);
            }
        }
    }

    /** ผลรวม % การชำระเงิน (แสดงสดใน UI) */
    public function getPaymentTotalProperty(): float
    {
        foreach ($this->sections as $section) {
            if (($section['type'] ?? '') === 'payment') {
                return app(TorDocumentService::class)->paymentTotal($section['data'] ?? []);
            }
        }

        return 0.0;
    }
}
