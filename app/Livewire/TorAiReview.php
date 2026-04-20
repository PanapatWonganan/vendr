<?php

namespace App\Livewire;

use App\Models\TermsOfReference;
use App\Services\TorAiService;
use Livewire\Component;

class TorAiReview extends Component
{
    public ?int $torId = null;
    public bool $isLoading = false;
    public ?array $result = null;
    public ?string $errorMessage = null;

    public function reviewTor(): void
    {
        if (!$this->torId) {
            $this->errorMessage = 'กรุณาบันทึก TOR ก่อนตรวจสอบ';
            return;
        }

        $this->isLoading = true;
        $this->errorMessage = null;
        $this->result = null;

        try {
            $tor = TermsOfReference::find($this->torId);
            if (!$tor) {
                $this->errorMessage = 'ไม่พบ TOR';
                $this->isLoading = false;
                return;
            }

            $service = app(TorAiService::class);
            $this->result = $service->reviewTor($tor);
        } catch (\Exception $e) {
            $this->errorMessage = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }

        $this->isLoading = false;
    }

    public function render()
    {
        return view('livewire.tor-ai-review');
    }
}
