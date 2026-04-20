<?php

namespace App\Filament\Resources\GoodsReceiptResource\RelationManagers;

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

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'เอกสารแนบ';

    protected static ?string $modelLabel = 'ไฟล์';

    protected static ?string $pluralModelLabel = 'ไฟล์แนบ';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('uploaded_file')
                    ->label('เลือกไฟล์')
                    ->required()
                    ->disk('public')
                    ->directory('goods-receipts')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                        'image/jpg',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(10240) // 10MB
                    ->previewable(true)
                    ->downloadable()
                    ->visibility('public')
                    ->storeFiles(false), // we handle storing manually in mutateFormDataUsing

                Forms\Components\TextInput::make('file_name')
                    ->label('ชื่อไฟล์ (ไม่บังคับ - ระบบใช้ชื่อไฟล์ต้นฉบับหากเว้นว่าง)')
                    ->maxLength(255),

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
                Tables\Columns\TextColumn::make('file_icon')
                    ->label('')
                    ->formatStateUsing(fn ($record) => $record->file_icon)
                    ->size('lg'),

                Tables\Columns\TextColumn::make('file_name')
                    ->label('ชื่อไฟล์')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->file_name),

                Tables\Columns\TextColumn::make('file_size_human')
                    ->label('ขนาด')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('description')
                    ->label('คำอธิบาย')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->description),

                Tables\Columns\TextColumn::make('uploadedBy.name')
                    ->label('ผู้อัปโหลด')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('วันที่อัปโหลด')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('อัปโหลดไฟล์')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $uploadedFile = $data['uploaded_file'] ?? null;

                        // FileUpload with storeFiles(false) returns array of TemporaryUploadedFile
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

                            // Persist the file to the public disk
                            $filePath = $uploadedFile->store('goods-receipts', 'public');

                            if ($filePath === false) {
                                throw new \RuntimeException('Failed to persist uploaded file.');
                            }
                        } catch (\Throwable $e) {
                            Log::error('GR attachment upload failed', [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);

                            Notification::make()
                                ->title('อัปโหลดไฟล์ไม่สำเร็จ')
                                ->body('ไฟล์ชั่วคราวอาจหมดอายุ กรุณาลองเลือกไฟล์แล้วอัปโหลดใหม่อีกครั้ง')
                                ->danger()
                                ->send();

                            throw $e;
                        }

                        $data['file_path'] = $filePath;
                        $data['file_name'] = ! empty($data['file_name']) ? $data['file_name'] : $originalName;
                        $data['file_type'] = $mimeType;
                        $data['file_size'] = $fileSize;
                        $data['uploaded_by'] = Auth::id();

                        unset($data['uploaded_file']);

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('ดู')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => Storage::url($record->file_path))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->canPreview()),

                Tables\Actions\Action::make('download')
                    ->label('ดาวน์โหลด')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn ($record) => Storage::url($record->file_path))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make()
                    ->label('แก้ไข')
                    ->form([
                        Forms\Components\TextInput::make('file_name')
                            ->label('ชื่อไฟล์')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('คำอธิบาย')
                            ->rows(3)
                            ->maxLength(500),
                    ]),

                Tables\Actions\DeleteAction::make()
                    ->label('ลบ')
                    ->requiresConfirmation()
                    ->modalHeading('ลบไฟล์')
                    ->modalDescription('คุณแน่ใจหรือไม่ที่ต้องการลบไฟล์นี้? ไฟล์จะถูกลบถาวรและไม่สามารถกู้คืนได้')
                    ->modalSubmitActionLabel('ลบ')
                    ->modalCancelActionLabel('ยกเลิก'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('ลบที่เลือก')
                        ->requiresConfirmation()
                        ->modalHeading('ลบไฟล์ที่เลือก')
                        ->modalDescription('คุณแน่ใจหรือไม่ที่ต้องการลบไฟล์ที่เลือกทั้งหมด? ไฟล์จะถูกลบถาวรและไม่สามารถกู้คืนได้'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
