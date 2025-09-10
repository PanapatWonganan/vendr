<?php

namespace App\Filament\Resources\ValueAnalysisResource\Pages;

use App\Filament\Resources\ValueAnalysisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditValueAnalysis extends EditRecord
{
    protected static string $resource = ValueAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
