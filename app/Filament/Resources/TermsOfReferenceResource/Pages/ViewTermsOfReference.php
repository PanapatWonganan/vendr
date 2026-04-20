<?php

namespace App\Filament\Resources\TermsOfReferenceResource\Pages;

use App\Filament\Resources\TermsOfReferenceResource;
use App\Events\TorSubmitted;
use App\Events\TorApproved;
use App\Events\TorRejected;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ViewTermsOfReference extends ViewRecord
{
    protected static string $resource = TermsOfReferenceResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('ข้อมูลพื้นฐาน')
                    ->schema([
                        Components\Grid::make(3)->schema([
                            Components\TextEntry::make('tor_number')->label('เลข TOR'),
                            Components\TextEntry::make('title')->label('ชื่อ TOR')->columnSpan(2),
                            Components\TextEntry::make('department.name')->label('แผนก'),
                            Components\TextEntry::make('tor_type')
                                ->label('ประเภท')
                                ->formatStateUsing(fn ($state) => \App\Models\TermsOfReference::getTorTypeOptions()[$state] ?? $state)
                                ->badge(),
                            Components\TextEntry::make('status')
                                ->label('สถานะ')
                                ->formatStateUsing(fn ($state) => \App\Models\TermsOfReference::getStatusOptions()[$state] ?? $state)
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'draft' => 'gray',
                                    'submitted' => 'info',
                                    'reviewing' => 'warning',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    default => 'gray',
                                }),
                            Components\TextEntry::make('form_category')
                                ->label('แบบฟอร์ม')
                                ->formatStateUsing(fn ($state) => \App\Models\TermsOfReference::getFormCategoryOptions()[$state] ?? $state),
                            Components\TextEntry::make('work_type')
                                ->label('ประเภทงาน')
                                ->formatStateUsing(fn ($state) => \App\Models\TermsOfReference::getWorkTypeOptions()[$state] ?? $state),
                            Components\TextEntry::make('procurement_method')
                                ->label('วิธีจัดซื้อ')
                                ->formatStateUsing(fn ($state) => \App\Models\TermsOfReference::getProcurementMethodOptions()[$state] ?? $state),
                            Components\TextEntry::make('priority')
                                ->label('ลำดับสำคัญ')
                                ->formatStateUsing(fn ($state) => \App\Models\TermsOfReference::getPriorityOptions()[$state] ?? $state)
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'low' => 'gray',
                                    'medium' => 'info',
                                    'high' => 'warning',
                                    'urgent' => 'danger',
                                    default => 'gray',
                                }),
                            Components\TextEntry::make('project_name')->label('ชื่อโครงการ'),
                            Components\TextEntry::make('project_code')->label('รหัสโครงการ'),
                        ]),
                    ]),

                Components\Section::make('ขอบเขตงาน')
                    ->schema([
                        Components\TextEntry::make('background')->label('ความเป็นมา')->html()->columnSpanFull(),
                        Components\TextEntry::make('objectives')->label('วัตถุประสงค์')->html()->columnSpanFull(),
                        Components\TextEntry::make('scope_of_work')->label('ขอบเขตของงาน')->html()->columnSpanFull(),
                        Components\TextEntry::make('deliverables')->label('ผลงานที่ต้องส่งมอบ')->html()->columnSpanFull(),
                        Components\TextEntry::make('specifications')->label('ข้อกำหนดทางเทคนิค')->html()->columnSpanFull(),
                    ])
                    ->collapsible(),

                Components\Section::make('เงื่อนไขและคุณสมบัติ')
                    ->schema([
                        Components\TextEntry::make('qualification_requirements')->label('คุณสมบัติผู้เสนอราคา')->html()->columnSpanFull(),
                        Components\TextEntry::make('payment_terms')->label('เงื่อนไขการจ่ายเงิน')->html()->columnSpanFull(),
                        Components\TextEntry::make('warranty_requirements')->label('การรับประกัน')->html()->columnSpanFull(),
                        Components\TextEntry::make('penalty_clause')->label('เงื่อนไขเบี้ยปรับ')->html()->columnSpanFull(),
                    ])
                    ->collapsible(),

                Components\Section::make('งบประมาณและระยะเวลา')
                    ->schema([
                        Components\Grid::make(3)->schema([
                            Components\TextEntry::make('budget_estimate')->label('งบประมาณ')->money('THB'),
                            Components\TextEntry::make('budget_code')->label('รหัสงบประมาณ'),
                            Components\TextEntry::make('currency')->label('สกุลเงิน'),
                            Components\TextEntry::make('start_date')->label('วันเริ่มต้น')->date('d/m/Y'),
                            Components\TextEntry::make('end_date')->label('วันสิ้นสุด')->date('d/m/Y'),
                            Components\TextEntry::make('duration_days')->label('ระยะเวลา (วัน)'),
                        ]),
                    ])
                    ->collapsible(),

                Components\Section::make('คณะกรรมการ')
                    ->schema([
                        Components\Grid::make(2)->schema([
                            Components\TextEntry::make('procurementCommittee.name')->label('คณะกรรมการจัดซื้อ'),
                            Components\TextEntry::make('inspectionCommittee.name')->label('คณะกรรมการตรวจรับ'),
                        ]),
                    ])
                    ->collapsible(),

                Components\Section::make('ข้อมูลการดำเนินการ')
                    ->schema([
                        Components\Grid::make(3)->schema([
                            Components\TextEntry::make('creator.name')->label('ผู้สร้าง'),
                            Components\TextEntry::make('created_at')->label('สร้างเมื่อ')->dateTime('d/m/Y H:i'),
                            Components\TextEntry::make('revision_label')->label('Revision'),
                            Components\TextEntry::make('submitter.name')->label('ผู้ส่งพิจารณา'),
                            Components\TextEntry::make('submitted_at')->label('ส่งพิจารณาเมื่อ')->dateTime('d/m/Y H:i'),
                            Components\TextEntry::make('approver.name')->label('ผู้อนุมัติ'),
                            Components\TextEntry::make('approved_at')->label('อนุมัติเมื่อ')->dateTime('d/m/Y H:i'),
                            Components\TextEntry::make('rejection_reason')
                                ->label('เหตุผลที่ไม่อนุมัติ')
                                ->visible(fn ($record) => $record->status === 'rejected')
                                ->columnSpan(2),
                        ]),
                    ])
                    ->collapsible(),

                // AI Review Section
                Components\Section::make('AI ตรวจสอบ TOR')
                    ->schema([
                        Components\ViewEntry::make('ai_review')
                            ->view('livewire.tor-ai-review-infolist', ['torId' => fn ($record) => $record->id]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Related PRs
                Components\Section::make('PR ที่สร้างจาก TOR นี้')
                    ->schema([
                        Components\RepeatableEntry::make('purchaseRequisitions')
                            ->schema([
                                Components\TextEntry::make('pr_number')->label('เลข PR'),
                                Components\TextEntry::make('title')->label('ชื่อ'),
                                Components\TextEntry::make('status')->label('สถานะ')->badge(),
                                Components\TextEntry::make('created_at')->label('สร้างเมื่อ')->date('d/m/Y'),
                            ])
                            ->columns(4),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => $record->purchaseRequisitions()->exists()),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Edit action
        if ($this->record->canBeEdited()) {
            $actions[] = Actions\EditAction::make()
                ->label('แก้ไข')
                ->icon('heroicon-o-pencil');
        }

        // Submit for approval
        if ($this->record->status === 'draft' && (
            $this->record->created_by === Auth::id()
            || Auth::user()?->hasAnyRole(['admin', 'procurement_manager'])
        )) {
            $actions[] = Actions\Action::make('submit')
                ->label('ส่งพิจารณา')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('ส่ง TOR เพื่อพิจารณา')
                ->modalDescription('คุณต้องการส่ง TOR นี้เพื่อพิจารณาหรือไม่?')
                ->action(function () {
                    $this->record->submit(Auth::user());
                    TorSubmitted::dispatch($this->record, Auth::user());

                    Notification::make()
                        ->title('ส่ง TOR เพื่อพิจารณาแล้ว')
                        ->body('TOR ' . $this->record->tor_number . ' ถูกส่งเพื่อพิจารณาแล้ว')
                        ->success()
                        ->send();
                });
        }

        // Approve
        if ($this->record->canBeApproved() && $this->canApprove()) {
            $actions[] = Actions\Action::make('approve')
                ->label('อนุมัติ')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('อนุมัติ TOR')
                ->form([
                    Forms\Components\Textarea::make('comments')
                        ->label('หมายเหตุ')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $this->record->approve(Auth::user(), $data['comments'] ?? null);
                    TorApproved::dispatch($this->record, Auth::user());

                    Notification::make()
                        ->title('อนุมัติ TOR แล้ว')
                        ->body('TOR ' . $this->record->tor_number . ' ได้รับการอนุมัติแล้ว')
                        ->success()
                        ->send();
                });
        }

        // Reject
        if ($this->record->canBeApproved() && $this->canApprove()) {
            $actions[] = Actions\Action::make('reject')
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
                ->action(function (array $data) {
                    $this->record->reject(Auth::user(), $data['rejection_reason']);
                    TorRejected::dispatch($this->record, Auth::user(), $data['rejection_reason']);

                    Notification::make()
                        ->title('ไม่อนุมัติ TOR')
                        ->body('TOR ' . $this->record->tor_number . ' ถูกปฏิเสธ')
                        ->warning()
                        ->send();
                });
        }

        // Return to draft (for rejected TORs)
        if ($this->record->status === 'rejected' && (
            $this->record->created_by === Auth::id()
            || Auth::user()?->hasAnyRole(['admin', 'procurement_manager'])
        )) {
            $actions[] = Actions\Action::make('return_to_draft')
                ->label('กลับไปแก้ไข')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->returnToDraft(Auth::user());
                    Notification::make()
                        ->title('ส่งกลับแก้ไขแล้ว')
                        ->success()
                        ->send();

                    return redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                });
        }

        // Create PR from TOR
        if ($this->record->status === 'approved') {
            $actions[] = Actions\Action::make('create_pr')
                ->label('สร้าง PR จาก TOR')
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->url(fn () => route('filament.admin.resources.purchase-requisitions.create', [
                    'tor_id' => $this->record->id,
                ]));
        }

        // Amend
        if ($this->record->status === 'approved' && (
            $this->record->created_by === Auth::id()
            || Auth::user()?->hasAnyRole(['admin', 'procurement_manager'])
        )) {
            $actions[] = Actions\Action::make('amend')
                ->label('แก้ไข TOR (Amend)')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('แก้ไข TOR')
                ->modalDescription('จะสร้าง TOR ฉบับแก้ไข (Revision) จาก TOR ที่อนุมัติแล้ว')
                ->form([
                    Forms\Components\Textarea::make('amendment_reason')
                        ->label('เหตุผลที่ต้องแก้ไข')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $revision = $this->record->createRevision(Auth::user(), $data['amendment_reason']);

                    Notification::make()
                        ->title('สร้าง TOR ฉบับแก้ไขแล้ว')
                        ->body('TOR ' . $revision->tor_number . ' (Rev.' . $revision->revision_number . ')')
                        ->success()
                        ->send();

                    return redirect($this->getResource()::getUrl('edit', ['record' => $revision]));
                });
        }

        // Export PDF
        $actions[] = Actions\Action::make('export_pdf')
            ->label('Export PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->action(function () {
                try {
                    $pdfService = app(\App\Services\TorPdfService::class);
                    $pdfContent = $pdfService->generatePdf($this->record);
                    $filename = $pdfService->generateFilename($this->record);

                    return response()->streamDownload(
                        fn () => print($pdfContent),
                        $filename,
                        ['Content-Type' => 'application/pdf']
                    );
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('ไม่สามารถสร้าง PDF ได้')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });

        return $actions;
    }

    protected function canApprove(): bool
    {
        $user = Auth::user();

        if ($user->hasAnyRole(['admin', 'procurement_manager'])) {
            return true;
        }

        if ($user->hasRole('department_head') && $user->department_id === $this->record->department_id) {
            return true;
        }

        return false;
    }
}
