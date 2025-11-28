<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SlaReportResource\Pages;
use App\Models\SlaTracking;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SlaReportResource extends Resource
{
    protected static ?string $model = SlaTracking::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'SLA Reports';
    protected static ?string $navigationGroup = 'Reports & Analytics';
    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        $companyId = session('company_id');
        return parent::getEloquentQuery()->when($companyId, fn($query) => $query->where('company_id', $companyId));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('purchaseRequisition.pr_number')
                    ->label('PR Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stage')
                    ->label('Stage')
                    ->formatStateUsing(fn($record) => $record->getStageName())
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('procurement_method')
                    ->label('Method')
                    ->formatStateUsing(fn($state) => match($state) {
                        'agreement_price' => 'ตกลงราคา',
                        'invitation_bid' => 'ประมูลเชิญ',
                        'open_bid' => 'ประกาศทั่วไป',
                        'special_1' => 'พิเศษ ข้อ 1',
                        'special_2' => 'พิเศษ ข้อ 2',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('sla_standard_days')
                    ->label('SLA Standard')
                    ->suffix(' days')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('actual_working_days')
                    ->label('Actual Days')
                    ->suffix(' days')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sla_percentage')
                    ->label('Performance')
                    ->suffix('%')
                    ->alignCenter()
                    ->color(fn($record) => $record->sla_percentage <= 100 ? 'success' : 'danger'),

                Tables\Columns\BadgeColumn::make('sla_grade')
                    ->label('Grade')
                    ->formatStateUsing(fn($record) => $record->sla_grade . ' - ' . $record->getGradeLabel())
                    ->color(fn($record) => $record->getGradeColor()),

                Tables\Columns\TextColumn::make('days_difference')
                    ->label('Diff')
                    ->formatStateUsing(fn($state) => ($state > 0 ? '+' : '') . $state . ' days')
                    ->color(fn($state) => $state <= 0 ? 'success' : 'danger'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'on_time',
                        'danger' => 'late',
                        'warning' => 'in_progress',
                    ]),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('End')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sla_grade')
                    ->label('Grade')
                    ->options([
                        'S' => 'S - Excellent',
                        'A' => 'A - Very Good',
                        'B' => 'B - Good',
                        'C' => 'C - Average',
                        'D' => 'D - Below Average',
                        'F' => 'F - Fail',
                    ]),

                Tables\Filters\SelectFilter::make('stage')
                    ->options([
                        'pr_submission_to_approval' => 'PR Submission → Approval',
                        'po_creation_to_approval' => 'PO Creation → Approval',
                        'full_cycle' => 'Full Cycle',
                    ]),

                Tables\Filters\SelectFilter::make('procurement_method')
                    ->options([
                        'agreement_price' => 'ตกลงราคา',
                        'invitation_bid' => 'ประมูลเชิญ',
                        'open_bid' => 'ประกาศทั่วไป',
                        'special_1' => 'พิเศษ ข้อ 1',
                        'special_2' => 'พิเศษ ข้อ 2',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'on_time' => 'On Time',
                        'late' => 'Late',
                        'in_progress' => 'In Progress',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSlaReports::route('/'),
        ];
    }
}
