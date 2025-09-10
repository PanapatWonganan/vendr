<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorPerformanceReportResource\Pages;
use App\Models\Vendor;
use App\Models\VendorScore;
use App\Models\VendorEvaluation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Split;

class VendorPerformanceReportResource extends Resource
{
    protected static ?string $model = Vendor::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Vendor Performance Report (รายงานผลการดำเนินงานผู้ขาย)';
    protected static ?string $modelLabel = 'รายงานผลการดำเนินงานผู้ขาย';
    protected static ?string $pluralModelLabel = 'รายงานผลการดำเนินงานผู้ขาย';
    protected static ?string $navigationGroup = 'Master Data (ข้อมูลหลัก)';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $companyId = session('company_id');
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
            })
            ->columns([
                Split::make([
                    Stack::make([
                        Tables\Columns\TextColumn::make('company_name')
                            ->label('Company Name')
                            ->searchable()
                            ->weight(FontWeight::Bold)
                            ->color('primary'),
                        Tables\Columns\TextColumn::make('work_category')
                            ->label('Category')
                            ->badge()
                            ->color('gray'),
                    ]),
                    
                    Stack::make([
                        Tables\Columns\TextColumn::make('current_score')
                            ->label('Current Score')
                            ->state(function (Vendor $record) {
                                $score = VendorScore::where('vendor_id', $record->id)
                                    ->where('company_id', session('company_id'))
                                    ->whereNull('quarter')
                                    ->whereNull('month')
                                    ->latest('year')
                                    ->first();
                                return $score ? number_format($score->average_score, 2) : 'N/A';
                            })
                            ->weight(FontWeight::Bold)
                            ->color(function (Vendor $record) {
                                $score = VendorScore::where('vendor_id', $record->id)
                                    ->where('company_id', session('company_id'))
                                    ->whereNull('quarter')
                                    ->whereNull('month')
                                    ->latest('year')
                                    ->first();
                                if (!$score) return 'gray';
                                if ($score->average_score >= 3.5) return 'success';
                                if ($score->average_score >= 2.5) return 'warning';
                                return 'danger';
                            }),
                        Tables\Columns\TextColumn::make('current_grade')
                            ->label('Grade')
                            ->state(function (Vendor $record) {
                                $score = VendorScore::where('vendor_id', $record->id)
                                    ->where('company_id', session('company_id'))
                                    ->whereNull('quarter')
                                    ->whereNull('month')
                                    ->latest('year')
                                    ->first();
                                return $score ? $score->grade : 'N/A';
                            })
                            ->badge()
                            ->color(function (Vendor $record) {
                                $score = VendorScore::where('vendor_id', $record->id)
                                    ->where('company_id', session('company_id'))
                                    ->whereNull('quarter')
                                    ->whereNull('month')
                                    ->latest('year')
                                    ->first();
                                if (!$score) return 'gray';
                                return match($score->grade) {
                                    'A' => 'success',
                                    'B' => 'primary',
                                    'C' => 'warning',
                                    'D' => 'danger',
                                    default => 'gray'
                                };
                            }),
                    ])->alignEnd(),
                ]),
                
                Tables\Columns\TextColumn::make('evaluation_count')
                    ->label('Evaluations')
                    ->state(function (Vendor $record) {
                        return VendorEvaluation::where('vendor_id', $record->id)
                            ->where('company_id', session('company_id'))
                            ->count();
                    })
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('avg_overall_score')
                    ->label('Avg Score')
                    ->state(function (Vendor $record) {
                        $avg = VendorEvaluation::where('vendor_id', $record->id)
                            ->where('company_id', session('company_id'))
                            ->whereNotNull('overall_score')
                            ->avg('overall_score');
                        return $avg ? number_format($avg / 25, 1) : 'N/A'; // Convert from percentage to 4-point scale
                    }),
                    
                Tables\Columns\TextColumn::make('last_evaluation')
                    ->label('Last Evaluated')
                    ->state(function (Vendor $record) {
                        $lastEval = VendorEvaluation::where('vendor_id', $record->id)
                            ->where('company_id', session('company_id'))
                            ->latest()
                            ->first();
                        return $lastEval ? $lastEval->evaluation_date->format('d/m/Y') : 'Never';
                    })
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('grade')
                    ->label('Grade Filter')
                    ->options([
                        'A' => 'Grade A',
                        'B' => 'Grade B', 
                        'C' => 'Grade C',
                        'D' => 'Grade D',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        
                        $companyId = session('company_id');
                        $vendorIds = VendorScore::where('company_id', $companyId)
                            ->whereNull('quarter')
                            ->whereNull('month')
                            ->where('grade', $data['value'])
                            ->pluck('vendor_id');
                            
                        return $query->whereIn('id', $vendorIds);
                    }),
                    
                Tables\Filters\Filter::make('score_range')
                    ->form([
                        Forms\Components\TextInput::make('min_score')
                            ->label('Minimum Score')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('max_score')
                            ->label('Maximum Score')
                            ->numeric()
                            ->default(5),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $companyId = session('company_id');
                        $vendorIds = VendorScore::where('company_id', $companyId)
                            ->whereNull('quarter')
                            ->whereNull('month')
                            ->when($data['min_score'], fn($q) => $q->where('average_score', '>=', $data['min_score']))
                            ->when($data['max_score'], fn($q) => $q->where('average_score', '<=', $data['max_score']))
                            ->pluck('vendor_id');
                            
                        return $query->whereIn('id', $vendorIds);
                    }),
                    
                Tables\Filters\SelectFilter::make('work_category')
                    ->options(function () {
                        $companyId = session('company_id');
                        return Vendor::where('company_id', $companyId)
                            ->distinct()
                            ->pluck('work_category', 'work_category')
                            ->toArray();
                    }),
            ])
            ->actions([
                Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (Vendor $record) => 'Performance Details: ' . $record->company_name)
                    ->modalContent(function (Vendor $record) {
                        $evaluations = VendorEvaluation::where('vendor_id', $record->id)
                            ->where('company_id', session('company_id'))
                            ->latest()
                            ->limit(5)
                            ->get();
                            
                        $score = VendorScore::where('vendor_id', $record->id)
                            ->where('company_id', session('company_id'))
                            ->latest()
                            ->first();

                        return view('filament.pages.vendor-performance-details', [
                            'vendor' => $record,
                            'evaluations' => $evaluations,
                            'score' => $score,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->bulkActions([])
            ->defaultSort('company_name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorPerformanceReports::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function getEloquentQuery(): Builder
    {
        $companyId = session('company_id');
        return parent::getEloquentQuery()->where('company_id', $companyId);
    }
}