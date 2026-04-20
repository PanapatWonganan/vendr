<?php

namespace App\Filament\Resources\TermsOfReferenceResource\Pages;

use App\Filament\Resources\TermsOfReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class EditTermsOfReference extends EditRecord
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        // Re-number items
        if (isset($data['items'])) {
            foreach ($data['items'] as $index => &$item) {
                $item['item_number'] = $index + 1;
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === 'draft' && (
                    $this->record->created_by === Auth::id()
                    || Auth::user()?->hasRole('admin')
                )),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'บันทึก TOR สำเร็จ';
    }
}
