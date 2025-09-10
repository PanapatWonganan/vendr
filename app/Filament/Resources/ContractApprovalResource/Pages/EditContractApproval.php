<?php

namespace App\Filament\Resources\ContractApprovalResource\Pages;

use App\Filament\Resources\ContractApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContractApproval extends EditRecord
{
    protected static string $resource = ContractApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
