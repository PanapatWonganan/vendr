<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TermsOfReferenceResource\Pages;
use App\Models\TermsOfReference;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TermsOfReferenceResource extends Resource
{
    use \App\Filament\Resources\Concerns\HasYearFilter;

    protected static ?string $model = TermsOfReference::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'TOR Management';

    protected static ?string $navigationGroup = 'Procurement Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'TOR';

    protected static ?string $pluralModelLabel = 'Terms of References';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'admin', 'requester', 'department_head',
            'procurement_officer', 'procurement_manager',
            'procurement_committee', 'auditor',
        ]) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $companyId = session('company_id');

        return parent::getEloquentQuery()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId));
    }

    public static function getNavigationBadge(): ?string
    {
        $companyId = session('company_id');
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        $count = TermsOfReference::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['submitted', 'reviewing'])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    /**
     * Create/Edit moved to the TOR Document Builder (app/Filament/Pages/TorBuilder).
     * The old 5-step wizard was removed 2026-08-20.
     */
    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tor_number')
                    ->label('เลข TOR')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('ชื่อ TOR')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('แผนก')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('tor_type')
                    ->label('ประเภท')
                    ->formatStateUsing(fn ($state) => TermsOfReference::getTorTypeOptions()[$state] ?? $state)
                    ->colors([
                        'primary' => 'goods',
                        'info' => 'services',
                        'warning' => 'construction',
                        'success' => 'consulting',
                    ]),

                Tables\Columns\TextColumn::make('procurement_method')
                    ->label('วิธีจัดซื้อ')
                    ->formatStateUsing(fn ($state) => TermsOfReference::getProcurementMethodOptions()[$state] ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('สถานะ')
                    ->formatStateUsing(fn ($state) => TermsOfReference::getStatusOptions()[$state] ?? $state)
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'submitted',
                        'warning' => 'reviewing',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => fn ($state) => in_array($state, ['cancelled', 'expired', 'amended']),
                    ]),

                Tables\Columns\TextColumn::make('budget_estimate')
                    ->label('งบประมาณ')
                    ->money('THB')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('สำคัญ')
                    ->formatStateUsing(fn ($state) => TermsOfReference::getPriorityOptions()[$state] ?? $state)
                    ->colors([
                        'secondary' => 'low',
                        'primary' => 'medium',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ]),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('ผู้สร้าง')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('สร้างเมื่อ')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('สถานะ')
                    ->options(TermsOfReference::getStatusOptions()),

                Tables\Filters\SelectFilter::make('tor_type')
                    ->label('ประเภท')
                    ->options(TermsOfReference::getTorTypeOptions()),

                Tables\Filters\SelectFilter::make('procurement_method')
                    ->label('วิธีจัดซื้อ')
                    ->options(TermsOfReference::getProcurementMethodOptions()),

                Tables\Filters\SelectFilter::make('department')
                    ->label('แผนก')
                    ->relationship('department', 'name'),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('ลำดับสำคัญ')
                    ->options(TermsOfReference::getPriorityOptions()),

                // กรองตามปีของเอกสาร (วันที่ส่งพิจารณา) ไม่ใช่วันที่สร้างในระบบ
                static::yearFilter('submitted_at'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('ดูเอกสาร')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('tor-builder.preview', $record), shouldOpenInNewTab: true)
                    ->visible(fn ($record) => ! empty($record->document_sections)),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->url(fn ($record) => route('tor-builder.pdf', $record), shouldOpenInNewTab: true)
                    ->visible(fn ($record) => ! empty($record->document_sections)),
                Tables\Actions\Action::make('edit')
                    ->label('แก้ไข')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn ($record) => url('/admin/tor-builder?tor='.$record->id))
                    ->visible(fn ($record) => $record->canBeEdited()),

                // Submit action
                Tables\Actions\Action::make('submit')
                    ->label('ส่งพิจารณา')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('ส่ง TOR เพื่อพิจารณา')
                    ->modalDescription('คุณต้องการส่ง TOR นี้เพื่อพิจารณาหรือไม่?')
                    ->visible(fn ($record) => $record->status === 'draft' && (
                        $record->created_by === Auth::id()
                        || Auth::user()?->hasAnyRole(['admin', 'procurement_manager'])
                    ))
                    ->action(function ($record) {
                        $record->submit(Auth::user());
                        \App\Events\TorSubmitted::dispatch($record, Auth::user());
                    }),

                // Approve action
                Tables\Actions\Action::make('approve')
                    ->label('อนุมัติ')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('อนุมัติ TOR')
                    ->form([
                        Forms\Components\Textarea::make('comments')
                            ->label('หมายเหตุ (ถ้ามี)')
                            ->rows(2),
                    ])
                    ->visible(fn ($record) => $record->canBeApproved() && Auth::user()?->hasAnyRole([
                        'admin', 'procurement_manager', 'department_head',
                    ]))
                    ->action(function ($record, array $data) {
                        $record->approve(Auth::user(), $data['comments'] ?? null);
                        \App\Events\TorApproved::dispatch($record, Auth::user());
                    }),

                // Reject action
                Tables\Actions\Action::make('reject')
                    ->label('ไม่อนุมัติ')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('ไม่อนุมัติ TOR')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('เหตุผลที่ไม่อนุมัติ')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn ($record) => $record->canBeApproved() && Auth::user()?->hasAnyRole([
                        'admin', 'procurement_manager', 'department_head',
                    ]))
                    ->action(function ($record, array $data) {
                        $record->reject(Auth::user(), $data['rejection_reason']);
                        \App\Events\TorRejected::dispatch($record, Auth::user(), $data['rejection_reason']);
                    }),

                // Create PR from TOR
                Tables\Actions\Action::make('create_pr')
                    ->label('สร้าง PR')
                    ->icon('heroicon-o-document-plus')
                    ->color('primary')
                    ->visible(fn ($record) => $record->status === 'approved')
                    ->url(fn ($record) => route('filament.admin.resources.purchase-requisitions.create', [
                        'tor_id' => $record->id,
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->hasRole('admin')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTermsOfReferences::route('/'),
        ];
    }
}
