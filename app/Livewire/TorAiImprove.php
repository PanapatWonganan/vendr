<?php

namespace App\Livewire;

use App\Services\TorAiService;
use Livewire\Component;

class TorAiImprove extends Component
{
    public string $field = '';
    public string $content = '';
    public string $title = '';
    public string $torType = 'goods';
    public ?float $budgetEstimate = null;

    public bool $isLoading = false;
    public ?string $improvedContent = null;
    public ?string $errorMessage = null;

    protected array $fieldLabels = [
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

    public function improveField(): void
    {
        if (empty($this->field) || empty($this->content)) {
            $this->errorMessage = 'กรุณาระบุฟิลด์และเนื้อหา';
            return;
        }

        $this->isLoading = true;
        $this->errorMessage = null;
        $this->improvedContent = null;

        try {
            $service = app(TorAiService::class);
            $this->improvedContent = $service->improveField($this->field, $this->content, [
                'title' => $this->title,
                'tor_type' => $this->torType,
                'budget_estimate' => $this->budgetEstimate,
            ]);

            $this->dispatch('tor-field-improved', field: $this->field, value: $this->improvedContent);
        } catch (\Exception $e) {
            $this->errorMessage = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }

        $this->isLoading = false;
    }

    public function applyImproved(): void
    {
        if ($this->improvedContent) {
            $this->dispatch('tor-apply-field', field: $this->field, value: $this->improvedContent);
        }
    }

    public function getFieldLabel(): string
    {
        return $this->fieldLabels[$this->field] ?? $this->field;
    }

    public function render()
    {
        return view('livewire.tor-ai-improve');
    }
}
