<?php

namespace App\Filament\Resources\ValueAnalysisResource\Pages;

use App\Filament\Resources\ValueAnalysisResource;
use App\Models\PurchaseRequisition;
use App\Models\ValueAnalysis;
use Filament\Resources\Pages\CreateRecord;

class CreateValueAnalysis extends CreateRecord
{
    protected static string $resource = ValueAnalysisResource::class;

    public function mount(): void
    {
        parent::mount();

        $prId = request()->get('purchase_requisition_id');
        $parentVaId = request()->get('parent_va_id');

        if ($parentVaId) {
            // Creating a revision from parent VA
            $parentVa = ValueAnalysis::find($parentVaId);
            if ($parentVa) {
                $originalVaId = $parentVa->parent_va_id ?? $parentVa->id;
                $nextRevision = ValueAnalysis::where('parent_va_id', $originalVaId)
                    ->max('revision_number') ?? $parentVa->revision_number;

                $this->form->fill([
                    'va_number' => ValueAnalysis::generateVANumber(),
                    'parent_va_id' => $originalVaId,
                    'revision_number' => $nextRevision + 1,
                    'purchase_requisition_id' => $parentVa->purchase_requisition_id,
                    'work_type' => $parentVa->work_type,
                    'procurement_method' => $parentVa->procurement_method,
                    'procured_from' => $parentVa->procured_from,
                    'total_budget' => $parentVa->total_budget,
                    'agreed_amount' => $parentVa->agreed_amount,
                    'currency' => $parentVa->currency ?? 'THB',
                    'recommendations' => $parentVa->recommendations,
                    'conclusion' => $parentVa->conclusion,
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);
            }
        } elseif ($prId) {
            $pr = PurchaseRequisition::find($prId);
            if ($pr) {
                $budget = $pr->total_amount ?: $pr->procurement_budget ?: 0;

                $this->form->fill([
                    'va_number' => ValueAnalysis::generateVANumber(),
                    'purchase_requisition_id' => $pr->id,
                    'work_type' => $pr->work_type,
                    'procurement_method' => $pr->procurement_method,
                    'total_budget' => $budget,
                    'agreed_amount' => $budget,
                    'currency' => $pr->currency ?? 'THB',
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);
            }
        }
    }
}
