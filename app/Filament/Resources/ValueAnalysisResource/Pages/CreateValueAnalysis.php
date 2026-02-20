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
            $pr = PurchaseRequisition::with('items')->find($prId);
            if ($pr) {
                $vaItems = $pr->items->map(function ($item, $index) {
                    $estAmount = $item->estimated_amount ?? ($item->quantity * ($item->estimated_unit_price ?? 0));
                    return [
                        'purchase_requisition_item_id' => $item->id,
                        'line_number' => $index + 1,
                        'item_code' => $item->item_code,
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit_of_measure' => $item->unit_of_measure,
                        'estimated_unit_price' => $item->estimated_unit_price ?? 0,
                        'estimated_amount' => $estAmount,
                        'agreed_unit_price' => $item->estimated_unit_price ?? 0,
                        'agreed_amount' => $estAmount,
                        'remarks' => null,
                    ];
                })->toArray();

                $this->form->fill([
                    'purchase_requisition_id' => $pr->id,
                    'work_type' => $pr->work_type,
                    'procurement_method' => $pr->procurement_method,
                    'total_budget' => $pr->total_amount ?: $pr->procurement_budget,
                    'agreed_amount' => collect($vaItems)->sum('agreed_amount'),
                    'currency' => $pr->currency ?? 'THB',
                    'items' => $vaItems,
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);
            }
        }
    }
}
