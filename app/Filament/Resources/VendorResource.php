<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorResource\Pages;
use App\Filament\Resources\VendorResource\RelationManagers;
use App\Models\Vendor;
use App\Models\VendorScore;
use App\Services\VendorScoreService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use App\Models\VendorAssessment;
use App\Services\VendorRiskAssessmentService;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Vendors / Suppliers';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'procurement_officer', 'procurement_manager', 'auditor']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('company_id')
                    ->default(fn () => session('company_id'))
                    ->required(),
                Forms\Components\TextInput::make('company_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('tax_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('work_category')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('experience')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('contact_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_phone')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        Vendor::STATUS_PENDING => 'รอดำเนินการ',
                        Vendor::STATUS_APPROVED => 'อนุมัติแล้ว',
                        Vendor::STATUS_REJECTED => 'ปฏิเสธ',
                        Vendor::STATUS_SUSPENDED => 'ระงับ',
                    ])
                    ->default(Vendor::STATUS_PENDING),
                Forms\Components\FileUpload::make('documents')
                    ->label('เอกสาร')
                    ->multiple()
                    ->directory('vendor-documents')
                    ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->maxSize(10240) // 10MB per file
                    ->downloadable()
                    ->openable()
                    ->reorderable()
                    ->columnSpanFull(),
            ]);
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
                Tables\Columns\TextColumn::make('company_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('work_category')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact_phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact_email')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        Vendor::STATUS_PENDING => 'รอดำเนินการ',
                        Vendor::STATUS_APPROVED => 'อนุมัติแล้ว', 
                        Vendor::STATUS_REJECTED => 'ปฏิเสธ',
                        Vendor::STATUS_SUSPENDED => 'ระงับ',
                        default => $state
                    })
                    ->color(fn (string $state): string => match($state) {
                        Vendor::STATUS_PENDING => 'warning',
                        Vendor::STATUS_APPROVED => 'success',
                        Vendor::STATUS_REJECTED => 'danger', 
                        Vendor::STATUS_SUSPENDED => 'secondary',
                        default => 'gray'
                    }),
                Tables\Columns\TextColumn::make('current_score')
                    ->label('คะแนนปัจจุบัน')
                    ->state(function (Vendor $record) {
                        $score = VendorScore::where('vendor_id', $record->id)
                            ->where('company_id', session('company_id'))
                            ->whereNull('quarter')
                            ->whereNull('month')
                            ->latest('year')
                            ->first();
                        return $score ? number_format($score->average_score, 2) : '-';
                    })
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
                BadgeColumn::make('current_grade')
                    ->label('เกรด')
                    ->state(function (Vendor $record) {
                        $score = VendorScore::where('vendor_id', $record->id)
                            ->where('company_id', session('company_id'))
                            ->whereNull('quarter')
                            ->whereNull('month')
                            ->latest('year')
                            ->first();
                        return $score ? $score->grade : '-';
                    })
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
                Tables\Columns\TextColumn::make('risk_level')
                    ->label('ความเสี่ยง')
                    ->state(function (Vendor $record) {
                        $assessment = VendorAssessment::where('vendor_id', $record->id)
                            ->where('company_id', session('company_id'))
                            ->where('assessment_status', 'completed')
                            ->latest()
                            ->first();
                        return $assessment ? $assessment->risk_level_label : '-';
                    })
                    ->badge()
                    ->color(function (Vendor $record) {
                        $assessment = VendorAssessment::where('vendor_id', $record->id)
                            ->where('company_id', session('company_id'))
                            ->where('assessment_status', 'completed')
                            ->latest()
                            ->first();
                        return $assessment ? $assessment->risk_level_color : 'gray';
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('evaluation_count')
                    ->label('จำนวนการประเมิน')
                    ->state(function (Vendor $record) {
                        $score = VendorScore::where('vendor_id', $record->id)
                            ->where('company_id', session('company_id'))
                            ->whereNull('quarter')
                            ->whereNull('month')
                            ->latest('year')
                            ->first();
                        return $score ? $score->evaluation_count : '0';
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('risk_assess')
                    ->label('ตรวจสอบ')
                    ->icon('heroicon-o-shield-check')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('ประเมินความเสี่ยง Vendor')
                    ->modalDescription(fn (Vendor $record) => "ตรวจสอบ {$record->company_name} (Tax ID: {$record->tax_id}) กับกรมพัฒนาธุรกิจการค้า + AI Analysis?")
                    ->modalSubmitActionLabel('เริ่มประเมิน')
                    ->action(function (Vendor $record) {
                        try {
                            $service = app(VendorRiskAssessmentService::class);
                            $assessment = $service->assess($record);

                            if ($assessment->isCompleted()) {
                                Notification::make()
                                    ->title("ผลการประเมิน: {$assessment->risk_level_label}")
                                    ->body("คะแนนความเสี่ยง: {$assessment->overall_risk_score}/100")
                                    ->color($assessment->risk_level_color)
                                    ->success()
                                    ->persistent()
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('view')
                                            ->label('ดูรายละเอียด')
                                            ->url('/admin/vendor-risk-assessment')
                                            ->button(),
                                    ])
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('การประเมินล้มเหลว')
                                    ->body($assessment->error_message ?? 'เกิดข้อผิดพลาด')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('เกิดข้อผิดพลาด')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
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
            'index' => Pages\ListVendors::route('/'),
            'create' => Pages\CreateVendor::route('/create'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
        ];
    }
}
