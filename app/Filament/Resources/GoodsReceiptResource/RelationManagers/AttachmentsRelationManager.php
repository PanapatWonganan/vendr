<?php

namespace App\Filament\Resources\GoodsReceiptResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
                    ->directory('goods-receipts')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->maxSize(10240) // 10MB
                    ->previewable(true)
                    ->downloadable()
                    ->visibility('public')
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $set('file_name', $state->getClientOriginalName());
                        }
                    }),
                    
                Forms\Components\TextInput::make('file_name')
                    ->label('ชื่อไฟล์')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\Textarea::make('description')
                    ->label('คำอธิบาย')
                    ->rows(3)
                    ->maxLength(500),
                    
                Forms\Components\Hidden::make('file_type'),
                Forms\Components\Hidden::make('file_size'),
                Forms\Components\Hidden::make('uploaded_by')
                    ->default(fn () => Auth::id()),
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
                        // Handle file upload
                        if (isset($data['uploaded_file'])) {
                            $uploadedFile = $data['uploaded_file'];
                            
                            // Store file
                            $filePath = $uploadedFile->store('goods-receipts', 'public');
                            
                            // Get file info
                            $originalName = $uploadedFile->getClientOriginalName();
                            $mimeType = $uploadedFile->getMimeType();
                            $fileSize = $uploadedFile->getSize();
                            
                            // Update data
                            $data['file_path'] = $filePath;
                            $data['file_name'] = $data['file_name'] ?: $originalName;
                            $data['file_type'] = $mimeType;
                            $data['file_size'] = $fileSize;
                            
                            // Remove uploaded_file from data
                            unset($data['uploaded_file']);
                        }
                        
                        $data['uploaded_by'] = Auth::id();
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
                    ->mutateFormDataUsing(function (array $data): array {
                        // ไม่ต้องเปลี่ยน uploaded_by เมื่อ edit
                        unset($data['uploaded_by']);
                        return $data;
                    }),
                    
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
