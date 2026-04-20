<?php

namespace App\Filament\Resources\TermsOfReferenceResource\Pages;

use App\Filament\Resources\TermsOfReferenceResource;
use App\Models\TermsOfReference;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class CreateTermsOfReference extends CreateRecord
{
    protected static string $resource = TermsOfReferenceResource::class;

    /**
     * Apply a single AI-generated field to the form.
     */
    #[On('tor-apply-field')]
    public function applyTorField(string $field, mixed $value): void
    {
        $allowedFields = [
            'background', 'objectives', 'scope_of_work', 'deliverables',
            'specifications', 'qualification_requirements', 'payment_terms',
            'warranty_requirements', 'penalty_clause',
        ];

        if (in_array($field, $allowedFields) && is_string($value)) {
            $this->data[$field] = nl2br(e($value));
        }

        if ($field === 'evaluation_criteria' && is_array($value)) {
            $this->data['evaluation_criteria'] = $value;
        }
    }

    /**
     * Apply all AI-generated fields to the form at once.
     */
    #[On('tor-apply-all')]
    public function applyTorAll(array $draft): void
    {
        $textFields = [
            'background', 'objectives', 'scope_of_work', 'deliverables',
            'specifications', 'qualification_requirements', 'payment_terms',
            'warranty_requirements', 'penalty_clause',
        ];

        foreach ($textFields as $field) {
            if (!empty($draft[$field]) && is_string($draft[$field])) {
                $this->data[$field] = nl2br(e($draft[$field]));
            }
        }

        if (!empty($draft['evaluation_criteria']) && is_array($draft['evaluation_criteria'])) {
            $this->data['evaluation_criteria'] = $draft['evaluation_criteria'];
        }

        if (!empty($draft['suggested_duration_days'])) {
            $this->data['duration_days'] = (int) $draft['suggested_duration_days'];
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('company_id') ?? 1;
        $data['tor_number'] = TermsOfReference::generateTorNumber();
        $data['created_by'] = Auth::id();
        $data['status'] = 'draft';
        $data['currency'] = $data['currency'] ?? 'THB';

        // Auto-number items
        if (isset($data['items'])) {
            foreach ($data['items'] as $index => &$item) {
                $item['item_number'] = $index + 1;
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Recalculate budget if items exist
        $totalEstimate = $this->record->items()->sum('estimated_total_price');
        if ($totalEstimate > 0 && !$this->record->budget_estimate) {
            $this->record->update(['budget_estimate' => $totalEstimate]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'สร้าง TOR สำเร็จ';
    }
}
