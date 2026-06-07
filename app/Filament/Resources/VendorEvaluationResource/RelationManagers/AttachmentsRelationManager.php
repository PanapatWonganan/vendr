<?php

namespace App\Filament\Resources\VendorEvaluationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * ข้อ 13: ให้หน้าการประเมินผลงานสามารถแนบไฟล์ได้
 * ใช้ตาราง polymorphic ProcurementAttachment (attachable) ร่วมกับโมดูลอื่น
 */
class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'เอกสารแนบ';

    protected static ?string $modelLabel = 'ไฟล์';

    protected static ?string $pluralModelLabel = 'ไฟล์แนบ';

    protected const ACCEPTED_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/jpg',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('uploaded_file')
                    ->label('เลือกไฟล์')
                    ->required()
                    ->disk('public')
                    ->directory('vendor-evaluations')
                    ->acceptedFileTypes(self::ACCEPTED_TYPES)
                    ->maxSize(51200) // 50MB
                    ->previewable(true)
                    ->downloadable()
                    ->visibility('public')
                    ->storeFiles(false),

                Forms\Components\TextInput::make('file_name')
                    ->label('ชื่อไฟล์ (ไม่บังคับ - ระบบใช้ชื่อไฟล์ต้นฉบับหากเว้นว่าง)')
                    ->maxLength(255),

                Forms\Components\Select::make('category')
                    ->label('ประเภทเอกสาร')
                    ->options(\App\Models\ProcurementAttachment::getCategories())
                    ->default('inspection_report'),

                Forms\Components\Textarea::make('description')
                    ->label('คำอธิบาย')
                    ->rows(3)
                    ->maxLength(500),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('file_name')
            ->columns([
                Tables\Columns\TextColumn::make('file_name')
                    ->label('ชื่อไฟล์')
                    ->searchable()
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->file_name),

                Tables\Columns\TextColumn::make('category')
                    ->label('ประเภท')
                    ->formatStateUsing(fn ($state) => \App\Models\ProcurementAttachment::getCategories()[$state] ?? $state)
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('file_size_for_humans')
                    ->label('ขนาด')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('description')
                    ->label('คำอธิบาย')
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->description),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('ผู้อัปโหลด')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('วันที่อัปโหลด')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('อัปโหลดไฟล์')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $uploadedFile = $data['uploaded_file'] ?? null;
                        unset($data['uploaded_file']);

                        if (is_array($uploadedFile)) {
                            $uploadedFile = reset($uploadedFile) ?: null;
                        }

                        if (! $uploadedFile instanceof TemporaryUploadedFile) {
                            Notification::make()
                                ->title('ไม่พบไฟล์ที่อัปโหลด')
                                ->body('กรุณาลองอัปโหลดไฟล์อีกครั้ง')
                                ->danger()
                                ->send();

                            throw new \RuntimeException('Uploaded file is not a TemporaryUploadedFile instance.');
                        }

                        try {
                            $originalName = $uploadedFile->getClientOriginalName();
                            $mimeType = $uploadedFile->getMimeType() ?: 'application/octet-stream';
                            $fileSize = $uploadedFile->getSize();

                            $filePath = $uploadedFile->store('vendor-evaluations', 'public');
                            if ($filePath === false) {
                                throw new \RuntimeException('Failed to persist uploaded file.');
                            }
                        } catch (\Throwable $e) {
                            Log::error('Vendor evaluation attachment upload failed', [
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('อัปโหลดไฟล์ไม่สำเร็จ')
                                ->body('ไฟล์ชั่วคราวอาจหมดอายุ กรุณาลองเลือกไฟล์แล้วอัปโหลดใหม่อีกครั้ง')
                                ->danger()
                                ->send();

                            throw $e;
                        }

                        $data['file_path'] = $filePath;
                        $data['original_name'] = $originalName;
                        $data['file_name'] = ! empty($data['file_name']) ? $data['file_name'] : $originalName;
                        $data['mime_type'] = $mimeType;
                        $data['file_size'] = $fileSize;
                        $data['category'] = $data['category'] ?? 'inspection_report';
                        $data['uploaded_by'] = Auth::id();
                        $data['company_id'] = session('company_id');
                        $data['is_public'] = true;

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('ดู')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => Storage::url($record->file_path))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('download')
                    ->label('ดาวน์โหลด')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn ($record) => Storage::url($record->file_path))
                    ->openUrlInNewTab(),

                // ข้อ 11/13: อนุญาตให้แก้ไข/แทนที่ไฟล์ที่อัปโหลดไปแล้ว
                Tables\Actions\EditAction::make()
                    ->label('แก้ไข')
                    ->form([
                        Forms\Components\FileUpload::make('replacement_file')
                            ->label('แทนที่ไฟล์ (เว้นว่างไว้หากไม่ต้องการเปลี่ยนไฟล์เดิม)')
                            ->disk('public')
                            ->directory('vendor-evaluations')
                            ->acceptedFileTypes(self::ACCEPTED_TYPES)
                            ->maxSize(51200)
                            ->previewable(true)
                            ->downloadable()
                            ->visibility('public')
                            ->storeFiles(false)
                            ->helperText(fn ($record) => $record ? 'ไฟล์ปัจจุบัน: '.$record->file_name : null),
                        Forms\Components\TextInput::make('file_name')
                            ->label('ชื่อไฟล์')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('category')
                            ->label('ประเภทเอกสาร')
                            ->options(\App\Models\ProcurementAttachment::getCategories()),
                        Forms\Components\Textarea::make('description')
                            ->label('คำอธิบาย')
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->mutateFormDataUsing(function (array $data, $record): array {
                        $uploadedFile = $data['replacement_file'] ?? null;
                        unset($data['replacement_file']);

                        if (is_array($uploadedFile)) {
                            $uploadedFile = reset($uploadedFile) ?: null;
                        }

                        if (! $uploadedFile instanceof TemporaryUploadedFile) {
                            return $data;
                        }

                        try {
                            $originalName = $uploadedFile->getClientOriginalName();
                            $mimeType = $uploadedFile->getMimeType() ?: 'application/octet-stream';
                            $fileSize = $uploadedFile->getSize();

                            $newPath = $uploadedFile->store('vendor-evaluations', 'public');
                            if ($newPath === false) {
                                throw new \RuntimeException('Failed to persist replacement file.');
                            }

                            if ($record->file_path && Storage::disk('public')->exists($record->file_path)) {
                                Storage::disk('public')->delete($record->file_path);
                            }
                        } catch (\Throwable $e) {
                            Log::error('Vendor evaluation attachment replacement failed', [
                                'error' => $e->getMessage(),
                                'attachment_id' => $record->id ?? null,
                            ]);

                            Notification::make()
                                ->title('แทนที่ไฟล์ไม่สำเร็จ')
                                ->danger()
                                ->send();

                            throw $e;
                        }

                        $data['file_path'] = $newPath;
                        $data['original_name'] = $originalName;
                        $data['mime_type'] = $mimeType;
                        $data['file_size'] = $fileSize;
                        if (empty($data['file_name'])) {
                            $data['file_name'] = $originalName;
                        }

                        return $data;
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('ลบ')
                    ->requiresConfirmation()
                    ->modalHeading('ลบไฟล์')
                    ->modalDescription('คุณแน่ใจหรือไม่ที่ต้องการลบไฟล์นี้?')
                    ->after(function ($record) {
                        if ($record->file_path && Storage::disk('public')->exists($record->file_path)) {
                            Storage::disk('public')->delete($record->file_path);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('ลบที่เลือก')
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
