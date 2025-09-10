<?php

namespace App\Filament\Resources\VendorEvaluationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Get;
use App\Models\VendorEvaluationItem;

class EvaluationItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'evaluationItems';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('criteria_category')
                    ->label('หมวดหมู่')
                    ->options(VendorEvaluationItem::getCategoryOptions())
                    ->required(),
                    
                TextInput::make('criteria_name')
                    ->label('หัวข้อหลัก')
                    ->placeholder('เช่น คุณภาพสินค้า/บริการ')
                    ->required(),
                    
                Textarea::make('criteria_description')
                    ->label('หัวข้อประเมิน')
                    ->placeholder('รายละเอียดเกณฑ์การประเมิน')
                    ->required()
                    ->rows(3),
                    
                Toggle::make('is_applicable')
                    ->label('สามารถประเมินได้')
                    ->default(true)
                    ->live(),
                    
                Radio::make('score')
                    ->label('คะแนนประเมิน')
                    ->options([
                        '4' => '4 - ดีมาก',
                        '3' => '3 - ดี',
                        '2' => '2 - พอใช้',
                        '1' => '1 - ควรปรับปรุง',
                    ])
                    ->inline()
                    ->visible(fn (Get $get) => $get('is_applicable'))
                    ->required(fn (Get $get) => $get('is_applicable')),
                    
                Textarea::make('comments')
                    ->label('ความคิดเห็น/หมายเหตุ')
                    ->rows(2),
                    
                Textarea::make('evidence')
                    ->label('หลักฐาน/ตัวอย่าง')
                    ->rows(2),
                    
                TextInput::make('weight')
                    ->label('ระดับความสำคัญ (1-5)')
                    ->helperText('1=สำคัญน้อย, 3=สำคัญปานกลาง, 5=สำคัญมาก')
                    ->numeric()
                    ->default(3.00)
                    ->step(1)
                    ->minValue(1)
                    ->maxValue(5),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('criteria_name')
            ->columns([
                TextColumn::make('criteria_category')
                    ->label('หมวดหมู่')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'quality' => 'success',
                        'delivery' => 'info', 
                        'service' => 'warning',
                        'performance' => 'primary',
                        default => 'secondary',
                    }),
                    
                TextColumn::make('criteria_name')
                    ->label('หัวข้อหลัก')
                    ->wrap(),
                    
                TextColumn::make('criteria_description')
                    ->label('หัวข้อประเมิน')
                    ->limit(50)
                    ->wrap(),
                    
                BadgeColumn::make('is_applicable')
                    ->label('สถานะ')
                    ->colors([
                        'success' => true,
                        'gray' => false,
                    ])
                    ->formatStateUsing(fn (bool $state): string => $state ? 'ใช้งาน' : 'N/A'),
                    
                BadgeColumn::make('score_text')
                    ->label('คะแนน')
                    ->colors([
                        'success' => 'ดีมาก',
                        'info' => 'ดี',
                        'warning' => 'พอใช้',
                        'danger' => 'ควรปรับปรุง',
                        'gray' => 'N/A',
                    ]),
                    
                TextColumn::make('weight')
                    ->label('ระดับความสำคัญ')
                    ->formatStateUsing(fn ($state) => match((int)$state) {
                        1 => '1 - สำคัญน้อย',
                        2 => '2 - ค่อนข้างสำคัญ',
                        3 => '3 - สำคัญปานกลาง',
                        4 => '4 - สำคัญมาก',
                        5 => '5 - สำคัญมากที่สุด',
                        default => $state
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('criteria_category')
                    ->label('หมวดหมู่')
                    ->options(VendorEvaluationItem::getCategoryOptions()),
                    
                Tables\Filters\TernaryFilter::make('is_applicable')
                    ->label('สถานะการใช้งาน')
                    ->placeholder('ทั้งหมด')
                    ->trueLabel('ใช้งาน')
                    ->falseLabel('N/A'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('เพิ่มเกณฑ์การประเมิน'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('criteria_category');
    }
}
