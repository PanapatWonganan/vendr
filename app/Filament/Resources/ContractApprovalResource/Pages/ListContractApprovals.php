<?php

namespace App\Filament\Resources\ContractApprovalResource\Pages;

use App\Filament\Resources\ContractApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContractApprovals extends ListRecords
{
    protected static string $resource = ContractApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
