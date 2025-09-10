<?php

namespace App\Filament\Resources\VendorEvaluationResource\Pages;

use App\Filament\Resources\VendorEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendorEvaluations extends ListRecords
{
    protected static string $resource = VendorEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
