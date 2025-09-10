<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMilestoneResource\Pages;
use App\Filament\Resources\PaymentMilestoneResource\RelationManagers;
use App\Models\PaymentMilestone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentMilestoneResource extends Resource
{
    protected static ?string $model = PaymentMilestone::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'งวดการจ่ายเงิน';
    
    protected static ?string $modelLabel = 'งวดการจ่าย';
    
    protected static ?string $pluralModelLabel = 'งวดการจ่ายเงิน';
    
    protected static ?string $navigationGroup = 'Financial Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ข้อมูลใบสั่งซื้อ')
                    ->schema([
                        Forms\Components\Select::make('purchase_order_id')
                            ->label('เลขที่ PO')
                            ->relationship('purchaseOrder', 'po_number')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                    
                Forms\Components\Section::make('ข้อมูลงวดการจ่าย')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('milestone_number')
                                ->label('งวดที่')
                                ->numeric()
                                ->required()
                                ->minValue(1),
                                
                            Forms\Components\TextInput::make('milestone_title')
                                ->label('ชื่องวด')
                                ->maxLength(191)
                                ->placeholder('เช่น มัดจำ, งวดกลาง'),
                                
                            Forms\Components\TextInput::make('percentage')
                                ->label('เปอร์เซ็นต์')
                                ->numeric()
                                ->suffix('%')
                                ->step(0.01)
                                ->minValue(0)
                                ->maxValue(100),
                        ]),
                        
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('amount')
                                ->label('จำนวนเงิน')
                                ->numeric()
                                ->prefix('฿')
                                ->step(0.01)
                                ->required(),
                                
                            Forms\Components\DatePicker::make('due_date')
                                ->label('วันครบกำหนด')
                                ->native(false),
                        ]),
                        
                        Forms\Components\Textarea::make('payment_terms')
                            ->label('เงื่อนไขการจ่าย')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('สถานะและการจ่ายเงิน')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('status')
                                ->label('สถานะ')
                                ->options(PaymentMilestone::getStatusOptions())
                                ->default('pending')
                                ->required(),
                                
                            Forms\Components\DatePicker::make('paid_date')
                                ->label('วันที่จ่าย')
                                ->native(false),
                        ]),
                        
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('paid_amount')
                                ->label('จำนวนเงินที่จ่าย')
                                ->numeric()
                                ->prefix('฿')
                                ->step(0.01),
                                
                            Forms\Components\TextInput::make('payment_reference')
                                ->label('เลขที่อ้างอิง')
                                ->maxLength(191)
                                ->placeholder('เช่น เลขที่เช็ค, Transaction ID'),
                        ]),
                        
                        Forms\Components\Textarea::make('payment_notes')
                            ->label('หมายเหตุการจ่าย')
                            ->rows(2)
                            ->columnSpanFull(),
                            
                        Forms\Components\Select::make('paid_by')
                            ->label('ผู้จ่าย')
                            ->relationship('paidBy', 'name')
                            ->searchable()
                            ->preload(),
                    ]),
                    
                Forms\Components\Hidden::make('company_id')
                    ->default(fn () => session('selected_company_id')),
                Forms\Components\Hidden::make('created_by')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('เลขที่ PO')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('milestone_number')
                    ->label('งวดที่')
                    ->alignCenter()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('milestone_title')
                    ->label('ชื่องวด')
                    ->searchable()
                    ->limit(20),
                    
                Tables\Columns\TextColumn::make('percentage')
                    ->label('%')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->alignCenter()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('จำนวนเงิน')
                    ->formatStateUsing(fn ($state) => '฿' . number_format($state, 2))
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('สถานะ')
                    ->formatStateUsing(fn ($state) => PaymentMilestone::getStatusOptions()[$state] ?? $state)
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'due' => 'info',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray'
                    }),
                    
                Tables\Columns\TextColumn::make('due_date')
                    ->label('ครบกำหนด')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('paid_date')
                    ->label('วันที่จ่าย')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('จำนวนที่จ่าย')
                    ->formatStateUsing(fn ($state) => $state ? '฿' . number_format($state, 2) : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('payment_reference')
                    ->label('เลขที่อ้างอิง')
                    ->searchable()
                    ->limit(15)
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('paidBy.name')
                    ->label('ผู้จ่าย')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('วันที่สร้าง')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
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
            'index' => Pages\ListPaymentMilestones::route('/'),
            'create' => Pages\CreatePaymentMilestone::route('/create'),
            'edit' => Pages\EditPaymentMilestone::route('/{record}/edit'),
        ];
    }
}
