<?php

namespace App\Filament\Resources\ValueAnalysisResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class VaComparisonWidget extends Widget
{
    protected static string $view = 'filament.resources.value-analysis-resource.widgets.va-comparison';

    public ?Model $record = null;

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $record = $this->record;
        if (!$record || !$record->isRevision()) {
            return ['changes' => null, 'parentVa' => null];
        }

        return [
            'changes' => $record->getChangesFromParent(),
            'parentVa' => $record->parentVa,
            'currentVa' => $record,
        ];
    }
}
