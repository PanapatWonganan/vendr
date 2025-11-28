<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ValueAnalysisResource\Pages;
use App\Filament\Resources\ValueAnalysisResource\RelationManagers;
use App\Models\ValueAnalysis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ValueAnalysisResource extends Resource
{
    protected static ?string $model = ValueAnalysis::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Vendor Approve';
    protected static ?string $pluralModelLabel = 'Vendor Approve';
    protected static ?string $navigationGroup = 'Procurement Management';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('va_number')
                    ->label('VA Number')
                    ->default(fn () => \App\Models\ValueAnalysis::generateVANumber())
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('purchase_requisition_id')
                    ->label('เลือก Purchase Requisition')
                    ->relationship(
                        name: 'purchaseRequisition',
                        titleAttribute: 'pr_number',
                        modifyQueryUsing: fn (Builder $query) => 
                            $query->when(
                                session('company_id'),
                                fn ($q, $companyId) => $q->where('company_id', $companyId)
                            )
                    )
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        $title = !empty($record->title) ? " - {$record->title}" : '';
                        return $record->pr_number . $title;
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $pr = \App\Models\PurchaseRequisition::find($state);
                            if ($pr) {
                                $set('work_type', $pr->work_type);
                                $set('procurement_method', $pr->procurement_method);
                                $set('total_budget', $pr->total_amount ?: $pr->procurement_budget);
                            }
                        }
                    })
                    ->required(),
                Forms\Components\TextInput::make('work_type')
                    ->label('Work Type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('procurement_method')
                    ->label('Procurement Method')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Textarea::make('procured_from')
                    ->label('Procured From (Vendor/Supplier)')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('total_budget')
                    ->label('Original Budget (THB)')
                    ->numeric()
                    ->prefix('₿')
                    ->default(null),
                Forms\Components\TextInput::make('agreed_amount')
                    ->label('Negotiated Amount (THB)')
                    ->numeric()
                    ->prefix('₿')
                    ->default(null),
                Forms\Components\TextInput::make('currency')
                    ->required()
                    ->maxLength(3)
                    ->default('THB'),
                Forms\Components\Textarea::make('analysis_objective')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('analysis_scope')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('evaluation_criteria')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('alternatives')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('comparison_matrix')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('recommendations')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('conclusion')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('draft')
                    ->required(),
                Forms\Components\Hidden::make('created_by')
                    ->default(fn () => auth()->id())
                    ->required(),
                Forms\Components\Select::make('analyzed_by')
                    ->relationship('analyzer', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('approved_by')
                    ->relationship('approver', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\DateTimePicker::make('analysis_date')
                    ->label('Analysis Date'),
                Forms\Components\DateTimePicker::make('approved_at')
                    ->label('Approved Date'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $companyId = session('company_id');
                if ($companyId) {
                    $query->whereHas('purchaseRequisition', function ($q) use ($companyId) {
                        $q->where('company_id', $companyId);
                    });
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('va_number')
                    ->label('VA Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseRequisition.title')
                    ->label('Purchase Requisition')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('work_type')
                    ->label('Work Type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_budget')
                    ->label('Original Budget')
                    ->money('THB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('agreed_amount')
                    ->label('Negotiated Amount')
                    ->money('THB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('savings_percentage')
                    ->label('Savings %')
                    ->getStateUsing(function ($record) {
                        if ($record->total_budget && $record->agreed_amount && $record->total_budget > 0) {
                            $savings = (($record->total_budget - $record->agreed_amount) / $record->total_budget) * 100;
                            return round($savings, 2) . '%';
                        }
                        return 'N/A';
                    })
                    ->color(fn ($state) => $state !== 'N/A' && floatval($state) > 0 ? 'success' : 'gray')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'in_progress',
                        'info' => 'completed',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('analysis_date')
                    ->label('Analysis Date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListValueAnalyses::route('/'),
            'create' => Pages\CreateValueAnalysis::route('/create'),
            'edit' => Pages\EditValueAnalysis::route('/{record}/edit'),
        ];
    }
}
