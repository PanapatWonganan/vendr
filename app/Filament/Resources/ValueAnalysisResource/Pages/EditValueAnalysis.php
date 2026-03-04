<?php

namespace App\Filament\Resources\ValueAnalysisResource\Pages;

use App\Filament\Resources\ValueAnalysisResource;
use App\Models\ValueAnalysis;
use App\Services\AmendmentService;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditValueAnalysis extends EditRecord
{
    protected static string $resource = ValueAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\DeleteAction::make(),
        ];

        $record = $this->getRecord();

        // "สร้าง VA Revision" button — only on approved VA
        if ($record->status === 'approved') {
            $actions[] = Actions\Action::make('createRevision')
                ->label('สร้าง VA Revision (แก้ไข)')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('สร้าง VA Revision สำหรับแก้ไข')
                ->modalDescription('ระบบจะ copy VA นี้เป็น revision ใหม่ เพื่อให้คุณแก้ไขรายละเอียดก่อนส่งอนุมัติ')
                ->form([
                    Select::make('amendment_type')
                        ->label('ประเภทการแก้ไข')
                        ->options(ValueAnalysis::AMENDMENT_TYPES)
                        ->required()
                        ->native(false),
                    Textarea::make('amendment_reason')
                        ->label('เหตุผลที่ต้องแก้ไข')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) use ($record) {
                    $revision = $record->createRevision(
                        $data['amendment_type'],
                        $data['amendment_reason'],
                        auth()->id()
                    );

                    Notification::make()
                        ->title('สร้าง VA Revision สำเร็จ')
                        ->body('VA ' . $revision->va_number . ' (Rev.' . $revision->revision_number . ') ถูกสร้างแล้ว')
                        ->success()
                        ->send();

                    return redirect()->to(
                        ValueAnalysisResource::getUrl('edit', ['record' => $revision])
                    );
                });
        }

        // "นำไปแก้ไข PO" button — only on approved VA revision
        if ($record->isRevision() && $record->status === 'approved') {
            $actions[] = Actions\Action::make('applyToPo')
                ->label('นำไปแก้ไข PO')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('นำ VA Revision ไปแก้ไข PO')
                ->modalDescription(
                    'ระบบจะอัปเดต PO ตาม VA Revision นี้ (Rev.' . $record->revision_number . ') ' .
                    'PO จะถูกส่งเข้ากระบวนการอนุมัติใหม่'
                )
                ->action(function () use ($record) {
                    try {
                        $service = app(AmendmentService::class);
                        $amendment = $service->applyVaRevisionToPo($record, auth()->id());

                        Notification::make()
                            ->title('แก้ไข PO สำเร็จ')
                            ->body('PO ' . $amendment->purchaseOrder->po_number .
                                ' (แก้ไขครั้งที่ ' . $amendment->amendment_number . ') ส่งรออนุมัติแล้ว')
                            ->success()
                            ->send();

                        return redirect()->to('/admin/purchase-orders/' . $amendment->purchase_order_id . '/edit');
                    } catch (\RuntimeException $e) {
                        Notification::make()
                            ->title('ไม่สามารถแก้ไข PO ได้')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                });
        }

        return $actions;
    }

    protected function getFooterWidgets(): array
    {
        $record = $this->getRecord();

        // Show comparison if this is a revision
        if ($record->isRevision()) {
            return [
                ValueAnalysisResource\Widgets\VaComparisonWidget::class,
            ];
        }

        // Show revisions list if this has revisions
        if ($record->revisions()->exists()) {
            return [
                ValueAnalysisResource\Widgets\VaRevisionsListWidget::class,
            ];
        }

        return [];
    }
}
