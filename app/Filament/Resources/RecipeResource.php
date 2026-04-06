<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecipeResource\Pages;
use App\Filament\Resources\RecipeResource\RelationManagers;
use App\Models\Recipe;
use App\Services\RecipeFactory;
use App\Services\RecipeImportService;
use App\Services\RecipePDFService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecipeResource extends Resource
{
    protected static ?string $model = Recipe::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Recipes';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image_path')
                            ->image()
                            ->directory(config('recipes.image_path'))
                            ->disk(config('recipes.image_disk'))
                            ->imageEditor()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Recipe Details')
                    ->schema([
                        Forms\Components\TextInput::make('servings')
                            ->numeric()
                            ->suffix('servings'),
                        Forms\Components\Select::make('difficulty')
                            ->options([
                                'easy' => 'Easy',
                                'medium' => 'Medium',
                                'hard' => 'Hard',
                            ])
                            ->native(false),
                        Forms\Components\TextInput::make('prep_time_minutes')
                            ->numeric()
                            ->suffix('min')
                            ->label('Prep Time'),
                        Forms\Components\TextInput::make('cook_time_minutes')
                            ->numeric()
                            ->suffix('min')
                            ->label('Cook Time'),
                        Forms\Components\TextInput::make('total_time_minutes')
                            ->numeric()
                            ->suffix('min')
                            ->label('Total Time'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Source & Attribution')
                    ->schema([
                        Forms\Components\TextInput::make('source_url')
                            ->url()
                            ->maxLength(255)
                            ->label('Source URL'),
                        Forms\Components\TextInput::make('source_name')
                            ->maxLength(255)
                            ->label('Source Name'),
                        Forms\Components\TextInput::make('author')
                            ->maxLength(255),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Organization')
                    ->schema([
                        Forms\Components\Select::make('protein_id')
                            ->relationship('protein', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Primary Protein'),
                        Forms\Components\Select::make('layout_id')
                            ->relationship('layout', 'name')
                            ->searchable()
                            ->preload()
                            ->label('PDF Layout')
                            ->helperText('Choose a layout template for PDF generation'),
                        Forms\Components\Toggle::make('is_published')
                            ->label('Published')
                            ->helperText('Ready to share or print'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Personal Notes')
                    ->schema([
                        Forms\Components\RichEditor::make('notes')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'italic',
                                'orderedList',
                                'redo',
                                'undo',
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
//                Tables\Columns\ImageColumn::make('image_path')
//                    ->circular(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Recipe $record): string => $record->source_name ?? ''),
                Tables\Columns\TextColumn::make('protein.name')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('servings')
                    ->suffix(' servings')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_time_minutes')
                    ->suffix(' min')
                    ->label('Time')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('difficulty')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'easy' => 'success',
                        'medium' => 'warning',
                        'hard' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean()
                    ->label('Published')
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
                Tables\Filters\SelectFilter::make('protein_id')
                    ->relationship('protein', 'name')
                    ->label('Protein'),
                Tables\Filters\SelectFilter::make('difficulty')
                    ->options([
                        'easy' => 'Easy',
                        'medium' => 'Medium',
                        'hard' => 'Hard',
                    ]),
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published')
                    ->boolean(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('download_pdf')
                        ->label('Download PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('layout')
                                ->label('PDF Layout')
                                ->options([
                                    'classic' => 'Classic (Georgia/Palatino)',
                                    'modern' => 'Modern (Helvetica/Arial)',
                                    'rustic' => 'Rustic (Century Schoolbook/Garamond)',
                                ])
                                ->default(fn (Recipe $record) => $record->layout?->slug ?? 'classic')
                                ->required(),
                        ])
                        ->action(function (Recipe $record, array $data) {
                            $pdfService = new RecipePDFService();
                            return $pdfService->downloadRecipePDF($record, $data['layout']);
                        }),
                    Tables\Actions\Action::make('preview_pdf')
                        ->label('Preview PDF')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn (Recipe $record): string => route('recipes.pdf.preview', $record))
                        ->openUrlInNewTab(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RecipeIngredientsRelationManager::class,
            RelationManagers\StepsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecipes::route('/'),
            'create' => Pages\CreateRecipe::route('/create'),
            'edit' => Pages\EditRecipe::route('/{record}/edit'),
        ];
    }
}
