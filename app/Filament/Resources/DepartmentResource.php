<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Filament\Resources\DepartmentResource\RelationManagers;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    
    protected static ?string $navigationGroup = 'HR Management';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $recordTitleAttribute = 'name';
    
    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_departments');
    }
    
    public static function canCreate(): bool
    {
        return Auth::user()->can('create_departments');
    }
    
    public static function canEdit($record): bool
    {
        return Auth::user()->can('edit_departments');
    }
    
    public static function canDelete($record): bool
    {
        return Auth::user()->can('delete_departments');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Department Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('manager_id')
                            ->relationship('manager', 'full_name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('first_name')
                                    ->required(),
                                Forms\Components\TextInput::make('last_name')
                                    ->required(),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required(),
                                Forms\Components\TextInput::make('employee_id')
                                    ->required()
                                    ->default(fn () => 'EMP-' . strtoupper(uniqid())),
                                Forms\Components\TextInput::make('position')
                                    ->required(),
                                Forms\Components\DatePicker::make('hire_date')
                                    ->required()
                                    ->default(now()),
                            ]),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(50)
                    ->placeholder('No Description'),
                Tables\Columns\TextColumn::make('manager.full_name')
                    ->label('Manager')
                    ->searchable(['manager.first_name', 'manager.last_name'])
                    ->sortable()
                    ->placeholder('No Manager'),
                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Employees')
                    ->getStateUsing(fn ($record) => $record->employees()->count())
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('System Users')
                    ->getStateUsing(fn ($record) => $record->users()->count())
                    ->badge()
                    ->color('success'),
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
                Tables\Filters\TernaryFilter::make('manager_id')
                    ->label('Has Manager')
                    ->placeholder('All departments')
                    ->trueLabel('With manager')
                    ->falseLabel('Without manager'),
                Tables\Filters\Filter::make('has_employees')
                    ->query(fn (Builder $query): Builder => $query->has('employees'))
                    ->label('Has Employees'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            // We can add relation managers for employees here later
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}
