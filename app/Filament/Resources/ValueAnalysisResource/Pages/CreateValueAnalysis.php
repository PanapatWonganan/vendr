<?php

namespace App\Filament\Resources\ValueAnalysisResource\Pages;

use App\Filament\Resources\ValueAnalysisResource;
use App\Models\PurchaseRequisition;
use Filament\Resources\Pages\CreateRecord;

class CreateValueAnalysis extends CreateRecord
{
    protected static string $resource = ValueAnalysisResource::class;

    public function mount(): void
    {
        parent::mount();

        $prId = request()->get('purchase_requisition_id');
        if ($prId) {
            $pr = PurchaseRequisition::find($prId);
            if ($pr) {
                $budget = $pr->total_amount ?: $pr->procurement_budget ?: 0;

                $this->form->fill([
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
