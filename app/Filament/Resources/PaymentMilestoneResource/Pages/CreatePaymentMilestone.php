<?php

namespace App\Filament\Resources\PaymentMilestoneResource\Pages;

use App\Filament\Resources\PaymentMilestoneResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentMilestone extends CreateRecord
{
    protected static string $resource = PaymentMilestoneResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default milestone_title if not provided
        if (empty($data['milestone_title'])) {
            $milestoneNumber = $data['milestone_number'] ?? 1;
            $data['milestone_title'] = "งวดที่ {$milestoneNumber}";
        }
        
        // Set default due_date if not provided
        if (empty($data['due_date'])) {
            $data['due_date'] = now()->addDays(30)->format('Y-m-d');
        }
        
        // Set created_by
        $data['created_by'] = auth()->id();
        
        return $data;
    }
}
