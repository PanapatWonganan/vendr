<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProcurementAttachmentResource\Pages;
use App\Models\ProcurementAttachment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ProcurementAttachmentResource extends Resource
{
    protected static ?string $model = ProcurementAttachment::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';
    protected static ?string $navigationLabel = 'Attachments (เอกสารแนบ)';
    protected static ?string $modelLabel = 'Attachment';
    protected static ?string $pluralModelLabel = 'Attachments';
    protected static ?string $navigationGroup = 'Procurement Management';
    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required()
                    ->default(function () {
                        return session('company_id');
                    })
                    ->disabled(function () {
                        return session('company_id') !== null;
                    })
                    ->dehydrated(),

                Forms\Components\Select::make('attachable_type')
                    ->options([
                        'App\Models\PurchaseRequisition' => 'Purchase Requisition',
                        'App\Models\PurchaseOrder' => 'Purchase Order',
                    ])
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('attachable_id')
                    ->required()
                    ->numeric(),

                Forms\Components\FileUpload::make('file_path')
                    ->label('File')
                    ->directory('procurement-attachments')
                    ->storeFileNamesIn('file_name')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->maxSize(10240) // 10MB
                    ->required(),

                Forms\Components\TextInput::make('original_name')
                    ->maxLength(255)
                    ->required(),

                Forms\Components\Select::make('category')
                    ->options(ProcurementAttachment::getCategories())
                    ->required()
                    ->searchable(),

                Forms\Components\Textarea::make('description')
                    ->maxLength(500)
                    ->columnSpanFull(),

                Forms\Components\Hidden::make('uploaded_by')
                    ->default(auth()->id()),

                Forms\Components\Toggle::make('is_public')
                    ->label('Public Access')
                    ->helperText('Allow public access to this file')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('attachable_type')
                    ->label('Document Type')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'App\Models\PurchaseRequisition' => 'Purchase Requisition',
                        'App\Models\PurchaseOrder' => 'Purchase Order',
                        default => $state
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'App\Models\PurchaseRequisition' => 'info',
                        'App\Models\PurchaseOrder' => 'warning',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('attachable_id')
                    ->label('Document ID')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('original_name')
                    ->label('File Name')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 30) {
                            return $state;
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('category')
                    ->formatStateUsing(fn (string $state): string => ProcurementAttachment::getCategories()[$state] ?? $state)
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('file_size_for_humans')
                    ->label('Size'),

                Tables\Columns\TextColumn::make('file_type')
                    ->label('Type')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_public')
                    ->boolean()
                    ->label('Public'),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->numeric()
                    ->sortable(),

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
                Tables\Filters\SelectFilter::make('attachable_type')
                    ->options([
                        'App\Models\PurchaseRequisition' => 'Purchase Requisition',
                        'App\Models\PurchaseOrder' => 'Purchase Order',
                    ]),

                Tables\Filters\SelectFilter::make('category')
                    ->options(ProcurementAttachment::getCategories()),

                Tables\Filters\Filter::make('my_uploads')
                    ->label('My Uploads')
                    ->query(fn (Builder $query): Builder => $query->where('uploaded_by', auth()->id())),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Public Access'),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->action(function (ProcurementAttachment $record) {
                        return Storage::download($record->file_path, $record->original_name);
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(session('company_id'), function ($query) {
                return $query->where('company_id', session('company_id'));
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProcurementAttachments::route('/'),
            'create' => Pages\CreateProcurementAttachment::route('/create'),
            'view' => Pages\ViewProcurementAttachment::route('/{record}'),
            'edit' => Pages\EditProcurementAttachment::route('/{record}/edit'),
        ];
    }
}