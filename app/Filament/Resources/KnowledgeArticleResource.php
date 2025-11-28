<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KnowledgeArticleResource\Pages;
use App\Filament\Resources\KnowledgeArticleResource\RelationManagers;
use App\Models\KnowledgeArticle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KnowledgeArticleResource extends Resource
{
    protected static ?string $model = KnowledgeArticle::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Knowledge Base';
    protected static ?string $modelLabel = 'บทความ';
    protected static ?string $pluralModelLabel = 'บทความความรู้';
    protected static ?string $navigationGroup = 'Knowledge Sharing';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ข้อมูลบทความ')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('ชื่อบทความ')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\Select::make('category')
                            ->label('หมวดหมู่')
                            ->options(KnowledgeArticle::CATEGORIES)
                            ->required(),
                            
                        Forms\Components\Select::make('type')
                            ->label('ประเภท')
                            ->options(KnowledgeArticle::TYPES)
                            ->required()
                            ->default(KnowledgeArticle::TYPE_DOCUMENT)
                            ->reactive()
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                $state === KnowledgeArticle::TYPE_VIDEO 
                                    ? $set('file_path', null) 
                                    : $set('youtube_url', null)
                            ),
                            
                        Forms\Components\Toggle::make('is_published')
                            ->label('เผยแพร่')
                            ->default(true),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('เนื้อหา')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->label('เนื้อหาบทความ')
                            ->required()
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('ไฟล์และลิงก์')
                    ->schema([
                        Forms\Components\TextInput::make('youtube_url')
                            ->label('YouTube URL')
                            ->url()
                            ->visible(fn (Forms\Get $get) => $get('type') === KnowledgeArticle::TYPE_VIDEO)
                            ->helperText('ใส่ลิงก์ YouTube เช่น https://www.youtube.com/watch?v=xxxxx'),
                            
                        Forms\Components\TextInput::make('video_duration')
                            ->label('ความยาววิดีโอ')
                            ->placeholder('เช่น 5:30 หรือ 1:20:45')
                            ->visible(fn (Forms\Get $get) => $get('type') === KnowledgeArticle::TYPE_VIDEO)
                            ->helperText('รูปแบบ MM:SS หรือ HH:MM:SS'),
                            
                        Forms\Components\FileUpload::make('file_path')
                            ->label('ไฟล์แนบ')
                            ->visible(fn (Forms\Get $get) => $get('type') === KnowledgeArticle::TYPE_DOCUMENT)
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/*'])
                            ->directory('knowledge-base')
                            ->helperText('รองรับไฟล์ PDF, Word, และรูปภาพ'),
                    ])
                    ->columns(1),
                    
                Forms\Components\Hidden::make('created_by')
                    ->default(fn () => auth()->id()),
                Forms\Components\Hidden::make('updated_by')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('ชื่อบทความ')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('category')
                    ->label('หมวดหมู่')
                    ->formatStateUsing(fn ($state) => KnowledgeArticle::CATEGORIES[$state] ?? $state)
                    ->colors([
                        'primary' => 'getting-started',
                        'success' => 'purchase-requisition',
                        'warning' => 'purchase-order',
                        'info' => 'goods-receipt',
                        'secondary' => 'vendor-management',
                        'danger' => 'reports',
                        'gray' => 'administration',
                    ]),
                    
                Tables\Columns\BadgeColumn::make('type')
                    ->label('ประเภท')
                    ->formatStateUsing(fn ($state) => KnowledgeArticle::TYPES[$state] ?? $state)
                    ->colors([
                        'primary' => KnowledgeArticle::TYPE_DOCUMENT,
                        'success' => KnowledgeArticle::TYPE_VIDEO,
                    ]),
                    
                Tables\Columns\IconColumn::make('is_published')
                    ->label('เผยแพร่')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('views_count')
                    ->label('จำนวนผู้เข้าชม')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('ผู้สร้าง')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('วันที่สร้าง')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('หมวดหมู่')
                    ->options(KnowledgeArticle::CATEGORIES),
                    
                Tables\Filters\SelectFilter::make('type')
                    ->label('ประเภท')
                    ->options(KnowledgeArticle::TYPES),
                    
                Tables\Filters\Filter::make('is_published')
                    ->label('เผยแพร่แล้ว')
                    ->query(fn (Builder $query): Builder => $query->where('is_published', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('ดู'),
                Tables\Actions\EditAction::make()
                    ->label('แก้ไข'),
                Tables\Actions\DeleteAction::make()
                    ->label('ลบ'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('ลบที่เลือก'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListKnowledgeArticles::route('/'),
            'create' => Pages\CreateKnowledgeArticle::route('/create'),
            'edit' => Pages\EditKnowledgeArticle::route('/{record}/edit'),
        ];
    }
}
