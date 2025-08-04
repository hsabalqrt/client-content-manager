<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationGroup = 'Task Management';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'title';
    
    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_tasks');
    }
    
    public static function canCreate(): bool
    {
        return Auth::user()->can('create_tasks') || Auth::user()->can('assign_tasks');
    }
    
    public static function canEdit($record): bool
    {
        return Auth::user()->can('edit_tasks') || ($record->assigned_to === Auth::id());
    }
    
    public static function canDelete($record): bool
    {
        return Auth::user()->can('delete_tasks');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Task Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'design' => 'Design',
                                'development' => 'Development',
                                'content' => 'Content',
                                'meeting' => 'Meeting',
                                'other' => 'Other',
                            ])
                            ->default('other')
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
                    ])->columns(2),
                    
                Forms\Components\Section::make('Assignment & Timeline')
                    ->schema([
                        Forms\Components\Select::make('assigned_to')
                            ->relationship('assignedUser', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(Auth::id()),
                        Forms\Components\Select::make('status')
                            ->options([
                                'todo' => 'To Do',
                                'in_progress' => 'In Progress',
                                'review' => 'Review',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('todo')
                            ->required()
                            ->live(),
                        Forms\Components\DatePicker::make('due_date')
                            ->native(false),
                        Forms\Components\TextInput::make('estimated_hours')
                            ->numeric()
                            ->suffix('hours')
                            ->placeholder('0.00'),
                    ])->columns(4),
                    
                Forms\Components\Section::make('Project Association')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('project_id', null);
                                }
                            }),
                        Forms\Components\Select::make('project_id')
                            ->relationship(
                                'project', 
                                'name',
                                fn (Builder $query, callable $get) => 
                                    $get('client_id') 
                                        ? $query->where('client_id', $get('client_id'))
                                        : $query
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Time Tracking')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_time')
                            ->native(false)
                            ->hidden(fn (callable $get) => !in_array($get('status'), ['in_progress', 'completed'])),
                        Forms\Components\DateTimePicker::make('end_time')
                            ->native(false)
                            ->hidden(fn (callable $get) => $get('status') !== 'completed'),
                        Forms\Components\TextInput::make('actual_hours')
                            ->numeric()
                            ->suffix('hours')
                            ->placeholder('0.00')
                            ->hidden(fn (callable $get) => !in_array($get('status'), ['in_progress', 'completed', 'review'])),
                    ])->columns(3),
                    
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('created_by')
                            ->default(Auth::id()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(30),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'design',
                        'success' => 'development',
                        'warning' => 'content',
                        'info' => 'meeting',
                        'secondary' => 'other',
                    ])
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'todo',
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->placeholder('No Client'),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->placeholder('No Project'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => 
                        $record->is_overdue ? 'danger' : null
                    ),
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'todo' => 'To Do',
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
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'design' => 'Design',
                        'development' => 'Development',
                        'content' => 'Content',
                        'meeting' => 'Meeting',
                        'other' => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->relationship('assignedUser', 'name')
                    ->label('Assigned To')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('client_id')
                    ->relationship('client', 'name')
                    ->label('Client')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('project_id')
                    ->relationship('project', 'name')
                    ->label('Project')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('my_tasks')
                    ->query(fn (Builder $query): Builder => $query->where('assigned_to', Auth::id()))
                    ->label('My Tasks'),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query): Builder => $query->overdue())
                    ->label('Overdue Tasks'),
                Tables\Filters\Filter::make('today')
                    ->query(fn (Builder $query): Builder => $query->today())
                    ->label('Due Today'),
                Tables\Filters\Filter::make('this_week')
                    ->query(fn (Builder $query): Builder => $query->thisWeek())
                    ->label('Due This Week'),
            ])
            ->actions([
                Tables\Actions\Action::make('start')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (Task $record): bool => 
                        $record->status === 'todo' && $record->assigned_to === Auth::id()
                    )
                    ->action(function (Task $record) {
                        $record->update([
                            'status' => 'in_progress',
                            'start_time' => now(),
                        ]);
                    }),
                Tables\Actions\Action::make('complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Task $record): bool => 
                        in_array($record->status, ['in_progress', 'review']) && $record->assigned_to === Auth::id()
                    )
                    ->action(function (Task $record) {
                        $record->update([
                            'status' => 'completed',
                            'end_time' => now(),
                        ]);
                    })
                    ->requiresConfirmation(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_completed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (in_array($record->status, ['todo', 'in_progress', 'review'])) {
                                    $record->update([
                                        'status' => 'completed',
                                        'end_time' => now(),
                                    ]);
                                }
                            }
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // We can add relation managers here later if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::assignedTo(Auth::id())->pending()->count();
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        $pendingCount = static::getModel()::assignedTo(Auth::id())->pending()->count();
        return $pendingCount > 0 ? 'warning' : 'success';
    }
}
