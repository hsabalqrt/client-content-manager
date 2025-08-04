<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentResource\Pages;
use App\Filament\Resources\ContentResource\RelationManagers;
use App\Models\Content;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ContentResource extends Resource
{
    protected static ?string $model = Content::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    
    protected static ?string $navigationGroup = 'Content Management';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'title';
    
    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_content');
    }
    
    public static function canCreate(): bool
    {
        return Auth::user()->can('create_content');
    }
    
    public static function canEdit($record): bool
    {
        return Auth::user()->can('edit_content');
    }
    
    public static function canDelete($record): bool
    {
        return Auth::user()->can('delete_content');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Content Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'image' => 'Image',
                                'video' => 'Video',
                                'document' => 'Document',
                                'marketing_material' => 'Marketing Material',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('category')
                            ->placeholder('e.g., Logo, Banner, Brochure')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('alt_text')
                            ->label('Alt Text')
                            ->placeholder('Describe the content for accessibility')
                            ->hidden(fn (callable $get) => !in_array($get('type'), ['image', 'marketing_material']))
                            ->maxLength(255),
                    ])->columns(2),
                    
                Forms\Components\Section::make('File Upload')
                    ->schema([
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Content File')
                            ->required()
                            ->directory('content')
                            ->acceptedFileTypes(function (callable $get) {
                                return match ($get('type')) {
                                    'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                                    'video' => ['video/mp4', 'video/webm', 'video/ogg'],
                                    'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                                    'marketing_material' => ['image/jpeg', 'image/png', 'application/pdf'],
                                    default => ['*'],
                                };
                            })
                            ->maxSize(20480) // 20MB
                            ->downloadable()
                            ->previewable()
                            ->imagePreviewHeight('250')
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $file = $state;
                                    $set('file_name', $file->getClientOriginalName());
                                    $set('file_size', $file->getSize());
                                    $set('mime_type', $file->getMimeType());
                                }
                            }),
                        Forms\Components\Hidden::make('file_name'),
                        Forms\Components\Hidden::make('file_size'),
                        Forms\Components\Hidden::make('mime_type'),
                    ]),
                    
                Forms\Components\Section::make('Tags & Metadata')
                    ->schema([
                        Forms\Components\TagsInput::make('tags')
                            ->placeholder('Add tags for better organization')
                            ->separator(',')
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Status & Approval')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'approved' => 'Approved',
                                'archived' => 'Archived',
                            ])
                            ->default('draft')
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('approved_by')
                            ->relationship('approver', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->hidden(fn (callable $get) => $get('status') !== 'approved'),
                        Forms\Components\DateTimePicker::make('approved_at')
                            ->native(false)
                            ->nullable()
                            ->hidden(fn (callable $get) => $get('status') !== 'approved'),
                    ])->columns(3),
                    
                Forms\Components\Section::make('Associations')
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
                        Forms\Components\Hidden::make('created_by')
                            ->default(Auth::id()),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('file_path')
                    ->label('Preview')
                    ->height(50)
                    ->width(50)
                    ->defaultImageUrl(url('/images/placeholder.png'))
                    ->circular(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'image',
                        'primary' => 'video',
                        'warning' => 'document',
                        'info' => 'marketing_material',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->searchable()
                    ->placeholder('No Category'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'approved',
                        'danger' => 'archived',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('file_size_formatted')
                    ->label('Size')
                    ->getStateUsing(fn ($record) => $record->file_size_formatted),
                Tables\Columns\TextColumn::make('tags')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->limitList(3)
                    ->expandableLimitedList(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->placeholder('No Client'),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->placeholder('No Project'),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Approved By')
                    ->sortable()
                    ->placeholder('Not Approved')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'image' => 'Image',
                        'video' => 'Video',
                        'document' => 'Document',
                        'marketing_material' => 'Marketing Material',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'approved' => 'Approved',
                        'archived' => 'Archived',
                    ]),
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
                Tables\Filters\Filter::make('pending_approval')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'draft'))
                    ->label('Pending Approval'),
                Tables\Filters\Filter::make('this_month')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereMonth('created_at', now()->month)
                              ->whereYear('created_at', now()->year)
                    )
                    ->label('Created This Month'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Content $record): bool => 
                        $record->status === 'draft' && Auth::user()->can('approve_content')
                    )
                    ->action(function (Content $record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ]);
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Content $record): string => Storage::url($record->file_path))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function (Content $record) {
                        if (Storage::exists($record->file_path)) {
                            Storage::delete($record->file_path);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (): bool => Auth::user()->can('approve_content'))
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->status === 'draft') {
                                    $record->update([
                                        'status' => 'approved',
                                        'approved_by' => Auth::id(),
                                        'approved_at' => now(),
                                    ]);
                                }
                            }
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function ($records) {
                            foreach ($records as $record) {
                                if (Storage::exists($record->file_path)) {
                                    Storage::delete($record->file_path);
                                }
                            }
                        }),
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
            'index' => Pages\ListContents::route('/'),
            'create' => Pages\CreateContent::route('/create'),
            'edit' => Pages\EditContent::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'draft')->count();
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        $draftCount = static::getModel()::where('status', 'draft')->count();
        return $draftCount > 0 ? 'warning' : 'success';
    }
}
