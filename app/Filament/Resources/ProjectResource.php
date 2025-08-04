<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use App\Models\User;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    
    protected static ?string $navigationGroup = 'Project Management';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'name';
    
    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_projects');
    }
    
    public static function canCreate(): bool
    {
        return Auth::user()->can('create_projects');
    }
    
    public static function canEdit($record): bool
    {
        return Auth::user()->can('edit_projects');
    }
    
    public static function canDelete($record): bool
    {
        return Auth::user()->can('delete_projects');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Project Information')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Status & Priority')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'planning' => 'Planning',
                                'in_progress' => 'In Progress',
                                'review' => 'Review',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('planning')
                            ->required(),
                        Forms\Components\Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->default('medium')
                            ->required(),
                        Forms\Components\Select::make('assigned_to')
                            ->relationship('assignedUser', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])->columns(3),
                    
                Forms\Components\Section::make('Timeline')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->native(false),
                        Forms\Components\DatePicker::make('due_date')
                            ->native(false),
                        Forms\Components\DatePicker::make('completed_date')
                            ->native(false)
                            ->hidden(fn (callable $get) => $get('status') !== 'completed'),
                    ])->columns(3),
                    
                Forms\Components\Section::make('Budget & Time Tracking')
                    ->schema([
                        Forms\Components\TextInput::make('budget')
                            ->numeric()
                            ->prefix('$')
                            ->placeholder('0.00'),
                        Forms\Components\TextInput::make('estimated_hours')
                            ->numeric()
                            ->suffix('hours')
                            ->placeholder('0'),
                        Forms\Components\TextInput::make('actual_hours')
                            ->numeric()
                            ->suffix('hours')
                            ->placeholder('0'),
                    ])->columns(3),
                    
                Forms\Components\Hidden::make('created_by')
                    ->default(Auth::id()),
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
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'planning',
                        'warning' => 'in_progress',
                        'info' => 'review',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'secondary' => 'low',
                        'primary' => 'medium',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Assigned To')
                    ->searchable()
                    ->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => 
                        $record->due_date && $record->due_date->isPast() && !in_array($record->status, ['completed', 'cancelled'])
                            ? 'danger' 
                            : null
                    ),
                Tables\Columns\TextColumn::make('budget')
                    ->money('USD')
                    ->sortable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('progress')
                    ->label('Progress')
                    ->getStateUsing(function ($record) {
                        if ($record->estimated_hours && $record->actual_hours) {
                            $percentage = min(($record->actual_hours / $record->estimated_hours) * 100, 100);
                            return round($percentage) . '%';
                        }
                        return 'N/A';
                    })
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state === 'N/A' => 'secondary',
                        (int) str_replace('%', '', $state) < 50 => 'danger',
                        (int) str_replace('%', '', $state) < 80 => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(),
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'planning' => 'Planning',
                        'in_progress' => 'In Progress',
                        'review' => 'Review',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),
                Tables\Filters\SelectFilter::make('client_id')
                    ->relationship('client', 'name')
                    ->label('Client')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->relationship('assignedUser', 'name')
                    ->label('Assigned To')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query): Builder => $query->overdue())
                    ->label('Overdue Projects'),
                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query): Builder => $query->active())
                    ->label('Active Projects'),
                Tables\Filters\Filter::make('completed_this_month')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereMonth('completed_date', now()->month)
                              ->whereYear('completed_date', now()->year)
                    )
                    ->label('Completed This Month'),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // We can add relation managers here later for tasks, invoices, documents, content
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        $activeCount = static::getModel()::active()->count();
        return $activeCount > 5 ? 'warning' : 'primary';
    }
}
