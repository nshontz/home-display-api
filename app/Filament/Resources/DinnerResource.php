<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DinnerResource\Pages;
use App\Filament\Resources\DinnerResource\RelationManagers;
use App\Models\Dinner;
use App\Models\Recipe;
use App\Services\RecipeImportService;
use App\Services\RecipeFactory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class DinnerResource extends Resource
{
    protected static ?string $model = Dinner::class;

    protected static ?string $navigationGroup = 'Dinners';
    protected static ?string $navigationIcon = 'heroicon-o-home';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('open_url')
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->url(fn ($record) => $record?->recipe_url)
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => !empty($record?->recipe_url))
                    )
                    ->maxLength(255),

                Forms\Components\Select::make('recipe_id')
                    ->label('Recipe')
                    ->relationship('recipe', 'title')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('title')
                            ->required(),
                    ])
                    ->helperText(fn ($record) => $record?->recipe_url ? new HtmlString(
                        '<a href="' . $record->recipe_url . '" target="_blank" class="text-primary-600 hover:underline">Source: ' . $record->recipe_url . '</a>'
                    ) : null),

                Forms\Components\Select::make('protein_id')
                    ->relationship('protein', 'name'),

                Forms\Components\DateTimePicker::make('complete'),
                Forms\Components\DateTimePicker::make('date'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('protein.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('recipe.title')
                    ->label('Linked Recipe')
                    ->sortable()
                    ->searchable()
                    ->placeholder('No recipe linked')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('recipe_url')
                    ->label('Source URL')
                    ->url(fn ($record) => $record->recipe_url)
                    ->openUrlInNewTab()
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->placeholder('No source')
                    ->limit(30)
                    ->toggleable()
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('protein')
                    ->relationship('protein', 'name'),
                Tables\Filters\Filter::make('missing_protein')
                    ->label('Missing Protein')
                    ->query(fn(Builder $query): Builder => $query->whereNull('protein_id'))
                    ->toggle(),
                Tables\Filters\Filter::make('missing_recipe')
                    ->label('Missing Recipe')
                    ->query(fn(Builder $query): Builder => $query->whereNull('recipe_id'))
                    ->toggle(),
                Tables\Filters\Filter::make('has_source_url')
                    ->label('Has Source URL')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('event->location'))
                    ->toggle(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('import_recipe')
                    ->label('Import Recipe')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (Dinner $record) => !empty($record->recipe_url) && empty($record->recipe_id))
                    ->requiresConfirmation()
                    ->modalHeading(fn (Dinner $record) => 'Import Recipe: ' . $record->title)
                    ->modalDescription(fn (Dinner $record) => 'Import recipe from: ' . $record->recipe_url)
                    ->action(function (Dinner $record) {
                        try {
                            $importService = new RecipeImportService();
                            $recipeData = $importService->importFromUrl($record->recipe_url);
                            $recipe = RecipeFactory::create($recipeData);

                            // Link the recipe to this dinner
                            $record->recipe_id = $recipe->id;
                            $record->save();

                            Notification::make()
                                ->success()
                                ->title('Recipe imported successfully!')
                                ->body("'{$recipe->title}' has been imported and linked to this dinner.")
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Import failed')
                                ->body('Could not import recipe: ' . $e->getMessage())
                                ->send();
                        }
                    }),
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
            'index' => Pages\ListDinners::route('/'),
            'create' => Pages\CreateDinner::route('/create'),
            'edit' => Pages\EditDinner::route('/{record}/edit'),
        ];
    }
}
