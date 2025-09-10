<?php

namespace App\Filament\Resources\PaymentMilestoneResource\Pages;

use App\Filament\Resources\PaymentMilestoneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentMilestones extends ListRecords
{
    protected static string $resource = PaymentMilestoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
