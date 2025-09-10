<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\Department;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Users (ผู้ใช้)';
    protected static ?string $navigationGroup = 'User Management (จัดการผู้ใช้)';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['department', 'roles']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->roles()?->where('name', 'admin')->exists() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User Information')
                    ->description('Basic user account details')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Full Name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('email')
                                ->label('Email Address')
                                ->email()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                        ]),
                        
                        Grid::make(2)->schema([
                            Forms\Components\Select::make('department_id')
                                ->label('Department')
                                ->relationship('department', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Forms\Components\TextInput::make('password')
                                ->label('Password')
                                ->password()
                                ->required(fn (string $context): bool => $context === 'create')
                                ->dehydrated(fn ($state) => filled($state))
                                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                ->minLength(8)
                                ->maxLength(255),
                        ]),
                        
                        Forms\Components\Select::make('roles')
                            ->label('Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ]),
                    
                Section::make('Email Notification Preferences')
                    ->description('Configure email notification settings')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('email_pr_notifications')
                                ->label('PR Notifications')
                                ->helperText('General PR notifications')
                                ->default(true),
                            Forms\Components\Toggle::make('email_pr_created')
                                ->label('PR Created')
                                ->helperText('When new PR is created')
                                ->default(false),
                        ]),
                        
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('email_pr_approved')
                                ->label('PR Approved')
                                ->helperText('When PR is approved')
                                ->default(true),
                            Forms\Components\Toggle::make('email_pr_rejected')
                                ->label('PR Rejected')
                                ->helperText('When PR is rejected')
                                ->default(true),
                        ]),
                        
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('email_po_notifications')
                                ->label('PO Notifications')
                                ->helperText('General PO notifications')
                                ->default(true),
                            Forms\Components\Toggle::make('email_po_approved')
                                ->label('PO Approved')
                                ->helperText('When PO is approved')
                                ->default(true),
                        ]),
                        
                        Forms\Components\Toggle::make('email_po_rejected')
                            ->label('PO Rejected')
                            ->helperText('When PO is rejected')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(','),
                    
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark'),
                    
                Tables\Columns\IconColumn::make('email_pr_notifications')
                    ->label('PR Notifications')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\IconColumn::make('email_po_notifications')
                    ->label('PO Notifications')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('verified')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'))
                    ->label('Verified Users'),
                    
                Tables\Filters\Filter::make('unverified')
                    ->query(fn (Builder $query): Builder => $query->whereNull('email_verified_at'))
                    ->label('Unverified Users'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        // Prevent deleting yourself
                        if ($record->id === auth()->id()) {
                            throw new \Exception('You cannot delete your own account.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Prevent deleting yourself in bulk actions
                            if ($records->contains('id', auth()->id())) {
                                throw new \Exception('You cannot delete your own account.');
                            }
                        }),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
