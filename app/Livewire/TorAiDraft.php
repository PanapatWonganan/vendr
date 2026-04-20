<?php

namespace App\Livewire;

use App\Services\TorAiService;
use Livewire\Component;

class TorAiDraft extends Component
{
    public string $title = '';
    public string $torType = 'goods';
    public string $workType = '';
    public string $procurementMethod = '';
    public ?float $budgetEstimate = null;
    public string $department = '';
    public string $category = '';

    public bool $isLoading = false;
    public ?array $result = null;
    public ?string $errorMessage = null;

    public function generateDraft(): void
    {
        $this->isLoading = true;
        $this->errorMessage = null;
        $this->result = null;

        try {
            $service = app(TorAiService::class);
            $this->result = $service->generateDraft([
                'title' => $this->title,
                'tor_type' => $this->torType,
                'work_type' => $this->workType,
                'procurement_method' => $this->procurementMethod,
                'budget_estimate' => $this->budgetEstimate,
                'department' => $this->department,
                'category' => $this->category,
            ]);

            $this->dispatch('tor-draft-generated', draft: $this->result);
        } catch (\Exception $e) {
            $this->errorMessage = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }

        $this->isLoading = false;
    }

    public function applyField(string $field): void
    {
        if ($this->result && isset($this->result[$field])) {
            $this->dispatch('tor-apply-field', field: $field, value: $this->result[$field]);
        }
    }

    public function applyAll(): void
    {
        if ($this->result) {
            $this->dispatch('tor-apply-all', draft: $this->result);
        }
    }

    public function render()
    {
        return view('livewire.tor-ai-draft');
    }
}
