<?php

namespace App\Filament\Resources\TermsOfReferenceResource\Pages;

use App\Filament\Resources\TermsOfReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTermsOfReferences extends ListRecords
{
    protected static string $resource = TermsOfReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('สร้าง TOR ใหม่'),
        ];
    }
}
