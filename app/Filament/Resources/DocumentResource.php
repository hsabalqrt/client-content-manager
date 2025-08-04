<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\DocumentResource\RelationManagers;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';
    
    protected static ?string $navigationGroup = 'Document Management';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'title';
    
    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_documents');
    }
    
    public static function canCreate(): bool
    {
        return Auth::user()->can('create_documents');
    }
    
    public static function canEdit($record): bool
    {
        return Auth::user()->can('edit_documents');
    }
    
    public static function canDelete($record): bool
    {
        return Auth::user()->can('delete_documents');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('category')
                            ->options([
                                'contract' => 'Contract',
                                'proposal' => 'Proposal',
                                'invoice' => 'Invoice',
                                'receipt' => 'Receipt',
                                'other' => 'Other',
                            ])
                            ->default('other')
                            ->required(),
                        Forms\Components\Toggle::make('is_confidential')
                            ->label('Confidential Document')
                            ->default(false),
                    ])->columns(2),
                    
                Forms\Components\Section::make('File Upload')
                    ->schema([
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Document File')
                            ->required()
                            ->directory('documents')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'])
                            ->maxSize(10240) // 10MB
                            ->downloadable()
                            ->previewable()
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
                                    // Clear project selection when client changes
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
                        Forms\Components\Hidden::make('uploaded_by')
                            ->default(Auth::id()),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('category')
                    ->colors([
                        'primary' => 'contract',
                        'success' => 'proposal',
                        'warning' => 'invoice',
                        'info' => 'receipt',
                        'secondary' => 'other',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('file_name')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('file_size_formatted')
                    ->label('Size')
                    ->getStateUsing(fn ($record) => $record->file_size_formatted),
                Tables\Columns\IconColumn::make('is_confidential')
                    ->label('Confidential')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye-slash')
                    ->falseIcon('heroicon-o-eye')
                    ->trueColor('danger')
                    ->falseColor('success'),
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
                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'contract' => 'Contract',
                        'proposal' => 'Proposal',
                        'invoice' => 'Invoice',
                        'receipt' => 'Receipt',
                        'other' => 'Other',
                    ]),
                Tables\Filters\TernaryFilter::make('is_confidential')
                    ->label('Confidential')
                    ->placeholder('All documents')
                    ->trueLabel('Confidential only')
                    ->falseLabel('Public only'),
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
                Tables\Filters\Filter::make('images')
                    ->query(fn (Builder $query): Builder => $query->where('mime_type', 'like', 'image/%'))
                    ->label('Images Only'),
                Tables\Filters\Filter::make('pdfs')
                    ->query(fn (Builder $query): Builder => $query->where('mime_type', 'application/pdf'))
                    ->label('PDFs Only'),
                Tables\Filters\Filter::make('this_month')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereMonth('created_at', now()->month)
                              ->whereYear('created_at', now()->year)
                    )
                    ->label('Uploaded This Month'),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Document $record): string => Storage::url($record->file_path))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function (Document $record) {
                        // Delete the file when document is deleted
                        if (Storage::exists($record->file_path)) {
                            Storage::delete($record->file_path);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function ($records) {
                            // Delete all files when documents are bulk deleted
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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = static::getModel()::count();
        return $count > 50 ? 'warning' : 'primary';
    }
}
