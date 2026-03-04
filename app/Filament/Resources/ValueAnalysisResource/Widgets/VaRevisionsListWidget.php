<?php

namespace App\Filament\Resources\ValueAnalysisResource\Widgets;

use App\Filament\Resources\ValueAnalysisResource;
use App\Models\ValueAnalysis;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class VaRevisionsListWidget extends BaseWidget
{
    public ?Model $record = null;

    protected static ?string $heading = 'ประวัติ VA Revision';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ValueAnalysis::query()
                    ->where('parent_va_id', $this->record?->id)
                    ->orderBy('revision_number', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('va_number')
                    ->label('VA Number'),
                Tables\Columns\TextColumn::make('revision_number')
                    ->label('Rev.')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('amendment_type')
                    ->label('ประเภทแก้ไข')
                    ->getStateUsing(fn ($record) => $record->amendment_type_label),
                Tables\Columns\TextColumn::make('agreed_amount')
                    ->label('วงเงินที่ตกลง')
                    ->money('THB'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'in_progress',
                        'info' => 'completed',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('สร้างโดย'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('วันที่สร้าง')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('ดู')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => ValueAnalysisResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
